<?php
$path = getenv('PATH');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asoundConfPath = '/etc/asound.conf';
    $targetCard = $_POST['card']; // 'usb' или 'i2s'

    if (file_exists($asoundConfPath)) {
        $content = file_get_contents($asoundConfPath);

        // Заменяем карту в зависимости от выбора
        if ($targetCard === 'usb') {
            $newContent = str_replace('card 0', 'card 1', $content);
        } else {
            $newContent = str_replace('card 1', 'card 0', $content);
        }

        // Запись изменений обратно в файл
        file_put_contents($asoundConfPath, $newContent);

        // Перезапуск плеера для применения изменений
        shell_exec('/usr/bin/sudo /etc/init.d/S95* restart');

        echo $message;
    } else {
        echo 'Файл /etc/asound.conf не найден!';
    }
}
?>
