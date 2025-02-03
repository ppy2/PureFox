<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);



function logMessage($message) {
    error_log("[Player Manager] " . $message);
}

function executeCommand($command) {
    logMessage("Executing: $command");
    $output = shell_exec("/usr/bin/sudo $command 2>&1");
    logMessage("Output: " . trim((string)$output));
    return trim((string)$output);
}

$players = [
    'naa' => ['process' => 'networkaudiod', 'script' => 'S95naa'],
    'raat' => ['process' => 'raat_app', 'script' => 'S95roonready'],
//    'upnp-mpd' => ['process' => 'mpd', 'script' => 'S95mpd'],
//    'upnp-aplayer' => ['process' => 'aplayer', 'script' => 'S95upnp-aplayer'],
    'shairport' => ['process' => 'shairport-sync', 'script' => 'S95shairport'],
    'lms' => ['process' => 'squeezelite', 'script' => 'S95squeezelite'],
//    'screen-audio' => ['process' => 'screen_audio', 'script' => 'S95screen-audio'],
    'spotify' => ['process' => 'librespot', 'script' => 'S95spotify'],
//    'tidal' => ['process' => 'tidal', 'script' => 'S95tidal'],
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $player_to_start = $_POST['service'] ?? '';
    if (!isset($players[$player_to_start])) {
        throw new Exception("Invalid player: $player_to_start");
    }

    logMessage("Request to start player: $player_to_start");

    // Проверка наличия скрипта
    $script_path = "/etc/rc.pure/{$players[$player_to_start]['script']}";
    if (!file_exists($script_path)) {
        throw new Exception("Player script not found: $script_path");
    }

    // Остановить все текущие плееры
    executeCommand("/etc/init.d/S95* stop");

    // Удаляем все S95* из /etc/init.d/
    executeCommand("/bin/rm -f /etc/init.d/S95*");

    // Создаем симлинк
    $target_link = "/etc/init.d/{$players[$player_to_start]['script']}";
    executeCommand("/bin/ln -s '$script_path' '$target_link'");
    
    // Запускаем плеер
    logMessage("Starting player: $player_to_start");
    $start_output = executeCommand("$target_link start");
    sleep(2);

    // Проверяем запуск
    $check = executeCommand("/usr/bin/pgrep -x {$players[$player_to_start]['process']}");
    if (empty($check)) {
        throw new Exception("Failed to start player: $player_to_start. Output: $start_output");
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Successfully started $player_to_start"
    ]);

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
