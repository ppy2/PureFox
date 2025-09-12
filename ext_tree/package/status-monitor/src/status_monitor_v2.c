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

// REAL ALSA API for reading volume
void get_volume_status_alsa(char* volume, int* muted) {
    snd_mixer_t *handle;
    snd_mixer_selem_id_t *sid;
    snd_mixer_elem_t *elem;
    long min, max, val;
    int switch_val;
    
    // DON'T set default values - get real ones
    strcpy(volume, "0%");
    *muted = 1;
    
    // Open mixer
    if (snd_mixer_open(&handle, 0) < 0) {
        return;
    }
    
    if (snd_mixer_attach(handle, "default") < 0) {
        snd_mixer_close(handle);
        return;
    }
    
    if (snd_mixer_selem_register(handle, NULL, NULL) < 0) {
        snd_mixer_close(handle);
        return;
    }
    
    if (snd_mixer_load(handle) < 0) {
        snd_mixer_close(handle);
        return;
    }
    
    // Create element selector
    snd_mixer_selem_id_alloca(&sid);
    snd_mixer_selem_id_set_index(sid, 0);
    snd_mixer_selem_id_set_name(sid, "PCM");
    
    elem = snd_mixer_find_selem(handle, sid);
    if (!elem) {
        // Try Master if PCM not found
        snd_mixer_selem_id_set_name(sid, "Master");
        elem = snd_mixer_find_selem(handle, sid);
    }
    
    if (elem) {
        // Get volume
        if (snd_mixer_selem_has_playback_volume(elem)) {
            snd_mixer_selem_get_playback_volume_range(elem, &min, &max);
            snd_mixer_selem_get_playback_volume(elem, SND_MIXER_SCHN_FRONT_LEFT, &val);
            
            int percent = (int)((val - min) * 100 / (max - min));
            snprintf(volume, 16, "%d%%", percent);
        }
        
        // Get mute state
        if (snd_mixer_selem_has_playback_switch(elem)) {
            snd_mixer_selem_get_playback_switch(elem, SND_MIXER_SCHN_FRONT_LEFT, &switch_val);
            *muted = !switch_val; // switch_val = 1 means enabled (not muted)
        }
    }
    
    snd_mixer_close(handle);
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
    
    printf("Status monitor v2 started (PID: %d) - using ALSA API\n", getpid());
    
    // INITIAL status update WITHOUT writing to file for initialization
    active_service = get_active_service();
    alsa_state = get_alsa_state();
    usb_dac = check_usb_dac();
    get_volume_status_alsa(volume, &muted);
    
    while (running) {
        // Get all statuses
        active_service = get_active_service();
        alsa_state = get_alsa_state();
        usb_dac = check_usb_dac();
        get_volume_status_alsa(volume, &muted);
        
        // Write JSON to file
        fp = fopen(STATUS_FILE, "w");
        if (fp) {
            fprintf(fp, "{\n");
            fprintf(fp, "  \"active_service\": \"%s\",\n", active_service);
            fprintf(fp, "  \"alsa_state\": \"%s\",\n", alsa_state);
            fprintf(fp, "  \"usb_dac\": %s,\n", usb_dac ? "true" : "false");
            fprintf(fp, "  \"volume\": \"%s\",\n", volume);
            fprintf(fp, "  \"muted\": %s,\n", muted ? "true" : "false");
            fprintf(fp, "  \"timestamp\": %ld\n", time(NULL));
            fprintf(fp, "}\n");
            fclose(fp);
        }
        
        sleep(UPDATE_INTERVAL);
    }
    
    // Cleanup
    unlink(LOCK_FILE);
    printf("Status monitor v2 stopped\n");
    
    return 0;
}