<?php
// process_station.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_station.html');
    exit;
}

$name = trim($_POST['name']);
$url = trim($_POST['url']);
$category = trim($_POST['category']);

if (empty($name) || empty($url) || empty($category)) {
    header('Location: add_station.html?error=' . urlencode('Пожалуйста, заполните все обязательные поля.'));
    exit;
}

// Обработка загрузки логотипа
$logoFilename = '';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = 'logos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $logoFilename = basename($_FILES['logo']['name']);
    $targetPath = $uploadDir . $logoFilename;
    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
        header('Location: add_station.html?error=' . urlencode('Ошибка загрузки логотипа.'));
        exit;
    }
}

// Чтение существующих данных
$jsonFile = 'radio.json';
if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
    if (!isset($data['stations'])) {
        $data['stations'] = array();
    }
} else {
    $data = array('stations' => array());
}

// Генерация нового уникального ID
$newId = 1;
foreach ($data['stations'] as $station) {
    if ($station['id'] >= $newId) {
        $newId = $station['id'] + 1;
    }
}

// Создание записи о новой станции
$newStation = array(
    'id'       => $newId,
    'name'     => $name,
    'url'      => $url,
    'category' => $category,
    'logo'     => $logoFilename
);

$data['stations'][] = $newStation;

// Сохранение обновлённого JSON с форматированием
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: radio.php');
exit;
?>

