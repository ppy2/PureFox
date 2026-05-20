#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <dlfcn.h>
#include <unistd.h>
#include <string.h>

/*
 * Exit code conventions:
 *   1  - unexpected assert (real bug, do not restart)
 *   42 - known libqobuz_connect stream manager assert (restart expected)
 */
#define EXIT_CODE_ASSERT_KNOWN 42

/*
 * The known intermittent assertion in libqobuz_connect:
 *   qbz_audio_stream_manager_get_audio_stream_properties: Assertion `stream != NULL' failed.
 *   (audio_stream_manager.c:403, state transition Created -> Info Retrieved)
 *
 * Root cause: internal race in libqobuz_connect stream state machine —
 * the MediaEngine transitions a stream to Info Retrieved before it is fully
 * registered in the audio_stream_manager. Not fixable without library source.
 */
#define KNOWN_ASSERT_FILE "audio_stream_manager.c"
#define KNOWN_ASSERT_FUNC "qbz_audio_stream_manager_get_audio_stream_properties"

void abort(void)
{
    static const char msg[] =
        "[assert_hook] abort() intercepted — exiting cleanly\n";
    (void)write(STDERR_FILENO, msg, sizeof(msg) - 1);
    _exit(EXIT_CODE_ASSERT_KNOWN);
}

void __assert_fail(
    const char *assertion,
    const char *file,
    unsigned int line,
    const char *function)
{
    char buf[1024];
    int len = snprintf(buf, sizeof(buf),
        "[assert_hook] Assertion failed: %s\n"
        "  file:     %s:%u\n"
        "  function: %s\n",
        assertion,
        file     ? file     : "?",
        line,
        function ? function : "?");
    (void)write(STDERR_FILENO, buf, (size_t)len);

    /* Determine whether this is the known libqobuz_connect stream manager bug */
    int is_known =
        (file     && strstr(file,     KNOWN_ASSERT_FILE) != NULL) ||
        (function && strstr(function, KNOWN_ASSERT_FUNC) != NULL);

    if (is_known) {
        static const char known_msg[] =
            "[assert_hook] Known libqobuz_connect stream-manager assertion — "
            "signalling launcher to restart (exit 42)\n";
        (void)write(STDERR_FILENO, known_msg, sizeof(known_msg) - 1);
        _exit(EXIT_CODE_ASSERT_KNOWN);
    }

    /* Unknown assertion — treat as a real bug, do not restart */
    static const char unknown_msg[] =
        "[assert_hook] Unknown assertion — not restarting\n";
    (void)write(STDERR_FILENO, unknown_msg, sizeof(unknown_msg) - 1);
    _exit(1);
}
