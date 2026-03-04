/*
 * dlna_bridge.c — ALSA loopback → HTTP WAV stream → DLNA push daemon
 *
 * Architecture:
 *   - Capture thread: reads hw:Loopback,1,0 into ring buffer
 *   - HTTP server thread: serves WAV stream to multiple clients
 *   - State thread: writes /tmp/dlna_bridge_status.json every 2s
 *   - Main: reads config, handles SIGUSR1 (re-push UPnP), SIGTERM
 */

#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <unistd.h>
#include <pthread.h>
#include <signal.h>
#include <errno.h>
#include <fcntl.h>
#include <netdb.h>
#include <ifaddrs.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <alsa/asoundlib.h>

/* ─── Configuration ─────────────────────────────────────────────────── */
#define CONF_FILE        "/etc/dlna_bridge.conf"
#define STATUS_FILE      "/tmp/dlna_bridge_status.json"
#define CAPTURE_DEVICE   "hw:Loopback,1,0"
#define RING_SIZE        (4 * 1024 * 1024)   /* 4 MB */
#define MAX_CLIENTS      8
#define HTTP_BACKLOG     8

/* ─── Global state ──────────────────────────────────────────────────── */
static volatile int g_running = 1;
static volatile int g_do_push = 0;

/* Ring buffer */
static uint8_t  g_ring[RING_SIZE];
static volatile size_t g_write_pos = 0;   /* monotonic write position */
static pthread_mutex_t g_ring_mutex = PTHREAD_MUTEX_INITIALIZER;
static pthread_cond_t  g_ring_cond  = PTHREAD_COND_INITIALIZER;

/* PCM format discovered on open */
static volatile unsigned int g_rate     = 44100;
static volatile unsigned int g_channels = 2;
static volatile unsigned int g_bits     = 16;
static volatile int          g_active   = 0;

/* Client count */
static volatile int g_clients = 0;
static pthread_mutex_t g_client_mutex = PTHREAD_MUTEX_INITIALIZER;

/* Config */
static int  cfg_stream_port = 8888;
static char cfg_renderer_ip[64]          = "";
static int  cfg_renderer_port            = 0;
static char cfg_renderer_control_url[256]= "";
static int  cfg_auto_push               = 0;

/* ─── Config parser ─────────────────────────────────────────────────── */
static void load_config(void)
{
    FILE *f = fopen(CONF_FILE, "r");
    if (!f) return;

    char line[512];
    while (fgets(line, sizeof(line), f)) {
        char *eq = strchr(line, '=');
        if (!eq) continue;
        *eq = '\0';
        char *key = line;
        char *val = eq + 1;
        /* trim trailing newline */
        size_t vl = strlen(val);
        while (vl > 0 && (val[vl-1] == '\n' || val[vl-1] == '\r'))
            val[--vl] = '\0';

        if (strcmp(key, "STREAM_PORT") == 0)
            cfg_stream_port = atoi(val);
        else if (strcmp(key, "RENDERER_IP") == 0)
            strncpy(cfg_renderer_ip, val, sizeof(cfg_renderer_ip)-1);
        else if (strcmp(key, "RENDERER_PORT") == 0)
            cfg_renderer_port = atoi(val);
        else if (strcmp(key, "RENDERER_CONTROL_URL") == 0)
            strncpy(cfg_renderer_control_url, val, sizeof(cfg_renderer_control_url)-1);
        else if (strcmp(key, "AUTO_PUSH") == 0)
            cfg_auto_push = atoi(val);
    }
    fclose(f);
}

/* ─── Own IP detection ──────────────────────────────────────────────── */
static int get_own_ip(char *buf, size_t bufsz)
{
    struct ifaddrs *ifa_list, *ifa;
    if (getifaddrs(&ifa_list) != 0) return -1;

    int found = 0;
    for (ifa = ifa_list; ifa; ifa = ifa->ifa_next) {
        if (!ifa->ifa_addr) continue;
        if (ifa->ifa_addr->sa_family != AF_INET) continue;
        if (strcmp(ifa->ifa_name, "lo") == 0) continue;
        struct sockaddr_in *sin = (struct sockaddr_in *)ifa->ifa_addr;
        if (inet_ntop(AF_INET, &sin->sin_addr, buf, (socklen_t)bufsz)) {
            found = 1;
            break;
        }
    }
    freeifaddrs(ifa_list);
    return found ? 0 : -1;
}

