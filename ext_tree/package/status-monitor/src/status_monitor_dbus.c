#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>
#include <sys/stat.h>
#include <time.h>
#include <fcntl.h>
#include <dirent.h>
#include <ctype.h>
#include <errno.h>
#include <alsa/asoundlib.h>
#include <dbus/dbus.h>

#define STATUS_FILE "/tmp/system_status.json"
#define LOCK_FILE "/tmp/status_monitor.lock"
#define DBUS_SERVICE_NAME "org.purefox.statusmonitor"
#define DBUS_OBJECT_PATH "/org/purefox/statusmonitor"
#define DBUS_INTERFACE_NAME "org.purefox.StatusMonitor"

volatile int running = 1;
DBusConnection *dbus_conn = NULL;

void signal_handler(int sig) {
    running = 0;
}

// Structure for storing current state
typedef struct {
    char active_service[32];
    char alsa_state[16];
    int usb_dac;
    char volume[16];
    int muted;
    int volume_control_available;
    int mute_control_available;
    time_t last_update;
} system_status_t;

system_status_t current_status = {
    .active_service = "",
    .alsa_state = "unknown",
    .usb_dac = 0,
    .volume = "100%",
    .muted = 0,
    .volume_control_available = 1,
    .mute_control_available = 1,
    .last_update = 0
};

// Check active audio process through /proc
void get_active_service(char* service) {
    DIR *proc_dir;
    struct dirent *entry;
    FILE *cmdline_file;
    char path[512];  // Increase buffer for safety
    char cmdline[1024];
    char *process_name;
    
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
    
    strcpy(service, "");
    
    proc_dir = opendir("/proc");
    if (!proc_dir) return;
    
    while ((entry = readdir(proc_dir)) != NULL) {
        // Skip non-PID directories
        if (!isdigit(entry->d_name[0])) continue;
        
        // Check process name length
        if (strlen(entry->d_name) > 100) continue;
        
        // Read cmdline
        snprintf(path, sizeof(path), "/proc/%s/cmdline", entry->d_name);
        cmdline_file = fopen(path, "r");
        if (!cmdline_file) continue;
        
        if (fgets(cmdline, sizeof(cmdline), cmdline_file)) {
            // Extract process name from full path
            process_name = strrchr(cmdline, '/');
            if (process_name) {
                process_name++; // Skip '/'
            } else {
                process_name = cmdline;
            }
            
            // Remove arguments (after first null byte)
            for (int i = 0; services[i][0]; i++) {
                if (strcmp(process_name, services[i][0]) == 0) {
                    strcpy(service, services[i][1]);
                    fclose(cmdline_file);
                    closedir(proc_dir);
                    return;
                }
            }
        }
        fclose(cmdline_file);
    }
    
    closedir(proc_dir);
}

// Check ALSA state
void get_alsa_state(char* state) {
    FILE *fp;
    char buffer[256];
    
    strcpy(state, "unknown");
    
    // Check /etc/output
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
        return;
    }
    
    // Fallback through /etc/asound.conf
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
}

// Check USB DAC
int check_usb_dac() {
    struct stat st;
    return (stat("/sys/class/sound/card1", &st) == 0) ? 1 : 0;
}

// ALSA API for reading volume
void get_volume_status_alsa(char* volume, int* muted) {
    snd_mixer_t *handle;
    snd_mixer_selem_id_t *sid;
    snd_mixer_elem_t *elem;
    long min, max, val;
    int switch_val;
    
    strcpy(volume, "100%"); // Default to 100% instead of 50%
    *muted = 0;
    
    if (snd_mixer_open(&handle, 0) < 0) return;
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
    
    // Search for any playback volume control, not just PCM/Master
    elem = NULL;
    for (elem = snd_mixer_first_elem(handle); elem; elem = snd_mixer_elem_next(elem)) {
        if (snd_mixer_selem_is_active(elem) && snd_mixer_selem_has_playback_volume(elem)) {
            const char *name = snd_mixer_selem_get_name(elem);
            // Prefer PCM, Master, or any volume-named control
            if (name && (strcmp(name, "PCM") == 0 || strcmp(name, "Master") == 0 || 
                        strstr(name, "volume") != NULL || strstr(name, "Volume") != NULL ||
                        strstr(name, "playback") != NULL || strstr(name, "Playback") != NULL)) {
                break;
            }
        }
    }
    
    // If no preferred control found, use first available playback volume control
    if (!elem) {
        for (elem = snd_mixer_first_elem(handle); elem; elem = snd_mixer_elem_next(elem)) {
            if (snd_mixer_selem_is_active(elem) && snd_mixer_selem_has_playback_volume(elem)) {
                break;
            }
        }
    }
    
    if (elem) {
        if (snd_mixer_selem_has_playback_volume(elem)) {
            snd_mixer_selem_get_playback_volume_range(elem, &min, &max);
            
            // Try to read from the appropriate channel
            int read_success = 0;
            
            // First try MONO channel (for mono controls like PCM Simple)
            if (snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_MONO)) {
                if (snd_mixer_selem_get_playback_volume(elem, SND_MIXER_SCHN_MONO, &val) >= 0) {
                    read_success = 1;
                }
            }
            
            // If MONO failed, try FRONT_LEFT
            if (!read_success && snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_FRONT_LEFT)) {
                if (snd_mixer_selem_get_playback_volume(elem, SND_MIXER_SCHN_FRONT_LEFT, &val) >= 0) {
                    read_success = 1;
                }
            }
            
            if (read_success && max > min) {
                int percent = (int)((val - min) * 100 / (max - min));
                snprintf(volume, 16, "%d%%", percent);
            }
        }
        
        if (snd_mixer_selem_has_playback_switch(elem)) {
            // Try to read mute state from the appropriate channel
            if (snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_MONO)) {
                if (snd_mixer_selem_get_playback_switch(elem, SND_MIXER_SCHN_MONO, &switch_val) >= 0) {
                    *muted = !switch_val;
                }
            } else if (snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_FRONT_LEFT)) {
                if (snd_mixer_selem_get_playback_switch(elem, SND_MIXER_SCHN_FRONT_LEFT, &switch_val) >= 0) {
                    *muted = !switch_val;
                }
            }
        }
    }
    
    snd_mixer_close(handle);
}

