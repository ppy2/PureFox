<?php
if (!isset($_GET['category'])) {
    header('Location: index.php');
    exit;
}
$categoryToDelete = $_GET['category'];
$jsonFile = 'radio.json';
if (!file_exists($jsonFile)) {
    die("Файл с данными не найден.");
}
$data = json_decode(file_get_contents($jsonFile), true);
$stations = $data['stations'];
$newStations = array();
foreach ($stations as $station) {
    if ($station['category'] === $categoryToDelete) {
        if (!empty($station['logo']) && file_exists("logos/" . $station['logo'])) {
            unlink("logos/" . $station['logo']);
        }
    } else {
        $newStations[] = $station;
    }
}
$data['stations'] = $newStations;
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: index.php');
exit;
?>