/* ─── WAV header (streaming variant) ───────────────────────────────── */
static void make_wav_header(uint8_t *hdr, unsigned int rate,
                             unsigned int channels, unsigned int bits)
{
    uint32_t byte_rate  = rate * channels * (bits / 8);
    uint16_t block_align = (uint16_t)(channels * (bits / 8));

    memcpy(hdr +  0, "RIFF", 4);
    /* chunk size = 0xFFFFFFFF (streaming) */
    hdr[ 4]=0xFF; hdr[ 5]=0xFF; hdr[ 6]=0xFF; hdr[ 7]=0xFF;
    memcpy(hdr +  8, "WAVE", 4);
    memcpy(hdr + 12, "fmt ", 4);
    /* subchunk1 size = 16 */
    hdr[16]=16; hdr[17]=0; hdr[18]=0; hdr[19]=0;
    /* PCM = 1 */
    hdr[20]=1; hdr[21]=0;
    /* channels */
    hdr[22]=(uint8_t)(channels & 0xFF);
    hdr[23]=(uint8_t)((channels>>8) & 0xFF);
    /* sample rate */
    hdr[24]=(uint8_t)(rate & 0xFF);
    hdr[25]=(uint8_t)((rate>>8) & 0xFF);
    hdr[26]=(uint8_t)((rate>>16) & 0xFF);
    hdr[27]=(uint8_t)((rate>>24) & 0xFF);
    /* byte rate */
    hdr[28]=(uint8_t)(byte_rate & 0xFF);
    hdr[29]=(uint8_t)((byte_rate>>8) & 0xFF);
    hdr[30]=(uint8_t)((byte_rate>>16) & 0xFF);
    hdr[31]=(uint8_t)((byte_rate>>24) & 0xFF);
    /* block align */
    hdr[32]=(uint8_t)(block_align & 0xFF);
    hdr[33]=(uint8_t)((block_align>>8) & 0xFF);
    /* bits per sample */
    hdr[34]=(uint8_t)(bits & 0xFF);
    hdr[35]=(uint8_t)((bits>>8) & 0xFF);
    memcpy(hdr + 36, "data", 4);
    /* data size = 0xFFFFFFFF (streaming) */
    hdr[40]=0xFF; hdr[41]=0xFF; hdr[42]=0xFF; hdr[43]=0xFF;
}

/* ─── SOAP UPnP push ────────────────────────────────────────────────── */
static int soap_post(const char *ip, int port, const char *control_url,
                     const char *action_ns, const char *soap_action,
                     const char *body)
{
    int sock = socket(AF_INET, SOCK_STREAM, 0);
    if (sock < 0) return -1;

    struct sockaddr_in sa;
    memset(&sa, 0, sizeof(sa));
    sa.sin_family = AF_INET;
    sa.sin_port   = htons((uint16_t)port);
    if (inet_pton(AF_INET, ip, &sa.sin_addr) <= 0) { close(sock); return -1; }

    struct timeval tv = {5, 0};
    setsockopt(sock, SOL_SOCKET, SO_RCVTIMEO, &tv, sizeof(tv));
    setsockopt(sock, SOL_SOCKET, SO_SNDTIMEO, &tv, sizeof(tv));

    if (connect(sock, (struct sockaddr *)&sa, sizeof(sa)) != 0) {
        close(sock); return -1;
    }

    char soap_env[2048];
    int body_len = snprintf(soap_env, sizeof(soap_env),
        "<?xml version=\"1.0\"?>"
        "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\" "
        "s:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">"
        "<s:Body>%s</s:Body></s:Envelope>", body);

    char request[4096];
    int req_len = snprintf(request, sizeof(request),
        "POST %s HTTP/1.1\r\n"
        "Host: %s:%d\r\n"
        "Content-Type: text/xml; charset=\"utf-8\"\r\n"
        "SOAPAction: \"%s#%s\"\r\n"
        "Content-Length: %d\r\n"
        "Connection: close\r\n"
        "\r\n"
        "%s",
        control_url, ip, port, action_ns, soap_action, body_len, soap_env);

    ssize_t sent = send(sock, request, req_len, 0);
    if (sent != req_len) { close(sock); return -1; }

    /* read response (just drain it) */
    char rbuf[1024];
    while (recv(sock, rbuf, sizeof(rbuf), 0) > 0);

    close(sock);
    return 0;
}

