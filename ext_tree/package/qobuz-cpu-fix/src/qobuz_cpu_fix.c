#define _GNU_SOURCE
#include <dlfcn.h>
#include <time.h>
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>

// Счетчики для мониторинга
static long time_calls = 0;
static int show_stats = 0;

// Кэш для clock_gettime
static struct timespec cached_time = {0, 0};
static long counter = 0;

// Перехватываем обычный clock_gettime (не 64-битную версию)
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
    
    // Кэшируем только CLOCK_MONOTONIC
    if (clk_id != CLOCK_MONOTONIC && clk_id != CLOCK_MONOTONIC_COARSE) {
        return real_clock_gettime(clk_id, tp);
    }
    
    time_calls++;
    counter++;
    
    // Обновляем кэш каждые 10 вызовов
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

// Проверяем, есть ли clock_gettime64 и перехватываем если есть
__attribute__((weak))
int clock_gettime64(clockid_t clk_id, struct timespec *tp) {
    return clock_gettime(clk_id, tp);
}

__attribute__((constructor))
void init() {
    char *env = getenv("CPU_FIX_STATS");
    show_stats = (env != NULL);
    fprintf(stderr, "[CPU_FIX] V2 loaded (clock_gettime). Stats: %s\n", show_stats ? "ON" : "OFF");
}