// Check USB DAC control availability using ALSA API
void check_usb_controls(int* volume_available, int* mute_available) {
    *volume_available = 0;
    *mute_available = 0;
    
    snd_mixer_t *mixer = NULL;
    snd_mixer_elem_t *elem;
    
    if (snd_mixer_open(&mixer, 0) >= 0) {
        if (snd_mixer_attach(mixer, "hw:1") >= 0) {  // USB card is typically hw:1
            if (snd_mixer_selem_register(mixer, NULL, NULL) >= 0) {
                if (snd_mixer_load(mixer) >= 0) {
                    // Iterate through all mixer elements
                    for (elem = snd_mixer_first_elem(mixer); elem; elem = snd_mixer_elem_next(elem)) {
                        if (snd_mixer_selem_is_active(elem)) {
                            // Check for playback volume capability
                            if (!*volume_available && snd_mixer_selem_has_playback_volume(elem)) {
                                // Verify it has proper playback channels
                                if (snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_FRONT_LEFT) ||
                                    snd_mixer_selem_has_playback_channel(elem, SND_MIXER_SCHN_MONO)) {
                                    *volume_available = 1;
                                }
                            }
                            
                            // Check for playback switch (mute) capability  
                            if (!*mute_available && snd_mixer_selem_has_playback_switch(elem)) {
                                *mute_available = 1;
                            }
                            
                            // Early exit when both found
                            if (*volume_available && *mute_available) break;
                        }
                    }
                }
            }
        }
        snd_mixer_close(mixer);
    }
}

// Update JSON file
void update_status_file() {
    FILE *fp = fopen(STATUS_FILE, "w");
    if (!fp) {
        printf("ERROR: Cannot open %s for writing: %s\n", STATUS_FILE, strerror(errno));
        return;
    }
    
    fprintf(fp, "{\n");
    fprintf(fp, "  \"active_service\": \"%s\",\n", current_status.active_service);
    fprintf(fp, "  \"alsa_state\": \"%s\",\n", current_status.alsa_state);
    fprintf(fp, "  \"usb_dac\": %s,\n", current_status.usb_dac ? "true" : "false");
    fprintf(fp, "  \"volume\": \"%s\",\n", current_status.volume);
    fprintf(fp, "  \"muted\": %s,\n", current_status.muted ? "true" : "false");
    fprintf(fp, "  \"volume_control_available\": %s,\n", current_status.volume_control_available ? "true" : "false");
    fprintf(fp, "  \"mute_control_available\": %s,\n", current_status.mute_control_available ? "true" : "false");
    fprintf(fp, "  \"timestamp\": %ld,\n", current_status.last_update);
    fprintf(fp, "  \"source\": \"dbus_monitor\"\n");
    fprintf(fp, "}\n");
    
    fclose(fp);
}

// Full status update
void refresh_all_status() {
    get_active_service(current_status.active_service);
    get_alsa_state(current_status.alsa_state);
    current_status.usb_dac = check_usb_dac();
    get_volume_status_alsa(current_status.volume, &current_status.muted);
    
    // Update control availability based on current state
    if (strcmp(current_status.alsa_state, "usb") == 0 && !current_status.usb_dac) {
        // USB mode without physical DAC - no controls
        current_status.volume_control_available = 0;
        current_status.mute_control_available = 0;
    } else if (strcmp(current_status.alsa_state, "usb") == 0 && current_status.usb_dac) {
        // USB DAC present - check real controls
        check_usb_controls(&current_status.volume_control_available, &current_status.mute_control_available);
    } else {
        // I2S - assume controls available
        current_status.volume_control_available = 1;
        current_status.mute_control_available = 1;
    }
    
    current_status.last_update = time(NULL);
    
    update_status_file();
    printf("Status updated: service=%s, alsa=%s, volume=%s\n", 
           current_status.active_service, current_status.alsa_state, current_status.volume);
}