static void upnp_push(void)
{
    if (!cfg_renderer_ip[0] || !cfg_renderer_port || !cfg_renderer_control_url[0]) {
        fprintf(stderr, "dlna_bridge: UPnP push skipped — renderer not configured\n");
        return;
    }

    char own_ip[64] = "127.0.0.1";
    get_own_ip(own_ip, sizeof(own_ip));

    char stream_uri[256];
    snprintf(stream_uri, sizeof(stream_uri),
             "http://%s:%d/stream.wav", own_ip, cfg_stream_port);

    fprintf(stderr, "dlna_bridge: pushing stream URI %s to %s:%d%s\n",
            stream_uri, cfg_renderer_ip, cfg_renderer_port, cfg_renderer_control_url);

    /* SetAVTransportURI */
    char body[1024];
    snprintf(body, sizeof(body),
        "<u:SetAVTransportURI xmlns:u=\"urn:schemas-upnp-org:service:AVTransport:1\">"
        "<InstanceID>0</InstanceID>"
        "<CurrentURI>%s</CurrentURI>"
        "<CurrentURIMetaData></CurrentURIMetaData>"
        "</u:SetAVTransportURI>", stream_uri);

    soap_post(cfg_renderer_ip, cfg_renderer_port, cfg_renderer_control_url,
              "urn:schemas-upnp-org:service:AVTransport:1",
              "SetAVTransportURI", body);

    usleep(300000); /* 300ms between calls */

    /* Play */
    snprintf(body, sizeof(body),
        "<u:Play xmlns:u=\"urn:schemas-upnp-org:service:AVTransport:1\">"
        "<InstanceID>0</InstanceID>"
        "<Speed>1</Speed>"
        "</u:Play>");

    soap_post(cfg_renderer_ip, cfg_renderer_port, cfg_renderer_control_url,
              "urn:schemas-upnp-org:service:AVTransport:1",
              "Play", body);
}

/* ─── Capture thread ────────────────────────────────────────────────── */
static void *capture_thread(void *arg)
{
    (void)arg;
    snd_pcm_t *pcm = NULL;

    while (g_running) {
        if (pcm) { snd_pcm_close(pcm); pcm = NULL; }
        g_active = 0;

        int err = snd_pcm_open(&pcm, CAPTURE_DEVICE, SND_PCM_STREAM_CAPTURE, 0);
        if (err < 0) {
            sleep(2);
            continue;
        }

        snd_pcm_hw_params_t *hw;
        snd_pcm_hw_params_alloca(&hw);
        snd_pcm_hw_params_any(pcm, hw);

        /* Try to match whatever is configured */
        snd_pcm_hw_params_set_access(pcm, hw, SND_PCM_ACCESS_RW_INTERLEAVED);

        /* Prefer S32_LE, fall back to S16_LE */
        unsigned int bits_local = 32;
        if (snd_pcm_hw_params_set_format(pcm, hw, SND_PCM_FORMAT_S32_LE) < 0) {
            snd_pcm_hw_params_set_format(pcm, hw, SND_PCM_FORMAT_S16_LE);
            bits_local = 16;
        }

        unsigned int rate_local = 0;
        snd_pcm_hw_params_get_rate(hw, &rate_local, NULL);
        if (!rate_local) rate_local = 44100;
        snd_pcm_hw_params_set_rate_near(pcm, hw, &rate_local, NULL);

        unsigned int ch_local = 2;
        snd_pcm_hw_params_set_channels_near(pcm, hw, &ch_local);

        snd_pcm_uframes_t period = 1024;
        snd_pcm_hw_params_set_period_size_near(pcm, hw, &period, NULL);

        snd_pcm_uframes_t buf_frames = period * 8;
        snd_pcm_hw_params_set_buffer_size_near(pcm, hw, &buf_frames);

        if (snd_pcm_hw_params(pcm, hw) < 0) { sleep(2); continue; }
        if (snd_pcm_prepare(pcm) < 0) { sleep(2); continue; }

        g_rate     = rate_local;
        g_channels = ch_local;
        g_bits     = bits_local;
        g_active   = 1;

        size_t frame_bytes = ch_local * (bits_local / 8);
        uint8_t *frame_buf = malloc(period * frame_bytes);
        if (!frame_buf) { sleep(1); continue; }

        while (g_running) {
            snd_pcm_sframes_t n = snd_pcm_readi(pcm, frame_buf, period);
            if (n == -EPIPE) { snd_pcm_prepare(pcm); continue; }
            if (n < 0) break;

            size_t nbytes = (size_t)n * frame_bytes;
            pthread_mutex_lock(&g_ring_mutex);
            for (size_t i = 0; i < nbytes; i++) {
                g_ring[(g_write_pos + i) % RING_SIZE] = frame_buf[i];
            }
            g_write_pos += nbytes;
            pthread_cond_broadcast(&g_ring_cond);
            pthread_mutex_unlock(&g_ring_mutex);
        }
        free(frame_buf);
        g_active = 0;
    }

    if (pcm) snd_pcm_close(pcm);
    return NULL;
}

