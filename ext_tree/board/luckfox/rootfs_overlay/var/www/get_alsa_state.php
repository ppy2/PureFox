<?php
$path = getenv('PATH');
$asoundConfPath = '/etc/asound.conf';

if (file_exists($asoundConfPath)) {
    $content = file_get_contents($asoundConfPath);

    // Проверяем, какая карта используется
    if (strpos($content, 'card 1') !== false) {
        echo 'usb'; // Используется USB (card 1)
    } else {
        echo 'i2s'; // Используется I2S (card 0)
    }
} else {
    echo 'error'; // Файл не найден
}
?>
