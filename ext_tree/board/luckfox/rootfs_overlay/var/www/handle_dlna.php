<?php
/*
 * handle_dlna.php — DLNA bridge control API
 *
 * Actions:
 *   GET  ?action=status      — bridge status + stream info
 *   POST action=enable       — run dlna_enable.sh
 *   POST action=disable      — run dlna_disable.sh
 *   GET  ?action=discover    — SSDP discovery, returns JSON array of renderers
 *   POST action=setrenderer  — write renderer settings to /etc/dlna_bridge.conf
 *   POST action=push         — SIGUSR1 to dlna_bridge (triggers UPnP push)
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'status';
} else {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    if (!$action && ($raw = file_get_contents('php://input'))) {
        parse_str($raw, $post);
        $action = isset($post['action']) ? trim($post['action']) : '';
    }
}

switch ($action) {
    case 'status':
        handle_status();
        break;
    case 'enable':
        handle_enable();
        break;
    case 'disable':
        handle_disable();
        break;
    case 'discover':
        handle_discover();
        break;
    case 'setrenderer':
        handle_setrenderer();
        break;
    case 'push':
        handle_push();
        break;
    default:
        echo json_encode(['error' => 'unknown action']);
        break;
}

/* ─── Status ─────────────────────────────────────────────────────── */
function handle_status() {
    $state = 'disabled';
    $state_file = '/etc/dlna_bridge.state';
    if (file_exists($state_file)) {
        $state = trim(file_get_contents($state_file));
    }

    $stream_info = ['active' => false, 'rate' => 0, 'bits' => 0, 'channels' => 0, 'clients' => 0];
    $status_file = '/tmp/dlna_bridge_status.json';
    if (file_exists($status_file)) {
        $json = @json_decode(file_get_contents($status_file), true);
        if ($json) $stream_info = $json;
    }

    $conf = read_conf();

    echo json_encode([
        'bridge_state'    => $state,
        'stream'          => $stream_info,
        'renderer_ip'     => $conf['RENDERER_IP'] ?? '',
        'renderer_port'   => (int)($conf['RENDERER_PORT'] ?? 0),
        'stream_port'     => (int)($conf['STREAM_PORT'] ?? 8888),
        'auto_push'       => (bool)(int)($conf['AUTO_PUSH'] ?? 0),
    ]);
}

/* ─── Enable ─────────────────────────────────────────────────────── */
function handle_enable() {
    $out = [];
    $rc  = 0;
    exec('/opt/dlna_enable.sh 2>&1', $out, $rc);
    echo json_encode(['success' => ($rc === 0), 'output' => implode("\n", $out)]);
}

/* ─── Disable ────────────────────────────────────────────────────── */
function handle_disable() {
    $out = [];
    $rc  = 0;
    exec('/opt/dlna_disable.sh 2>&1', $out, $rc);
    echo json_encode(['success' => ($rc === 0), 'output' => implode("\n", $out)]);
}

/* ─── Discover ───────────────────────────────────────────────────── */
function handle_discover() {
    $renderers = ssdp_discover();
    echo json_encode($renderers);
}

function ssdp_discover() {
    if (!function_exists('socket_create')) return [];

    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($sock === false) return [];

    socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_set_nonblock($sock);

    $msearch =
        "M-SEARCH * HTTP/1.1\r\n" .
        "HOST: 239.255.255.250:1900\r\n" .
        "MAN: \"ssdp:discover\"\r\n" .
        "MX: 3\r\n" .
        "ST: urn:schemas-upnp-org:device:MediaRenderer:1\r\n" .
        "\r\n";

    // Send twice for reliability
    @socket_sendto($sock, $msearch, strlen($msearch), 0, '239.255.255.250', 1900);
    usleep(100000);
    @socket_sendto($sock, $msearch, strlen($msearch), 0, '239.255.255.250', 1900);

    $locations = [];
    $deadline  = microtime(true) + 4.5;

    while (($remaining = $deadline - microtime(true)) > 0) {
        $r    = [$sock];
        $w    = null;
        $e    = null;
        $usec = (int)min($remaining * 1e6, 500000); // poll in 500 ms slices
        $ready = @socket_select($r, $w, $e, 0, $usec);
        if ($ready === false) break;   // fatal error
        if ($ready === 0)    continue; // nothing yet — keep looping

        $buf = $from_ip = '';
        $from_port = 0;
        $n = @socket_recvfrom($sock, $buf, 2048, 0, $from_ip, $from_port);
        if ($n > 0 && preg_match('/LOCATION:\s*(\S+)/i', $buf, $m)) {
            $loc = trim($m[1]);
            if (!in_array($loc, $locations)) $locations[] = $loc;
        }
    }
    socket_close($sock);

    $renderers = [];
    foreach ($locations as $loc) {
        $info = fetch_device_description($loc);
        if ($info) $renderers[] = $info;
    }
    return $renderers;
}

