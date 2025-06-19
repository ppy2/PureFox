<?php
header('Content-Type: application/json; charset=utf-8');

// Самоочищающийся кэш для систем без cron
$cache_file = '/tmp/combined_status_cache';
$cache_ttl = 3; // TTL кэша в секундах
$cleanup_chance = 10; // 10% вероятность очистки старых файлов

// Самоочистка кэша (выполняется случайно, чтобы не нагружать каждый запрос)
if (rand(1, 100) <= $cleanup_chance) {
    $old_files = glob('/tmp/*_cache');
    $now = time();
    foreach ($old_files as $file) {
        if (file_exists($file) && ($now - filemtime($file)) > 3600) { // Удаляем файлы старше часа
            unlink($file);
        }
    }
}

// Проверяем актуальный кэш
if (file_exists($cache_file)) {
    $cache_age = time() - filemtime($cache_file);
    if ($cache_age < $cache_ttl) {
        $cache_content = file_get_contents($cache_file);
        if ($cache_content) {
            echo $cache_content;
            exit;
        }
    }
}

$services = [
    'naa'         => 'networkaudiod',
    'raat'        => 'raat_app',
    'mpd'         => 'mpd', 
    'squeeze2upn' => 'squeeze2upnp',
    'aprenderer'  => 'ap2renderer',
    'aplayer'     => 'aplayer',
    'apscream'    => 'apscream',
    'lms'         => 'squeezelite',
    'shairport'   => 'shairport-sync',
    'spotify'     => 'librespot',
    'qobuz'       => 'qobuz-connect',
    'tidalconnect'=> 'tidalconnect',
];

$status = [
    'active_service' => '',
    'alsa_state' => 'unknown',
    'usb_dac' => false
];

// Одна команда для проверки всех процессов - максимальная экономия
$all_processes = implode('|', array_values($services));
$output = shell_exec("ps -eo comm | grep -E '^($all_processes)$' | head -1");

if (!empty($output)) {
    $found_process = trim($output);
    foreach ($services as $key => $process) {
        if ($found_process === $process) {
            $status['active_service'] = $key;
            break;
        }
    }
}

// Быстрая проверка ALSA через файл конфигурации (без exec)
$asound_conf = '/etc/asound.conf';
if (file_exists($asound_conf)) {
    $content = file_get_contents($asound_conf);
    if (strpos($content, 'card 1') !== false) {
        $status['alsa_state'] = 'usb';
    } elseif (strpos($content, 'card 0') !== false) {
        $status['alsa_state'] = 'i2s';
    }
}

// Проверка USB DAC только когда нужно
if ($status['alsa_state'] === 'usb') {
    // Используем более быстрый способ проверки USB устройств
    $usb_check = file_exists('/sys/class/sound/card1');
    $status['usb_dac'] = $usb_check;
}

// Сохраняем в кэш
$json_response = json_encode($status);
file_put_contents($cache_file, $json_response, LOCK_EX);

echo $json_response;