/* ─── HTTP client handler ───────────────────────────────────────────── */
struct client_state {
    int fd;
    size_t read_pos; /* how far this client has consumed (monotonic) */
};

static void *http_client_thread(void *arg)
{
    struct client_state *cs = (struct client_state *)arg;
    int fd = cs->fd;

    /* Read HTTP request (drain until blank line) */
    char rbuf[2048];
    ssize_t total = 0;
    while (total < (ssize_t)sizeof(rbuf)-1) {
        ssize_t r = recv(fd, rbuf + total, sizeof(rbuf)-1-(size_t)total, 0);
        if (r <= 0) break;
        total += r;
        rbuf[total] = '\0';
        if (strstr(rbuf, "\r\n\r\n") || strstr(rbuf, "\n\n")) break;
    }

    /* Send HTTP headers */
    char hdr_buf[512];
    int hlen = snprintf(hdr_buf, sizeof(hdr_buf),
        "HTTP/1.1 200 OK\r\n"
        "Content-Type: audio/wav\r\n"
        "Transfer-Encoding: identity\r\n"
        "Cache-Control: no-cache\r\n"
        "Connection: close\r\n"
        "\r\n");
    send(fd, hdr_buf, (size_t)hlen, MSG_NOSIGNAL);

    /* Send WAV header */
    uint8_t wav_hdr[44];
    make_wav_header(wav_hdr, g_rate, g_channels, g_bits);
    send(fd, wav_hdr, sizeof(wav_hdr), MSG_NOSIGNAL);

    /* Wait for capture to start if not already active */
    int waited = 0;
    while (!g_active && g_running && waited < 50) {
        usleep(100000);
        waited++;
    }

    pthread_mutex_lock(&g_client_mutex);
    g_clients++;
    pthread_mutex_unlock(&g_client_mutex);

    /* Stream PCM data from ring buffer */
    cs->read_pos = (g_write_pos > RING_SIZE) ? (g_write_pos - RING_SIZE) : 0;

    uint8_t send_buf[4096];
    while (g_running) {
        pthread_mutex_lock(&g_ring_mutex);
        while (g_write_pos == cs->read_pos && g_running) {
            struct timespec ts;
            clock_gettime(CLOCK_REALTIME, &ts);
            ts.tv_sec += 2;
            pthread_cond_timedwait(&g_ring_cond, &g_ring_mutex, &ts);
        }

        size_t avail = g_write_pos - cs->read_pos;
        if (avail > RING_SIZE) {
            /* Overrun: jump to current write position minus half ring */
            cs->read_pos = g_write_pos - RING_SIZE / 2;
            avail = RING_SIZE / 2;
        }
        pthread_mutex_unlock(&g_ring_mutex);

        if (!g_running) break;
        if (avail == 0) continue;

        size_t chunk = avail < sizeof(send_buf) ? avail : sizeof(send_buf);
        for (size_t i = 0; i < chunk; i++) {
            send_buf[i] = g_ring[(cs->read_pos + i) % RING_SIZE];
        }

        ssize_t sent = send(fd, send_buf, chunk, MSG_NOSIGNAL);
        if (sent <= 0) break;
        cs->read_pos += (size_t)sent;
    }

    pthread_mutex_lock(&g_client_mutex);
    g_clients--;
    pthread_mutex_unlock(&g_client_mutex);

    close(fd);
    free(cs);
    return NULL;
}

