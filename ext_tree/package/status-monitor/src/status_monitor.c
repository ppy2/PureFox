#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>
#include <sys/stat.h>
#include <time.h>
#include <fcntl.h>
#include <alsa/asoundlib.h>

#define STATUS_FILE "/tmp/system_status.json"
#define LOCK_FILE "/tmp/status_monitor.lock"
#define UPDATE_INTERVAL 1  // 1 second - fast response
#define USB_CONTROLS_CACHE "/tmp/usb_controls_cache"

volatile int running = 1;

void signal_handler(int sig) {
    running = 0;
}

// Check active audio process
char* get_active_service() {
    static char service[32] = "";
    FILE *fp;
    char buffer[256];
    
    // List of services to check (in priority order)
    const char* services[][2] = {
        {"networkaudiod", "naa"},
        {"raat_app", "raat"},
        {"mpd", "mpd"},
        {"squeeze2upnp", "squeeze2upn"},
        {"ap2renderer", "aprenderer"},
        {"aplayer", "aplayer"},
        {"apscream", "apscream"},
        {"squeezelite", "lms"},
        {"shairport-sync", "shairport"},
        {"librespot", "spotify"},
        {"qobuz-connect", "qobuz"},
        {"tidalconnect", "tidalconnect"},
        {NULL, NULL}
    };
    
    fp = popen("ps -eo comm --no-headers", "r");
    if (!fp) return "";
    
    // Read all processes and look for matches
    while (fgets(buffer, sizeof(buffer), fp)) {
        // Remove newline character
        buffer[strcspn(buffer, "\n")] = 0;
        
        for (int i = 0; services[i][0]; i++) {
            if (strcmp(buffer, services[i][0]) == 0) {
                strcpy(service, services[i][1]);
                pclose(fp);
                return service;
            }
        }
    }
    
    pclose(fp);
    service[0] = '\0';
    return service;
}

// Check ALSA state
char* get_alsa_state() {
    static char state[16] = "unknown";
    FILE *fp;
    char buffer[256];
    
    // First check /etc/output
    fp = fopen("/etc/output", "r");
    if (fp) {
        if (fgets(buffer, sizeof(buffer), fp)) {
            buffer[strcspn(buffer, "\n")] = 0;
            if (strcasecmp(buffer, "USB") == 0) {
                strcpy(state, "usb");
            } else if (strcasecmp(buffer, "I2S") == 0) {
                strcpy(state, "i2s");
            }
        }
        fclose(fp);
        return state;
    }
    
    // Fallback - check /etc/asound.conf
    fp = fopen("/etc/asound.conf", "r");
    if (fp) {
        while (fgets(buffer, sizeof(buffer), fp)) {
            if (strstr(buffer, "card 1")) {
                strcpy(state, "usb");
                break;
            } else if (strstr(buffer, "card 0")) {
                strcpy(state, "i2s");
                break;
            }
        }
        fclose(fp);
    }
    
    return state;
}

// Check USB DAC
int check_usb_dac() {
    struct stat st;
    return (stat("/sys/class/sound/card1", &st) == 0) ? 1 : 0;
}

// Direct ALSA control reading WITHOUT calling amixer
void get_volume_status_direct(char* volume, int* muted) {
    FILE *fp;
    char buffer[256];
    long vol_min = 0, vol_max = 100, vol_cur = 50;
    int mute_state = 0;
    
    strcpy(volume, "50%");
    *muted = 0;
    
    // Read /proc/asound/card0/codec#0 if available (some systems)
    fp = fopen("/proc/asound/card0/codec#0", "r");
    if (fp) {
        while (fgets(buffer, sizeof(buffer), fp)) {
            // Look for volume information in codec info
            if (strstr(buffer, "volume")) {
                // Parse if we find the needed line
            }
        }
        fclose(fp);
    }
    
    // Alternative path: read through /sys/class/sound
    char control_path[256];
    snprintf(control_path, sizeof(control_path), "/sys/class/sound/controlC0");
    
    // Try directly through ALSA control interface
    int fd = open("/dev/snd/controlC0", O_RDONLY);
    if (fd >= 0) {
        // Here we need to use ALSA control API, but it's complex without libraries
        close(fd);
    }
    
    // TEMPORARY SOLUTION: read cached value or use simple amixer
    // But WITHOUT full amixer run - only if file is old
    static time_t last_check = 0;
    static char cached_vol[16] = "50%";
    static int cached_mute = 0;
    
    time_t now = time(NULL);
    
    // Update every 1 second for responsive volume control using direct ALSA API
    if (now - last_check > 1) {
        snd_mixer_t *mixer = NULL;
        snd_mixer_elem_t *elem;
        
        if (snd_mixer_open(&mixer, 0) >= 0) {
            if (snd_mixer_attach(mixer, "default") >= 0) {
                if (snd_mixer_selem_register(mixer, NULL, NULL) >= 0) {
                    if (snd_mixer_load(mixer) >= 0) {
                        // Look for PCM or Master volume control
                        for (elem = snd_mixer_first_elem(mixer); elem; elem = snd_mixer_elem_next(elem)) {
                            const char *name = snd_mixer_selem_get_name(elem);
                            if (name && (strcmp(name, "PCM") == 0 || strcmp(name, "Master") == 0)) {
                                if (snd_mixer_selem_has_playback_volume(elem)) {
                                    long vol_min, vol_max, vol_current;
                                    if (snd_mixer_selem_get_playback_volume_range(elem, &vol_min, &vol_max) >= 0) {
                                        if (snd_mixer_selem_get_playback_volume(elem, SND_MIXER_SCHN_FRONT_LEFT, &vol_current) >= 0) {
                                            int percentage = (int)((vol_current - vol_min) * 100 / (vol_max - vol_min));
                                            snprintf(cached_vol, sizeof(cached_vol), "%d%%", percentage);
                                        }
                                    }
                                }
                                
                                if (snd_mixer_selem_has_playback_switch(elem)) {
                                    int switch_val;
                                    if (snd_mixer_selem_get_playback_switch(elem, SND_MIXER_SCHN_FRONT_LEFT, &switch_val) >= 0) {
                                        cached_mute = !switch_val; // switch_val = 1 means on, 0 means muted
                                    }
                                }
                                break; // Found PCM or Master, stop searching
                            }
                        }
                    }
                }
            }
            snd_mixer_close(mixer);
        }
        last_check = now;
    }
    
    strcpy(volume, cached_vol);
    *muted = cached_mute;
}

