<?php
header('Content-Type: text/plain');

// Определяем IP устройства
$ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

echo trim($ip);
?>

