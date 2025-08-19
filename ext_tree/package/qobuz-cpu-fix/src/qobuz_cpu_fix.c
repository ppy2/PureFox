#define _GNU_SOURCE
#include <dlfcn.h>
#include <time.h>
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <pthread.h>
#include <alsa/asoundlib.h>

// Counters for monitoring
static long time_calls = 0;
static int show_stats = 0;

// Cache for clock_gettime
static struct timespec cached_time = {0, 0};
static long counter = 0;

// Keepalive for qobuz-connect
static pthread_t keepalive_thread = 0;
static volatile int keepalive_active = 0;
static pthread_mutex_t keepalive_mutex = PTHREAD_MUTEX_INITIALIZER;

// Intercept regular clock_gettime (not 64-bit version)
int clock_gettime(clockid_t clk_id, struct timespec *tp) {
    static int (*real_clock_gettime)(clockid_t, struct timespec *) = NULL;
    
    if (!real_clock_gettime) {
        real_clock_gettime = dlsym(RTLD_NEXT, "clock_gettime");
        if (!real_clock_gettime) {
            fprintf(stderr, "[CPU_FIX] ERROR: Cannot find clock_gettime\n");
            errno = ENOSYS;
            return -1;
        }
    }
    
    // Cache only CLOCK_MONOTONIC
    if (clk_id != CLOCK_MONOTONIC && clk_id != CLOCK_MONOTONIC_COARSE) {
        return real_clock_gettime(clk_id, tp);
    }
    
    time_calls++;
    counter++;
    
    // Update cache every 10 calls
    if (counter >= 10 || cached_time.tv_sec == 0) {
        real_clock_gettime(CLOCK_MONOTONIC, &cached_time);
        counter = 0;
        
        if (show_stats && time_calls % 50000 == 0) {
            fprintf(stderr, "[CPU_FIX] clock_gettime: %ld calls\n", time_calls);
        }
    }
    
    *tp = cached_time;
    return 0;
}

// Check if clock_gettime64 exists and intercept if it does
__attribute__((weak))
int clock_gettime64(clockid_t clk_id, struct timespec *tp) {
    return clock_gettime(clk_id, tp);
}

// Keepalive worker thread
static void* keepalive_worker(void* arg) {
    (void)arg;
    fprintf(stderr, "[KEEPALIVE] Thread started\n");
    
    snd_mixer_t *mixer = NULL;
    snd_mixer_elem_t *elem = NULL;
    
    // ALSA mixer initialization
    if (snd_mixer_open(&mixer, 0) < 0) {
        fprintf(stderr, "[KEEPALIVE] Failed to open mixer\n");
        return NULL;
    }
    
    if (snd_mixer_attach(mixer, "default") < 0) {
        fprintf(stderr, "[KEEPALIVE] Failed to attach mixer to default\n");
        snd_mixer_close(mixer);
        return NULL;
    }
    
    if (snd_mixer_selem_register(mixer, NULL, NULL) < 0 || snd_mixer_load(mixer) < 0) {
        fprintf(stderr, "[KEEPALIVE] Failed to register/load mixer\n");
        snd_mixer_close(mixer);
        return NULL;
    }
    
    // Find first element with switch control (preferred) or volume control (fallback)
    snd_mixer_elem_t *switch_elem = NULL;
    snd_mixer_elem_t *volume_elem = NULL;
    
    for (elem = snd_mixer_first_elem(mixer); elem; elem = snd_mixer_elem_next(elem)) {
        if (snd_mixer_selem_has_playback_switch(elem)) {
            switch_elem = elem;
            // If it also has volume, prefer this one
            if (snd_mixer_selem_has_playback_volume(elem)) {
                break;
            }
        }
        if (!volume_elem && snd_mixer_selem_has_playback_volume(elem)) {
            volume_elem = elem;
        }
    }
    
    // Prefer switch control if available, otherwise use volume control
    elem = switch_elem ? switch_elem : volume_elem;
    
    if (!elem) {
        fprintf(stderr, "[KEEPALIVE] No suitable mixer element found\n");
        snd_mixer_close(mixer);
        return NULL;
    }
    
    int has_switch = snd_mixer_selem_has_playback_switch(elem);
    fprintf(stderr, "[KEEPALIVE] Using element: %s (switch: %s)\n", 
            snd_mixer_selem_get_name(elem), has_switch ? "yes" : "no");
    
    while (1) {
        pthread_mutex_lock(&keepalive_mutex);
        if (!keepalive_active) {
            pthread_mutex_unlock(&keepalive_mutex);
            break;
        }
        pthread_mutex_unlock(&keepalive_mutex);
        
        // Keepalive action: switch toggle if available, otherwise mixer reload
        int current_switch;
        if (snd_mixer_selem_get_playback_switch(elem, SND_MIXER_SCHN_FRONT_LEFT, &current_switch) == 0) {
            // Fast mute/unmute toggle
            snd_mixer_selem_set_playback_switch_all(elem, !current_switch);
            usleep(100); // 0.1ms  
            snd_mixer_selem_set_playback_switch_all(elem, current_switch);
        } else {
            // No switch control - just reload mixer state (keepalive without changes)
            snd_mixer_load(mixer);
        }
        
        // Wait 30 minutes
        for (int i = 0; i < 1800; i++) {
            sleep(1);
            pthread_mutex_lock(&keepalive_mutex);
            if (!keepalive_active) {
                pthread_mutex_unlock(&keepalive_mutex);
                goto cleanup;
            }
            pthread_mutex_unlock(&keepalive_mutex);
        }
    }
    
cleanup:
    snd_mixer_close(mixer);
    fprintf(stderr, "[KEEPALIVE] Thread stopped\n");
    return NULL;
}

static void start_keepalive(void) {
    pthread_mutex_lock(&keepalive_mutex);
    if (!keepalive_active) {
        keepalive_active = 1;
        if (pthread_create(&keepalive_thread, NULL, keepalive_worker, NULL) == 0) {
            fprintf(stderr, "[KEEPALIVE] Started\n");
        } else {
            keepalive_active = 0;
            fprintf(stderr, "[KEEPALIVE] Failed to start\n");
        }
    }
    pthread_mutex_unlock(&keepalive_mutex);
}

static void stop_keepalive(void) {
    pthread_mutex_lock(&keepalive_mutex);
    if (keepalive_active) {
        keepalive_active = 0;
        pthread_mutex_unlock(&keepalive_mutex);
        if (keepalive_thread) {
            pthread_join(keepalive_thread, NULL);
            keepalive_thread = 0;
        }
        fprintf(stderr, "[KEEPALIVE] Stopped\n");
    } else {
        pthread_mutex_unlock(&keepalive_mutex);
    }
}

__attribute__((constructor))
void init() {
    char *env = getenv("CPU_FIX_STATS");
    show_stats = (env != NULL);
    fprintf(stderr, "[CPU_FIX] V2 loaded (clock_gettime). Stats: %s\n", show_stats ? "ON" : "OFF");
    
    // Start keepalive
    start_keepalive();
}

__attribute__((destructor))  
void cleanup() {
    stop_keepalive();
    pthread_mutex_destroy(&keepalive_mutex);
    fprintf(stderr, "[CPU_FIX] Unloaded\n");
}