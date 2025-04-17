<?php
$path = getenv('PATH');

$services = [
    'naa' => 'networkaudiod',
    'raat' => 'raat_app',
    'mpd' => 'bin/mpd',
    'squeeze2upn' => 'squeeze2upn',
    'aprenderer' => 'ap2renderer',
    'aplayer' => 'aplayer',
    'apscream' => 'apscream',
    'lms' => 'squeezelite',
    'shairport' => 'shairport-sync',
//    'scream' => 'screen_audio',
    'spotify' => 'librespot',
    'tidalconnect' => 'tidalconnect',
];

foreach ($services as $key => $process) {
    $output = shell_exec("/usr/bin/pgrep $process");
    if ($output) {
        echo $key;
        exit;
    }
}
?>