// Check USB DAC volume/mute control availability and update cache
void check_usb_controls_availability(const char* alsa_state, int usb_dac) {
    static time_t last_control_check = 0;
    static int cached_volume_available = 1;
    static int cached_mute_available = 1;
    
    time_t now = time(NULL);
    
    // Check USB controls only when USB DAC is present and every 10 seconds
    if (strcmp(alsa_state, "usb") == 0 && usb_dac && (now - last_control_check > 10)) {
        FILE *fp;
        char buffer[256];
        int volume_found = 0, mute_found = 0;
        
        // Direct ALSA API access - much more efficient
        snd_mixer_t *mixer = NULL;
        snd_mixer_elem_t *elem;
        
        if (snd_mixer_open(&mixer, 0) >= 0) {
            if (snd_mixer_attach(mixer, "hw:1") >= 0) {
                if (snd_mixer_selem_register(mixer, NULL, NULL) >= 0) {
                    if (snd_mixer_load(mixer) >= 0) {
                        // Iterate through all mixer elements
                        for (elem = snd_mixer_first_elem(mixer); elem; elem = snd_mixer_elem_next(elem)) {
                            if (snd_mixer_selem_is_active(elem)) {
                                // Check for playback volume capability
                                if (!volume_found && snd_mixer_selem_has_playback_volume(elem)) {
                                    // Verify it has proper playback channels
                                    if (snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_FRONT_LEFT) ||
                                        snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_MONO)) {
                                        volume_found = 1;
                                    }
                                }
                                
                                // Check for playback switch (mute) capability  
                                if (!mute_found && snd_mixer_selem_has_playback_switch(elem)) {
                                    mute_found = 1;
                                }
                                
                                // Early exit when both found
                                if (volume_found && mute_found) break;
                            }
                        }
                    }
                }
            }
            snd_mixer_close(mixer);
        }
        
        cached_volume_available = volume_found;
        cached_mute_available = mute_found;
        last_control_check = now;
        
        // Update cache file
        FILE *cache_fp = fopen(USB_CONTROLS_CACHE, "w");
        if (cache_fp) {
            fprintf(cache_fp, "%d|%d", volume_found, mute_found);
            fclose(cache_fp);
        }
    } else if (strcmp(alsa_state, "i2s") == 0) {
        // I2S always has controls
        cached_volume_available = 1;
        cached_mute_available = 1;
    } else if (strcmp(alsa_state, "usb") == 0 && !usb_dac) {
        // USB mode without DAC - no controls
        cached_volume_available = 0;
        cached_mute_available = 0;
    }
}

int main() {
    FILE *fp;
    char *active_service;
    char *alsa_state;
    char volume[16];
    int usb_dac, muted;
    
    // Signal handler setup
    signal(SIGTERM, signal_handler);
    signal(SIGINT, signal_handler);
    
    // Create lock file
    fp = fopen(LOCK_FILE, "w");
    if (fp) {
        fprintf(fp, "%d\n", getpid());
        fclose(fp);
    }
    
    printf("Status monitor started (PID: %d)\n", getpid());
    
    while (running) {
        // Get all statuses
        active_service = get_active_service();
        alsa_state = get_alsa_state();
        usb_dac = check_usb_dac();
        get_volume_status_direct(volume, &muted);
        
        // Check and update USB controls availability
        check_usb_controls_availability(alsa_state, usb_dac);
        
        // Read control availability from cache
        int volume_control_available = 1, mute_control_available = 1;
        FILE *cache_fp = fopen(USB_CONTROLS_CACHE, "r");
        if (cache_fp) {
            fscanf(cache_fp, "%d|%d", &volume_control_available, &mute_control_available);
            fclose(cache_fp);
        }
        
        // Write JSON to file
        fp = fopen(STATUS_FILE, "w");
        if (fp) {
            fprintf(fp, "{\n");
            fprintf(fp, "  \"active_service\": \"%s\",\n", active_service);
            fprintf(fp, "  \"alsa_state\": \"%s\",\n", alsa_state);
            fprintf(fp, "  \"usb_dac\": %s,\n", usb_dac ? "true" : "false");
            fprintf(fp, "  \"volume\": \"%s\",\n", volume);
            fprintf(fp, "  \"muted\": %s,\n", muted ? "true" : "false");
            fprintf(fp, "  \"volume_control_available\": %s,\n", volume_control_available ? "true" : "false");
            fprintf(fp, "  \"mute_control_available\": %s,\n", mute_control_available ? "true" : "false");
            fprintf(fp, "  \"timestamp\": %ld\n", time(NULL));
            fprintf(fp, "}\n");
            fclose(fp);
        }
        
        sleep(UPDATE_INTERVAL);
    }
    
    // Cleanup
    unlink(LOCK_FILE);
    printf("Status monitor stopped\n");
    
    return 0;
}