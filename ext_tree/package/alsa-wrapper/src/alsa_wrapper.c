#define _GNU_SOURCE
#include <dlfcn.h>
#include <alsa/asoundlib.h>
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <unistd.h>
#include <pthread.h>
#include <errno.h>
#include <stdint.h>

// ===== MACROS AND CONFIGURATION =====
#define MAX_DEVICES 16
#define CLAMP(val, min, max) ((val) < (min) ? (min) : ((val) > (max) ? (max) : (val)))

#ifdef DEBUG
#define DEBUG_LOG(fmt, ...) fprintf(stderr, "[ALSA_WRAPPER] " fmt "\n", ##__VA_ARGS__)
#else
#define DEBUG_LOG(fmt, ...) do {} while(0)
#endif

// ===== DATA STRUCTURES =====
typedef struct {
    snd_pcm_t *pcm;
    float volume;
    int mute;
    int paused;
    snd_pcm_format_t format;
    unsigned int channels;
    unsigned int rate;
    pthread_mutex_t mutex;
} pcm_device_t;

// ===== GLOBAL VARIABLES =====
static pcm_device_t devices[MAX_DEVICES];
static int device_count = 0;
static pthread_mutex_t global_mutex = PTHREAD_MUTEX_INITIALIZER;

// ===== INITIALIZATION =====
__attribute__((constructor))
void wrapper_init(void) {
    DEBUG_LOG("ALSA Wrapper initialized for ARM");
    
    for (int i = 0; i < MAX_DEVICES; i++) {
        devices[i].pcm = NULL;
        devices[i].volume = 1.0f;
        devices[i].mute = 0;
        devices[i].paused = 0;
        devices[i].format = SND_PCM_FORMAT_S16_LE;
        devices[i].channels = 2;
        devices[i].rate = 48000;
        pthread_mutex_init(&devices[i].mutex, NULL);
    }
}

__attribute__((destructor))
void wrapper_cleanup(void) {
    for (int i = 0; i < MAX_DEVICES; i++) {
        pthread_mutex_destroy(&devices[i].mutex);
    }
    DEBUG_LOG("ALSA Wrapper cleanup completed");
}

// ===== UTILITIES =====
static pcm_device_t* find_device(snd_pcm_t *pcm) {
    for (int i = 0; i < device_count; i++) {
        if (devices[i].pcm == pcm) {
            return &devices[i];
        }
    }
    return NULL;
}

static pcm_device_t* add_device(snd_pcm_t *pcm) {
    pthread_mutex_lock(&global_mutex);
    
    if (device_count >= MAX_DEVICES) {
        pthread_mutex_unlock(&global_mutex);
        return NULL;
    }
    
    pcm_device_t *dev = &devices[device_count++];
    dev->pcm = pcm;
    dev->volume = 1.0f;
    dev->mute = 0;
    dev->paused = 0;
    dev->format = SND_PCM_FORMAT_S16_LE;
    dev->channels = 2;
    dev->rate = 48000;
    
    pthread_mutex_unlock(&global_mutex);
    DEBUG_LOG("Added device %p (total: %d)", pcm, device_count);
    return dev;
}

static void remove_device(snd_pcm_t *pcm) {
    pthread_mutex_lock(&global_mutex);
    
    for (int i = 0; i < device_count; i++) {
        if (devices[i].pcm == pcm) {
            // Shift array
            memmove(&devices[i], &devices[i + 1], 
                    (device_count - i - 1) * sizeof(pcm_device_t));
            device_count--;
            DEBUG_LOG("Removed device %p (remaining: %d)", pcm, device_count);
            break;
        }
    }
    
    pthread_mutex_unlock(&global_mutex);
}

