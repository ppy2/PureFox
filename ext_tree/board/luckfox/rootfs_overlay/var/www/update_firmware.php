<?php
header('Content-Type: text/plain');

$command = "/usr/bin/sudo /opt/update.sh 2>&1";

$output = [];
$return_var = 0;
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Обновление завершено успешно.";
} else {
    echo "Ошибка обновления:\n" . implode("\n", $output);
}
?>

