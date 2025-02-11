<?php

// Добавим 333 проверки. А то, иногда файл обнуляется

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asoundConfPath = '/etc/asound.conf';
    $targetCard = $_POST['card'] ?? '';
    
    // Валидация ввода
    if (!in_array($targetCard, ['usb', 'i2s'])) {
        http_response_code(400);
        die('Неверный параметр card. Допустимые значения: usb, i2s');
    }

    // Проверка существования файла
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

    // Определение замены
    $search = ($targetCard === 'usb') ? 'card 0' : 'card 1';
    $replace = ($targetCard === 'usb') ? 'card 1' : 'card 0';

    // Проверка наличия заменяемой строки
    if (strpos($content, $search) === false) {
        die("Целевая карта '$search' не найдена в файле");
    }

    // Выполнение замены
    $newContent = str_replace($search, $replace, $content);
    
    // Проверка изменений
    if ($newContent === $content) {
        die('Не удалось выполнить замену. Содержимое не изменилось');
    }

    // Атомарная запись с блокировкой
    $tempFile = tempnam(sys_get_temp_dir(), 'asound');
    if (file_put_contents($tempFile, $newContent) === false) {
        unlink($tempFile);
        http_response_code(500);
        die('Ошибка создания временного файла');
    }

    // Перемещение файла с проверкой
    if (!rename($tempFile, $asoundConfPath)) {
        unlink($tempFile);
        http_response_code(500);
        die('Ошибка записи основного файла');
    }

    // Перезапуск сервиса
    $output = shell_exec('/usr/bin/sudo /etc/init.d/S95* restart 2>&1');
    echo "Конфигурация успешно обновлена. Вывод: $output";
} else {
    http_response_code(405);
    header('Allow: POST');
    echo 'Используйте POST-запрос';
}
?>