// ===== AUDIO PROCESSING =====
static void scale_audio_data(void *buffer, snd_pcm_uframes_t frames, 
                            float volume, snd_pcm_format_t format, 
                            unsigned int channels) {
    if (volume == 1.0f) return;
    
    size_t total_samples = frames * channels;
    size_t i;
    
    // Support only basic formats for compatibility
    switch (format) {
        case SND_PCM_FORMAT_S8:
            for (i = 0; i < total_samples; i++) {
                ((int8_t *)buffer)[i] = (int8_t)(((int8_t *)buffer)[i] * volume);
            }
            break;
            
        case SND_PCM_FORMAT_U8:
            for (i = 0; i < total_samples; i++) {
                int val = (int)(((uint8_t *)buffer)[i] - 128) * volume + 128;
                ((uint8_t *)buffer)[i] = (uint8_t)CLAMP(val, 0, 255);
            }
            break;
            
        case SND_PCM_FORMAT_S16_LE:
        case SND_PCM_FORMAT_S16_BE:
            for (i = 0; i < total_samples; i++) {
                ((int16_t *)buffer)[i] = (int16_t)(((int16_t *)buffer)[i] * volume);
            }
            break;
            
        case SND_PCM_FORMAT_S32_LE:
        case SND_PCM_FORMAT_S32_BE:
            for (i = 0; i < total_samples; i++) {
                ((int32_t *)buffer)[i] = (int32_t)(((int32_t *)buffer)[i] * volume);
            }
            break;
            
        case SND_PCM_FORMAT_FLOAT_LE:
        case SND_PCM_FORMAT_FLOAT_BE:
            for (i = 0; i < total_samples; i++) {
                ((float *)buffer)[i] *= volume;
            }
            break;
            
        default:
            DEBUG_LOG("Unsupported format for volume scaling: %d", format);
            break;
    }
}

// ===== INTERCEPTED FUNCTIONS =====

int snd_pcm_open(snd_pcm_t **pcm, const char *name, snd_pcm_stream_t stream, int mode) {
    static int (*real_snd_pcm_open)(snd_pcm_t **, const char *, snd_pcm_stream_t, int) = NULL;
    
    if (!real_snd_pcm_open) {
        real_snd_pcm_open = dlsym(RTLD_NEXT, "snd_pcm_open");
        if (!real_snd_pcm_open) {
            return -ENOSYS;
        }
    }
    
    int ret = real_snd_pcm_open(pcm, name, stream, mode);
    if (ret == 0 && pcm && *pcm && stream == SND_PCM_STREAM_PLAYBACK) {
        add_device(*pcm);
        DEBUG_LOG("Opened PCM device: %s (%p)", name ? name : "unknown", *pcm);
    }
    
    return ret;
}

int snd_pcm_close(snd_pcm_t *pcm) {
    static int (*real_snd_pcm_close)(snd_pcm_t *) = NULL;
    
    if (!real_snd_pcm_close) {
        real_snd_pcm_close = dlsym(RTLD_NEXT, "snd_pcm_close");
    }
    
    remove_device(pcm);
    DEBUG_LOG("Closed PCM device: %p", pcm);
    
    return real_snd_pcm_close ? real_snd_pcm_close(pcm) : 0;
}

snd_pcm_sframes_t snd_pcm_writei(snd_pcm_t *pcm, const void *buffer, snd_pcm_uframes_t size) {
    static snd_pcm_sframes_t (*real_snd_pcm_writei)(snd_pcm_t *, const void *, snd_pcm_uframes_t) = NULL;
    
    if (!real_snd_pcm_writei) {
        real_snd_pcm_writei = dlsym(RTLD_NEXT, "snd_pcm_writei");
        if (!real_snd_pcm_writei) {
            return -ENOSYS;
        }
    }
    
    pcm_device_t *dev = find_device(pcm);
    if (!dev) {
        return real_snd_pcm_writei(pcm, buffer, size);
    }
    
    pthread_mutex_lock(&dev->mutex);
    
    // Pause handling
    if (dev->paused) {
        pthread_mutex_unlock(&dev->mutex);
        usleep(1000); // Simulate pause
        return size;
    }
    
    snd_pcm_sframes_t result;
    
    if (dev->mute) {
        // Create silence buffer
        size_t sample_size = snd_pcm_format_width(dev->format) / 8;
        size_t total_size = size * sample_size * dev->channels;
        void *silence = calloc(1, total_size);
        
        if (!silence) {
            pthread_mutex_unlock(&dev->mutex);
            return -ENOMEM;
        }
        
        result = real_snd_pcm_writei(pcm, silence, size);
        free(silence);
    } else if (dev->volume != 1.0f) {
        // Apply volume
        size_t sample_size = snd_pcm_format_width(dev->format) / 8;
        size_t total_size = size * sample_size * dev->channels;
        void *scaled = malloc(total_size);
        
        if (!scaled) {
            pthread_mutex_unlock(&dev->mutex);
            return -ENOMEM;
        }
        
        memcpy(scaled, buffer, total_size);
        scale_audio_data(scaled, size, dev->volume, dev->format, dev->channels);
        result = real_snd_pcm_writei(pcm, scaled, size);
        free(scaled);
    } else {
        result = real_snd_pcm_writei(pcm, buffer, size);
    }
    
    pthread_mutex_unlock(&dev->mutex);
    return result;
}

