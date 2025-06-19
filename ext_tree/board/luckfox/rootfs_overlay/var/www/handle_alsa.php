<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetCard = $_POST['card'] ?? '';
    
    // Валидация ввода
    if (!in_array($targetCard, ['usb', 'i2s'])) {
        http_response_code(400);
        die('Неверный параметр card. Допустимые значения: usb, i2s');
    }

    // Проверка существования файла
    $asoundConfPath = '/etc/asound.conf';
    if (!file_exists($asoundConfPath)) {
        http_response_code(404);
        die('Файл /etc/asound.conf не найден');
    }

    // Чтение содержимого с проверкой
    $content = file_get_contents($asoundConfPath);
    if ($content === false || trim($content) === '') {
        http_response_code(500);
        die('Файл пуст или недоступен для чтения');
    }

    // Проверка текущего состояния
    $search = ($targetCard === 'usb') ? 'card 0' : 'card 1';
    if (strpos($content, $search) === false) {
        die("Целевая карта '$search' не найдена в файле");
    }

    // Вызов соответствующего скрипта
    $script = ($targetCard === 'usb') ? '/opt/2_usb.sh' : '/opt/2_i2s.sh';
    if (!file_exists($script)) {
        http_response_code(500);
        die("Скрипт $script не найден");
    }

    // Выполнение скрипта
    $output = [];
    $returnVar = 0;
    exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        http_response_code(500);
        die("Ошибка выполнения скрипта $script: " . implode("\n", $output));
    }

    // Перезапуск сервиса
    $serviceOutput = shell_exec('/usr/bin/sudo /etc/init.d/S95* restart 2>&1');
    echo "Конфигурация успешно обновлена. Вывод: $serviceOutput";
} else {
    http_response_code(405);
    header('Allow: POST');
    echo 'Используйте POST-запрос';
}
?>