/* ─── HTTP server thread ────────────────────────────────────────────── */
static void *http_server_thread(void *arg)
{
    (void)arg;
    int srv = socket(AF_INET, SOCK_STREAM, 0);
    if (srv < 0) { perror("socket"); return NULL; }

    int opt = 1;
    setsockopt(srv, SOL_SOCKET, SO_REUSEADDR, &opt, sizeof(opt));

    struct sockaddr_in sa;
    memset(&sa, 0, sizeof(sa));
    sa.sin_family      = AF_INET;
    sa.sin_addr.s_addr = INADDR_ANY;
    sa.sin_port        = htons((uint16_t)cfg_stream_port);

    if (bind(srv, (struct sockaddr *)&sa, sizeof(sa)) < 0) {
        perror("bind"); close(srv); return NULL;
    }
    if (listen(srv, HTTP_BACKLOG) < 0) {
        perror("listen"); close(srv); return NULL;
    }

    fprintf(stderr, "dlna_bridge: HTTP server listening on port %d\n", cfg_stream_port);

    while (g_running) {
        fd_set fds;
        FD_ZERO(&fds);
        FD_SET(srv, &fds);
        struct timeval tv = {1, 0};
        if (select(srv + 1, &fds, NULL, NULL, &tv) <= 0) continue;

        struct sockaddr_in ca;
        socklen_t cal = sizeof(ca);
        int cfd = accept(srv, (struct sockaddr *)&ca, &cal);
        if (cfd < 0) continue;

        if (g_clients >= MAX_CLIENTS) { close(cfd); continue; }

        struct client_state *cs = malloc(sizeof(*cs));
        if (!cs) { close(cfd); continue; }
        cs->fd = cfd;
        cs->read_pos = 0;

        pthread_t tid;
        pthread_attr_t attr;
        pthread_attr_init(&attr);
        pthread_attr_setdetachstate(&attr, PTHREAD_CREATE_DETACHED);
        if (pthread_create(&tid, &attr, http_client_thread, cs) != 0) {
            close(cfd); free(cs);
        }
        pthread_attr_destroy(&attr);
    }

    close(srv);
    return NULL;
}

/* ─── State thread ──────────────────────────────────────────────────── */
static void *state_thread(void *arg)
{
    (void)arg;
    while (g_running) {
        FILE *f = fopen(STATUS_FILE ".tmp", "w");
        if (f) {
            fprintf(f,
                "{\"active\":%s,\"rate\":%u,\"bits\":%u,\"channels\":%u,\"clients\":%d}\n",
                g_active ? "true" : "false",
                g_rate, g_bits, g_channels, g_clients);
            fclose(f);
            rename(STATUS_FILE ".tmp", STATUS_FILE);
        }
        sleep(2);
    }
    return NULL;
}

/* ─── Signal handlers ───────────────────────────────────────────────── */
static void sig_term(int s) { (void)s; g_running = 0; }
static void sig_usr1(int s) { (void)s; g_do_push = 1; }

/* ─── Main ──────────────────────────────────────────────────────────── */
int main(void)
{
    load_config();

    signal(SIGTERM, sig_term);
    signal(SIGINT,  sig_term);
    signal(SIGUSR1, sig_usr1);
    signal(SIGPIPE, SIG_IGN);

    pthread_t t_cap, t_http, t_state;
    pthread_create(&t_cap,   NULL, capture_thread,     NULL);
    pthread_create(&t_http,  NULL, http_server_thread,  NULL);
    pthread_create(&t_state, NULL, state_thread,         NULL);

    if (cfg_auto_push) {
        sleep(3); /* wait for HTTP server to be ready */
        upnp_push();
    }

    while (g_running) {
        if (g_do_push) {
            g_do_push = 0;
            load_config(); /* re-read config in case renderer changed */
            upnp_push();
        }
        sleep(1);
    }

    pthread_join(t_cap,   NULL);
    pthread_join(t_http,  NULL);
    pthread_join(t_state, NULL);

    unlink(STATUS_FILE);
    return 0;
}
