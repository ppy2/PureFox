#define _GNU_SOURCE
#include <dlfcn.h>
#include <time.h>
#include <errno.h>

// Cache for clock_gettime
static struct timespec cached_time = {0, 0};
static long counter = 0;

// Intercept clock_gettime
int clock_gettime(clockid_t clk_id, struct timespec *tp) {
    static int (*real_clock_gettime)(clockid_t, struct timespec *) = NULL;
    
    if (!real_clock_gettime) {
        real_clock_gettime = dlsym(RTLD_NEXT, "clock_gettime");
        if (!real_clock_gettime) {
            errno = ENOSYS;
            return -1;
        }
    }
    
    // Cache only CLOCK_MONOTONIC
    if (clk_id != CLOCK_MONOTONIC && clk_id != CLOCK_MONOTONIC_COARSE) {
        return real_clock_gettime(clk_id, tp);
    }
    
    counter++;
    
    // Update cache every 10 calls
    if (counter >= 10 || cached_time.tv_sec == 0) {
        real_clock_gettime(CLOCK_MONOTONIC, &cached_time);
        counter = 0;
    }
    
    *tp = cached_time;
    return 0;
}

// Intercept clock_gettime64 if available
__attribute__((weak))
int clock_gettime64(clockid_t clk_id, struct timespec *tp) {
    return clock_gettime(clk_id, tp);
}