// ===== PAUSE SUPPORT =====
int snd_pcm_pause(snd_pcm_t *pcm, int enable) {
    pcm_device_t *dev = find_device(pcm);
    if (!dev) {
        // Fallback to original function
        static int (*real_snd_pcm_pause)(snd_pcm_t *, int) = NULL;
        if (!real_snd_pcm_pause) {
            real_snd_pcm_pause = dlsym(RTLD_NEXT, "snd_pcm_pause");
        }
        return real_snd_pcm_pause ? real_snd_pcm_pause(pcm, enable) : -ENOSYS;
    }
    
    pthread_mutex_lock(&dev->mutex);
    dev->paused = enable;
    pthread_mutex_unlock(&dev->mutex);
    
    DEBUG_LOG("Device %p %s", pcm, enable ? "paused" : "resumed");
    return 0;
}

int snd_pcm_hw_params_can_pause(const snd_pcm_hw_params_t *params) {
    (void)params; // Suppress unused parameter warning
    return 1;
}

// ===== PARAMETER TRACKING =====
int snd_pcm_set_params(snd_pcm_t *pcm,
                      snd_pcm_format_t format,
                      snd_pcm_access_t access,
                      unsigned int channels,
                      unsigned int rate,
                      int soft_resample,
                      unsigned int latency) {
    static int (*real_snd_pcm_set_params)(snd_pcm_t *, snd_pcm_format_t, snd_pcm_access_t, 
                                         unsigned int, unsigned int, int, unsigned int) = NULL;
    
    if (!real_snd_pcm_set_params) {
        real_snd_pcm_set_params = dlsym(RTLD_NEXT, "snd_pcm_set_params");
    }
    
    pcm_device_t *dev = find_device(pcm);
    if (dev) {
        pthread_mutex_lock(&dev->mutex);
        dev->rate = rate;
        dev->channels = channels;
        dev->format = format;
        pthread_mutex_unlock(&dev->mutex);
        DEBUG_LOG("Updated device %p params: rate=%u, channels=%u, format=%d", pcm, rate, channels, format);
    }
    
    return real_snd_pcm_set_params ? 
           real_snd_pcm_set_params(pcm, format, access, channels, rate, soft_resample, latency) : 
           -ENOSYS;
}

int snd_pcm_hw_params(snd_pcm_t *pcm, snd_pcm_hw_params_t *params) {
    static int (*real_snd_pcm_hw_params)(snd_pcm_t *, snd_pcm_hw_params_t *) = NULL;
    
    if (!real_snd_pcm_hw_params) {
        real_snd_pcm_hw_params = dlsym(RTLD_NEXT, "snd_pcm_hw_params");
    }
    
    pcm_device_t *dev = find_device(pcm);
    if (dev && params) {
        pthread_mutex_lock(&dev->mutex);
        
        unsigned int rate;
        if (snd_pcm_hw_params_get_rate(params, &rate, NULL) == 0) {
            dev->rate = rate;
        }
        if (snd_pcm_hw_params_get_channels(params, &dev->channels) != 0) {
            dev->channels = 2;
        }
        if (snd_pcm_hw_params_get_format(params, &dev->format) != 0) {
            dev->format = SND_PCM_FORMAT_S16_LE;
        }
        
        pthread_mutex_unlock(&dev->mutex);
        DEBUG_LOG("HW params set for device %p: rate=%u, channels=%u, format=%d", 
                  pcm, dev->rate, dev->channels, dev->format);
    }
    
    return real_snd_pcm_hw_params ? real_snd_pcm_hw_params(pcm, params) : -ENOSYS;
}

