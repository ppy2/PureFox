<?php
// stop.php
function connectToMPD() {
    $fp = fsockopen("localhost", 6600, $errno, $errstr, 5);
    if (!$fp) {
        die("Ошибка подключения к MPD: $errstr ($errno)");
    }
    fgets($fp);
    return $fp;
}

$fp = connectToMPD();
fwrite($fp, "stop\n");
while (!feof($fp)) {
    $line = fgets($fp);
    if (strpos($line, "OK") === 0 || strpos($line, "ACK") === 0) break;
}
fclose($fp);
// Удаляем информацию о текущей станции
if (file_exists('current_station.json')) {
    unlink('current_station.json');
}
header('Location: radio.php');
exit;
?>

