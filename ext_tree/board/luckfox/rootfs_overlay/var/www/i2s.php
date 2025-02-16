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

// --- Обработка POST-запроса ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Если нажата кнопка для изменения режима (PLL/EXT)
    if (isset($_POST['mode'])) {
        $mode = $_POST['mode'];
        if ($mode === 'pll' || $mode === 'ext') {
            $script = ($mode === 'pll') ? '/opt/2pll.sh' : '/opt/2ext.sh';
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
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

/**
 * Если режим PLL, то принудительно устанавливаем MCLK=1024,
 * если он ещё не установлен.
 */
if ($current_mode === 'pll' && $current_mclk !== '1024') {
    updateMclk($config_file, '1024');
    $current_mclk = '1024';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <!-- Важный тег для корректного масштабирования на мобильных устройствах -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки режима и MCLK</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Оверлей для затемнения экрана */
        #overlay {
            display: none; /* Скрыт по умолчанию */
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
        }
        /* Центрирование спиннера */
        .spinner-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        /* Спиннер: адаптивные размеры */
        .spinner {
            width: 15vw;
            height: 15vw;
            max-width: 80px;
            max-height: 80px;
            min-width: 50px;
            min-height: 50px;
            border: 0.8em solid #ccc;
            border-top: 0.8em solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Для маленьких экранов увеличим спиннер */
        @media (max-width: 480px) {
            .spinner {
                width: 25vw;
                height: 25vw;
                max-width: 100px;
                max-height: 100px;
            }
        }
        /* Горизонтальное расположение кнопок в группах */
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
    </style>
    <script>
        // Функция показа оверлея при отправке формы
        function showOverlay() {
            document.getElementById('overlay').style.display = 'block';
        }
    </script>
</head>
<body>
    <!-- Оверлей с анимированным спиннером -->
    <div id="overlay">
        <div class="spinner-wrapper">
            <div class="spinner"></div>
        </div>
    </div>
    <div class="container">
        <h1>Настройки</h1>
        <!-- Форма с горизонтальным расположением кнопок в группах -->
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
                    <!-- Если режим PLL, делаем кнопки недоступными (disabled) -->
                    <button type="submit" name="mclk" value="512"
                        class="btn-custom <?php if ($current_mclk === '512') echo 'active'; ?>"
                        <?php if ($current_mode === 'pll') echo 'disabled'; ?>>
                        512
                    </button>
                    <button type="submit" name="mclk" value="1024"
                        class="btn-custom <?php if ($current_mclk === '1024') echo 'active'; ?>"
                        <?php if ($current_mode === 'pll') echo 'disabled'; ?>>
                        1024
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>

