<?php
// play.php
function connectToMPD() {
    $fp = fsockopen("localhost", 6600, $errno, $errstr, 5);
    if (!$fp) {
        die("Ошибка подключения к MPD: $errstr ($errno)");
    }
    fgets($fp); // читаем приветственное сообщение
    return $fp;
}

if (!isset($_GET['id'])) {
    die("Не указан идентификатор станции.");
}
$id = intval($_GET['id']);
$jsonFile = 'radio.json';
if (!file_exists($jsonFile)) {
    die("Файл с данными не найден.");
}
$data = json_decode(file_get_contents($jsonFile), true);
$station = null;
foreach ($data['stations'] as $s) {
    if ($s['id'] == $id) {
        $station = $s;
        break;
    }
}
if (!$station) {
    die("Станция не найдена.");
}

$fp = connectToMPD();
fwrite($fp, "clear\n");
fwrite($fp, "add " . $station['url'] . "\n");
fwrite($fp, "play\n");
while (!feof($fp)) {
    $line = fgets($fp);
    if (strpos($line, "OK") === 0 || strpos($line, "ACK") === 0) break;
}
fclose($fp);

// Записываем информацию о текущей станции
$currentStation = array(
    'id' => $station['id'],
    'name' => $station['name'],
    'url' => $station['url']
);
file_put_contents('current_station.json', json_encode($currentStation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: index.php');
exit;
?>

