<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lock file to prevent concurrent service switches
// Lock removed - allow concurrent switches

function logMessage($message) {
    error_log("[Player Manager] " . $message);
}

function executeCommand($command) {
    logMessage("Executing: $command");
    $output = shell_exec("/usr/bin/sudo $command 2>&1");
    logMessage("Output: " . trim((string)$output));
    return trim((string)$output);
}

function acquireLock($lockfile) {
    global $lock_fp;
    $lock_fp = fopen($lockfile, 'c');
    if (!$lock_fp) {
        throw new Exception("Cannot open lock file");
    }
    
    // Non-blocking exclusive lock
    if (!flock($lock_fp, LOCK_EX | LOCK_NB)) {
        fclose($lock_fp);
        $lock_fp = null;
        throw new Exception("Service switch already in progress, please wait");
    }
    
    logMessage("Lock acquired");
    return $lock_fp;
}

function releaseLock() {
    global $lock_fp;
    if ($lock_fp) {
        flock($lock_fp, LOCK_UN);
        fclose($lock_fp);
        $lock_fp = null;
        logMessage("Lock released");
    }
}

$players = [
    'naa' => ['process' => 'networkaudiod', 'script' => 'S95naa'],
    'raat' => ['process' => 'raat_app', 'script' => 'S95roonready'],
    'mpd' => ['process' => 'mpd', 'script' => 'S95mpd'],
    'aprenderer' => ['process' => 'ap2renderer', 'script' => 'S95aprenderer'],
    'squeeze2upn' => ['process' => 'squeeze2upn', 'script' => 'S95apsq'],
    'aplayer' => ['process' => 'aplayer', 'script' => 'S95aplayer'],
    'apscream' => ['process' => 'apscream', 'script' => 'S95apscream'],
    'shairport' => ['process' => 'shairport-sync', 'script' => 'S95shairport'],
    'lms' => ['process' => 'squeezelite', 'script' => 'S95squeezelite'],
//    'screen-audio' => ['process' => 'screen_audio', 'script' => 'S95screen-audio'],
    'spotify' => ['process' => 'librespot', 'script' => 'S95spotify'],
    'qobuz' => ['process' => 'qobuz-connect', 'script' => 'S95qobuz'],
];

// Tidal Connect - only available if package is installed
if (file_exists('/opt/tidal.sqfs')) {
    $players['tidalconnect'] = ['process' => 'tidalconnect', 'script' => 'S95tidal'];
}

try {
    // If USB-to-I2S mode active, disable it first (uac2_router holds ALSA)
    if (file_exists('/etc/usb_to_i2s.state')) {
        exec('/opt/usb_unlock.sh 2>&1');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $player_to_start = $_POST['service'] ?? '';
    if (!isset($players[$player_to_start])) {
        throw new Exception("Invalid player: $player_to_start");
    }

    logMessage("Request to start player: $player_to_start");

    // No lock - allow concurrent switches

    // Check script existence
    $script_path = "/etc/rc.pure/{$players[$player_to_start]['script']}";
    if (!file_exists($script_path)) {
        // lock removed
        throw new Exception("Player script not found: $script_path");
    }

    // Force kill all known player processes (bypasses missing init scripts)
    executeCommand("killall -9 networkaudiod raat_app mpd ap2renderer aplayer apscream shairport-sync squeezelite librespot qobuz-connect tidalconnect 2>/dev/null");
    executeCommand("killall avahi-publish-service 2>/dev/null");
    executeCommand("ps | grep -E '/tmp/(tidal|qobuz|spotify)' | awk '{print $1}' | xargs kill -9 2>/dev/null");
    // Stop via init scripts (if symlinks exist)
    executeCommand("/etc/init.d/S95* stop >/dev/null 2>&1");

    // Remove all S95* from /etc/init.d/
    sleep(1);
    executeCommand("/bin/rm -f /etc/init.d/S95*");

    // Create symlink
    $target_link = "/etc/init.d/{$players[$player_to_start]['script']}";
    executeCommand("/bin/ln -s '$script_path' '$target_link'");
    
    // Delay to ensure all processes fully terminated
    sleep(2);
    // Start player in background (don't wait - init script backgrounds processes)
    logMessage("Starting player: $player_to_start");
    $start_output = executeCommand("$target_link start >/dev/null 2>&1 &");

    // Send D-Bus signal about service change for instant update
    executeCommand("/opt/dbus_notify ServiceChanged \"$player_to_start\" 2>/dev/null &");

    // Release lock immediately - don't wait for confirmation
    // lock removed

    // Return immediately - UI will confirm via status polling
    echo json_encode([
        'status' => 'success',
        'message' => "Switch to $player_to_start initiated",
        'confirmed' => false  // UI polls status separately
    ]);

} catch (Exception $e) {
    // lock removed
    logMessage("Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