function fetch_device_description($url) {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $xml_str = @file_get_contents($url, false, $ctx);
    if (!$xml_str) return null;

    $xml = @simplexml_load_string($xml_str);
    if (!$xml) return null;

    $name = (string)($xml->device->friendlyName ?? 'Unknown');

    /* Find AVTransport control URL */
    $control_url = '';
    $base_url    = '';
    if (isset($xml->device->serviceList->service)) {
        foreach ($xml->device->serviceList->service as $svc) {
            $st = (string)$svc->serviceType;
            if (strpos($st, 'AVTransport') !== false) {
                $control_url = (string)$svc->controlURL;
                break;
            }
        }
    }

    /* Extract host/port from location URL */
    $parts = parse_url($url);
    $host  = $parts['host'] ?? '';
    $port  = $parts['port'] ?? 80;

    /* Make control URL absolute if relative */
    if ($control_url && $control_url[0] === '/') {
        // already relative to host root — good
    } elseif ($control_url && !preg_match('/^https?:\/\//', $control_url)) {
        $base_path = isset($parts['path']) ? dirname($parts['path']) : '';
        $control_url = $base_path . '/' . $control_url;
    }

    if (!$host || !$control_url) return null;

    return [
        'name'        => $name,
        'ip'          => $host,
        'port'        => (int)$port,
        'control_url' => $control_url,
        'location'    => $url,
    ];
}

/* ─── Set renderer ───────────────────────────────────────────────── */
function handle_setrenderer() {
    $ip   = isset($_POST['renderer_ip'])   ? trim($_POST['renderer_ip'])   : '';
    $port = isset($_POST['renderer_port']) ? (int)trim($_POST['renderer_port']) : 0;
    $url  = isset($_POST['control_url'])   ? trim($_POST['control_url'])   : '';

    if (!$ip || !$port || !$url) {
        // Also try JSON body
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        if ($body) {
            $ip   = $body['renderer_ip']   ?? $ip;
            $port = (int)($body['renderer_port'] ?? $port);
            $url  = $body['control_url']   ?? $url;
        }
    }

    $conf = read_conf();
    $conf['RENDERER_IP']          = $ip;
    $conf['RENDERER_PORT']        = $port;
    $conf['RENDERER_CONTROL_URL'] = $url;
    $ok = write_conf($conf);
    echo json_encode(['success' => $ok]);
}

/* ─── Push (SIGUSR1) ─────────────────────────────────────────────── */
function handle_push() {
    $pidfile = '/var/run/dlna_bridge.pid';
    if (!file_exists($pidfile)) {
        echo json_encode(['success' => false, 'error' => 'daemon not running']);
        return;
    }
    $pid = (int)trim(file_get_contents($pidfile));
    if ($pid <= 0) {
        echo json_encode(['success' => false, 'error' => 'invalid pid']);
        return;
    }
    $ok = posix_kill($pid, SIGUSR1);
    echo json_encode(['success' => $ok]);
}

/* ─── Config helpers ─────────────────────────────────────────────── */
function read_conf() {
    $conf = [
        'STREAM_PORT'          => '8888',
        'RENDERER_IP'          => '',
        'RENDERER_PORT'        => '',
        'RENDERER_CONTROL_URL' => '',
        'AUTO_PUSH'            => '0',
    ];
    $file = '/etc/dlna_bridge.conf';
    if (!file_exists($file)) return $conf;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $conf[trim($k)] = trim($v);
    }
    return $conf;
}

function write_conf($conf) {
    $lines = [];
    foreach ($conf as $k => $v) {
        $lines[] = "$k=$v";
    }
    return file_put_contents('/etc/dlna_bridge.conf', implode("\n", $lines) . "\n") !== false;
}
