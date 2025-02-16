<?php
// Путь к конфигурационному файлу
$config_file = '/etc/i2s.conf';

/**
 * Функция для чтения текущего режима (MODE) и MCLK из конфигурационного файла.
 */
function readConfig($filePath) {
    $result = ['mode' => '', 'mclk' => ''];
    if (file_exists($filePath)) {
        $contents = file_get_contents($filePath);
        if (preg_match('/^MODE=(\w+)/m', $contents, $matches)) {
            $result['mode'] = strtolower($matches[1]);
        }
        if (preg_match('/^MCLK=(\d+)/m', $contents, $matches)) {
            $result['mclk'] = $matches[1];
        }
    }
    return $result;
}

/**
 * Функция для обновления (или добавления) строки MCLK в файле.
 */
function updateMclk($filePath, $newValue) {
    $contents = file_get_contents($filePath);
    if (preg_match('/^MCLK=\d+$/m', $contents)) {
        // Заменяем существующую строку
        $contents = preg_replace('/^MCLK=\d+$/m', "MCLK=" . $newValue, $contents);
    } else {
        // Добавляем строку в конец
        $contents .= PHP_EOL . "MCLK=" . $newValue;
    }
    file_put_contents($filePath, $contents);
}

// --- Логика обработки формы (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Если нажата кнопка для изменения режима (PLL/EXT)
    if (isset($_POST['mode'])) {
        $mode = $_POST['mode'];
        if ($mode === 'pll' || $mode === 'ext') {
            $script = ($mode === 'pll') ? '/opt/2pll.sh' : '/opt/2ext.sh';
            // Вызов скрипта (блокирующий на время выполнения ~1.32 c)
            // Если нужно в фоне, добавьте `nohup` и `&`, но тогда вы не увидите реальную блокировку
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
            // $output и $returnVar можно логировать при необходимости
        }
    }

    // Если нажата кнопка для изменения MCLK
    if (isset($_POST['mclk'])) {
        $mclk = $_POST['mclk'];
        if ($mclk === '512' || $mclk === '1024') {
            if (!file_exists($config_file)) {
                file_put_contents($config_file, "MCLK=" . $mclk . "\n");
            } else {
                updateMclk($config_file, $mclk);
            }
        }
    }

    // После выполнения действий перенаправляем (PRG)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- GET-запрос: читаем текущее состояние ---
$config = readConfig($config_file);
$current_mode = $config['mode'];
$current_mclk = $config['mclk'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки режима и MCLK</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #1a1a1a;
            font-family: Arial, sans-serif;
            color: #e0e0e0;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
        }
        h1 {
            text-align: center;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
        .group {
            margin-bottom: 30px;
            text-align: center;
        }
        .group h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #e0e0e0;
        }
        .group .row {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .btn-custom {
            padding: 10px 20px;
            font-size: 16px;
            color: #e0e0e0;
            background-color: #3d3d3d;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .btn-custom:hover {
            background-color: #4d4d4d;
        }
        .btn-custom.active {
            background-color: #007bff;
            color: #fff;
        }

        /* Оверлей - затемнение всего экрана */
        #overlay {
            display: none; /* По умолчанию скрыт */
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
        }
        /* Спиннер по центру оверлея */
        .spinner-wrapper {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        /* Пример простого «часика» (CSS-анимация) */
        .spinner {
            margin: 0 auto;
            width: 60px; 
            height: 60px;
            border: 6px solid #ccc;
            border-top: 6px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Функция для показа оверлея
        function showOverlay() {
            document.getElementById('overlay').style.display = 'block';
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Настройки</h1>
    <!-- Оверлей для затемнения + спиннер -->
    <div id="overlay">
        <div class="spinner-wrapper">
            <div class="spinner"></div>
        </div>
    </div>

    <!-- При отправке формы запускаем showOverlay() -->
    <form method="post" onsubmit="showOverlay()">
        <div class="group">
            <h2>Режим</h2>
            <div class="row">
                <button type="submit" name="mode" value="pll"
                    class="btn-custom <?php if ($current_mode === 'pll') echo 'active'; ?>">
                    PLL
                </button>
                <button type="submit" name="mode" value="ext"
                    class="btn-custom <?php if ($current_mode === 'ext') echo 'active'; ?>">
                    EXT
                </button>
            </div>
        </div>
        <div class="group">
            <h2>MCLK</h2>
            <div class="row">
                <button type="submit" name="mclk" value="512"
                    class="btn-custom <?php if ($current_mclk === '512') echo 'active'; ?>">
                    512
                </button>
                <button type="submit" name="mclk" value="1024"
                    class="btn-custom <?php if ($current_mclk === '1024') echo 'active'; ?>">
                    1024
                </button>
            </div>
	 </div>
	После изменений обязательно перезагрузите SBC.
    </form>
</div>
</body>
</html>