// D-Bus signal handler
DBusHandlerResult handle_dbus_message(DBusConnection *connection, DBusMessage *message, void *user_data) {
    const char *interface = dbus_message_get_interface(message);
    const char *member = dbus_message_get_member(message);
    const char *path = dbus_message_get_path(message);
    
    printf("D-Bus signal: %s.%s on %s\n", interface ? interface : "null", 
           member ? member : "null", path ? path : "null");
    
    // Handle service switching signals
    if (interface && strcmp(interface, DBUS_INTERFACE_NAME) == 0) {
        if (member && strcmp(member, "ServiceChanged") == 0) {
            printf("Service change detected via D-Bus\n");
            // Update only services and ALSA state
            get_active_service(current_status.active_service);
            get_alsa_state(current_status.alsa_state);
            current_status.usb_dac = check_usb_dac();
            current_status.last_update = time(NULL);
            update_status_file();
        } else if (member && strcmp(member, "VolumeChanged") == 0) {
            printf("Volume change detected via D-Bus\n");
            // Update only volume
            get_volume_status_alsa(current_status.volume, &current_status.muted);
            current_status.last_update = time(NULL);
            update_status_file();
        }
    }
    
    // Handle system signals (start/stop processes)
    if (interface && strstr(interface, "systemd")) {
        printf("SystemD signal detected, refreshing services\n");
        get_active_service(current_status.active_service);
        get_alsa_state(current_status.alsa_state);
        current_status.last_update = time(NULL);
        update_status_file();
    }
    
    return DBUS_HANDLER_RESULT_NOT_YET_HANDLED;
}

// D-Bus initialization
int init_dbus() {
    DBusError error;
    dbus_error_init(&error);
    
    dbus_conn = dbus_bus_get(DBUS_BUS_SYSTEM, &error);
    if (dbus_error_is_set(&error)) {
        printf("D-Bus connection error: %s\n", error.message);
        dbus_error_free(&error);
        return -1;
    }
    
    // Register our service
    dbus_bus_request_name(dbus_conn, DBUS_SERVICE_NAME, 
                         DBUS_NAME_FLAG_DO_NOT_QUEUE, &error);
    if (dbus_error_is_set(&error)) {
        printf("D-Bus name request error: %s\n", error.message);
        dbus_error_free(&error);
        return -1;
    }
    
    // Subscribe to our interface signals
    char match_rule[512];
    snprintf(match_rule, sizeof(match_rule), 
             "type='signal',interface='%s'", DBUS_INTERFACE_NAME);
    dbus_bus_add_match(dbus_conn, match_rule, &error);
    
    // Subscribe to system signals (optional)
    dbus_bus_add_match(dbus_conn, 
                      "type='signal',interface='org.freedesktop.systemd1.Manager'", 
                      &error);
    
    // Set message handler
    dbus_connection_add_filter(dbus_conn, handle_dbus_message, NULL, NULL);
    
    printf("D-Bus initialized successfully\n");
    return 0;
}

int main() {
    // Signal handler setup
    signal(SIGTERM, signal_handler);
    signal(SIGINT, signal_handler);
    
    // Create lock file
    FILE *fp = fopen(LOCK_FILE, "w");
    if (fp) {
        fprintf(fp, "%d\n", getpid());
        fclose(fp);
    }
    
    printf("D-Bus Status Monitor started (PID: %d)\n", getpid());
    
    // D-Bus initialization
    if (init_dbus() < 0) {
        printf("Failed to initialize D-Bus, falling back to polling mode\n");
        // Fallback to old approach
        while (running) {
            refresh_all_status();
            sleep(2);
        }
    } else {
        // Initial update
        refresh_all_status();
        
        // Event-driven mode
        printf("Entering D-Bus event loop\n");
        while (running) {
            // Process D-Bus messages
            dbus_connection_read_write_dispatch(dbus_conn, 1000); // 1 second timeout
            
            // Quick volume check (every 2 seconds)
            static time_t last_alsa_check = 0;
            // Full update (every 30 seconds as backup)
            static time_t last_full_refresh = 0;
            time_t now = time(NULL);
            
            // Check ALSA state every second for instant response
            if (now - last_alsa_check > 1) {
                char old_volume[16];
                int old_muted = current_status.muted;
                strcpy(old_volume, current_status.volume);
                
                get_volume_status_alsa(current_status.volume, &current_status.muted);
                
                // If volume changed, update JSON immediately
                if (strcmp(old_volume, current_status.volume) != 0 || old_muted != current_status.muted) {
                    printf("Volume changed: %s (muted: %s)\n", current_status.volume, current_status.muted ? "yes" : "no");
                    current_status.last_update = now;
                    update_status_file();
                }
                last_alsa_check = now;
            }
            
            if (now - last_full_refresh > 30) {
                refresh_all_status();
                last_full_refresh = now;
            }
        }
        
        if (dbus_conn) {
            dbus_connection_close(dbus_conn);
        }
    }
    
    // Cleanup
    unlink(LOCK_FILE);
    printf("D-Bus Status Monitor stopped\n");
    
    return 0;
}