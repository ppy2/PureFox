<?php
$path = getenv('PATH');

$services = [
    'naa' => 'networkaudiod',
    'raat' => 'raat_app',
    'mpd' => 'bin/mpd',
//    'upnp-aplayer' => 'aplayer',
    'lms' => 'squeezelite',
    'shairport' => 'shairport-sync',
//    'scream' => 'screen_audio',
    'spotify' => 'librespot',
//    'tidal' => 'tidal',
];

foreach ($services as $key => $process) {
    $output = shell_exec("/usr/bin/pgrep $process");
    if ($output) {
        echo $key;
        exit;
    }
}
?>
