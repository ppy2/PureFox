<?php
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = intval($_GET['id']);
$jsonFile = 'radio.json';
if (!file_exists($jsonFile)) {
    die("Файл с данными не найден.");
}
$data = json_decode(file_get_contents($jsonFile), true);
$stations = $data['stations'];
$newStations = array();
$found = false;
foreach ($stations as $station) {
    if ($station['id'] == $id) {
        $found = true;
        if (!empty($station['logo']) && file_exists("logos/" . $station['logo'])) {
            unlink("logos/" . $station['logo']);
        }
    } else {
        $newStations[] = $station;
    }
}
if (!$found) {
    header('Location: index.php?error=' . urlencode('Радиостанция не найдена.'));
    exit;
}
$data['stations'] = $newStations;
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: index.php');
exit;
?>