// ===== MIXER API EMULATION =====
int snd_mixer_selem_has_playback_volume(snd_mixer_elem_t *elem) {
    (void)elem;
    return 1;
}

int snd_mixer_selem_has_playback_switch(snd_mixer_elem_t *elem) {
    (void)elem;
    return 1;
}

int snd_mixer_selem_get_playback_volume_range(snd_mixer_elem_t *elem, long *min, long *max) {
    (void)elem;
    *min = 0;
    *max = 100;
    return 0;
}

int snd_mixer_selem_get_playback_volume(snd_mixer_elem_t *elem, 
                                       snd_mixer_selem_channel_id_t channel, 
                                       long *value) {
    (void)elem;
    (void)channel;
    
    if (device_count > 0) {
        *value = (long)(devices[0].volume * 100.0f);
    } else {
        *value = 100;
    }
    return 0;
}

int snd_mixer_selem_set_playback_volume_all(snd_mixer_elem_t *elem, long value) {
    (void)elem;
    
    float vol = (float)value / 100.0f;
    vol = CLAMP(vol, 0.0f, 1.0f);
    
    pthread_mutex_lock(&global_mutex);
    for (int i = 0; i < device_count; i++) {
        pthread_mutex_lock(&devices[i].mutex);
        devices[i].volume = vol;
        devices[i].mute = (vol == 0.0f);
        pthread_mutex_unlock(&devices[i].mutex);
    }
    pthread_mutex_unlock(&global_mutex);
    
    DEBUG_LOG("Set volume to %.2f for all devices", vol);
    return 0;
}

int snd_mixer_selem_set_playback_volume(snd_mixer_elem_t *elem, 
                                       snd_mixer_selem_channel_id_t channel, 
                                       long value) {
    (void)channel;
    return snd_mixer_selem_set_playback_volume_all(elem, value);
}

int snd_mixer_selem_get_playback_switch(snd_mixer_elem_t *elem, 
                                       snd_mixer_selem_channel_id_t channel, 
                                       int *value) {
    (void)elem;
    (void)channel;
    
    *value = device_count > 0 ? !devices[0].mute : 1;
    return 0;
}

int snd_mixer_selem_set_playback_switch(snd_mixer_elem_t *elem, 
                                       snd_mixer_selem_channel_id_t channel, 
                                       int value) {
    (void)elem;
    (void)channel;
    
    pthread_mutex_lock(&global_mutex);
    for (int i = 0; i < device_count; i++) {
        pthread_mutex_lock(&devices[i].mutex);
        devices[i].mute = !value;
        pthread_mutex_unlock(&devices[i].mutex);
    }
    pthread_mutex_unlock(&global_mutex);
    
    DEBUG_LOG("Set mute to %d for all devices", !value);
    return 0;
}

// ===== ADDITIONAL FUNCTIONS FOR EXTERNAL CONTROL =====
void alsa_wrapper_set_volume(float volume) {
    volume = CLAMP(volume, 0.0f, 1.0f);
    
    pthread_mutex_lock(&global_mutex);
    for (int i = 0; i < device_count; i++) {
        pthread_mutex_lock(&devices[i].mutex);
        devices[i].volume = volume;
        devices[i].mute = (volume == 0.0f);
        pthread_mutex_unlock(&devices[i].mutex);
    }
    pthread_mutex_unlock(&global_mutex);
}

void alsa_wrapper_set_mute(int mute) {
    pthread_mutex_lock(&global_mutex);
    for (int i = 0; i < device_count; i++) {
        pthread_mutex_lock(&devices[i].mutex);
        devices[i].mute = mute;
        pthread_mutex_unlock(&devices[i].mutex);
    }
    pthread_mutex_unlock(&global_mutex);
}

int alsa_wrapper_get_device_count(void) {
    return device_count;
}