<?php
require_once 'config.php';
// Path to configuration file
$config_file = '/etc/i2s.conf';

/**
 * Function for reading the current mode (MODE), MCLK and SUBMODE from configuration file.
 */
function readConfig($filePath) {
    $result = ['mode' => '', 'mclk' => '', 'submode' => ''];
    if (file_exists($filePath)) {
        $contents = file_get_contents($filePath);
        if ($contents !== false) {
            if (preg_match('/^MODE=(\w+)/m', $contents, $matches)) {
                $result['mode'] = strtolower($matches[1]);
            }
            if (preg_match('/^MCLK=(\d+)/m', $contents, $matches)) {
                $result['mclk'] = $matches[1];
            }
            if (preg_match('/^SUBMODE=(\w+)/m', $contents, $matches)) {
                $result['submode'] = strtolower($matches[1]);
            } else {
                $result['submode'] = 'std';
            }
        }
    }
    return $result;
}

// --- Processing AJAX request to get current state ---
if (isset($_GET['action']) && $_GET['action'] === 'getStatus') {
    $config = readConfig($config_file);
    header('Content-Type: application/json');
    echo json_encode($config);
    exit;
}

// --- Processing POST request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If button for changing mode (PLL/EXT) is pressed
    if (isset($_POST['mode'])) {
        $mode = $_POST['mode'];
        if (in_array($mode, ['pll', 'ext'])) {
            $script = ($mode === 'pll') ? '/opt/2pll.sh' : '/opt/2ext.sh';
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }

    // If button for changing SUBMODE (STD/L/R/±L/±R/8CH) is pressed
    if (isset($_POST['submode'])) {
        $submode = $_POST['submode'];
        if (in_array($submode, ['std', 'lr', 'plr', '8ch'])) {
            $script = "/opt/2_$submode.sh";
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }

    // If button for changing MCLK is pressed
    if (isset($_POST['mclk'])) {
        $mclk = $_POST['mclk'];
        if (in_array($mclk, ['512', '1024'])) {
            // Choose script depending on current mode
            $config = readConfig($config_file);
            $mode = $config['mode'];
            
            if ($mode === 'pll') {
                $script = ($mclk === '1024') ? '/opt/2_1024_pll.sh' : '/opt/2_512_pll.sh';
            } else {
                $script = ($mclk === '1024') ? '/opt/2_1024_ext.sh' : '/opt/2_512_ext.sh';
            }
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }
    
    // Reboot processing removed - now using reboot.php

    // After performing actions, redirect (PRG)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- GET request: reading current state ---
$config = readConfig($config_file);
$current_mode = $config['mode'];
$current_mclk = $config['mclk'];
$current_submode = $config['submode'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки режима I2S</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo VERSION; ?>">
    <style>
        .group {
            margin-bottom: 20px;
            text-align: center;
        }
        .group h2 {
            margin-bottom: 8px;
            font-size: 16px;
            color: #e0e0e0;
        }
        .group .row {
            display: flex;
            justify-content: space-around;
            flex-wrap: nowrap;
            gap: 5px;
        }
        .group .row button {
            padding: 5px 10px;
            font-size: 14px;
            margin: 0 2px;
            min-width: 60px;
        }
        .reboot-btn {
            margin-top: 15px;
            text-align: right;
        }
        .reboot-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .warning {
            margin-top: 15px;
            text-align: left;
            color: #e0e0e0;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.4;
        }
        .pulse {
            color: #ff4444;
            animation: pulse 2s infinite ease-in-out;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .status-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #28a745;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .status-indicator.active {
            opacity: 1;
        }
    </style>
    <script>
        function showOverlay() {
            document.querySelector('.spinner-overlay').classList.add('show');
        }
        // Custom confirm dialog
        function customConfirm(message, callback) {
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('custom-confirm').classList.add('show');
            
            // Button handlers
            document.getElementById('confirm-yes').onclick = function() {
                document.getElementById('custom-confirm').classList.remove('show');
                callback(true);
            };
            
            document.getElementById('confirm-no').onclick = function() {
                document.getElementById('custom-confirm').classList.remove('show');
                callback(false);
            };
        }

        function confirmReboot(event) {
            event.preventDefault();
            customConfirm("Вы уверены, что хотите перезагрузить устройство?", function(confirmed) {
                if (confirmed) {
                    document.querySelector('.spinner-overlay').classList.add('show');
                    document.querySelector('.spinner-text').textContent = 'Перезагрузка...';
                    
                    fetch('reboot.php', {
                        method: 'POST'
                    }).then(() => {
                        // After reboot, wait for connection restoration
                        setTimeout(checkConnection, 3000);
                    }).catch(() => {
                        // If request failed, still wait for restoration
                        setTimeout(checkConnection, 3000);
                    });
                }
            });
            return false;
        }
        
        function checkConnection() {
            fetch('?action=getStatus')
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        setTimeout(checkConnection, 2000);
                    }
                })
                .catch(() => {
                    setTimeout(checkConnection, 2000);
                });
        }
        function checkStatus() {
            fetch('?action=getStatus')
                .then(response => response.json())
                .then(data => {
                    const indicator = document.getElementById('statusIndicator');
                    indicator.classList.add('active');
                    setTimeout(() => indicator.classList.remove('active'), 300);
                    
                    updateButtonState('pll-btn', data.mode === 'pll');
                    updateButtonState('ext-btn', data.mode === 'ext');
                    
                    updateButtonState('std-btn', data.submode === 'std');
                    updateButtonState('lr-btn', data.submode === 'lr');
                    updateButtonState('plr-btn', data.submode === 'plr');
                    updateButtonState('8ch-btn', data.submode === '8ch');
                    
                    updateButtonState('mclk-512-btn', data.mclk === '512');
                    updateButtonState('mclk-1024-btn', data.mclk === '1024');
                })
                .catch(error => console.error('Error getting status:', error));
        }
        function updateButtonState(btnId, isActive) {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.classList.toggle('active', isActive);
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            checkStatus();
            setInterval(checkStatus, 5000);
        });
    </script>
</head>
<body>
    <!-- Updated spinner -->
    <div class="spinner-overlay">
	<div class="spinner-container">
        <div class="spinner"></div>
        <div class="spinner-text">Загрузка...</div>
        </div>
    </div>
    <div id="statusIndicator" class="status-indicator"></div>
    <div class="container">
        <div class="header">
            <a href="index.php" class="home-button" title="Home">
                <img src="assets/img/home.svg" class="settings-icon" alt="Home">
            </a>
            <h1>Настройки I2S</h1>
        </div>
        <form method="post" onsubmit="showOverlay()">
            <div class="group">
                <h2>Основной режим</h2>
                <div class="row">
                    <button type="submit" name="mode" value="pll" id="pll-btn"
                        class="btn-custom <?php if ($current_mode === 'pll') echo 'active'; ?>">PLL</button>
                    <button type="submit" name="mode" value="ext" id="ext-btn"
                        class="btn-custom <?php if ($current_mode === 'ext') echo 'active'; ?>">EXT</button>
                </div>
            </div>
            <div class="group">
                <h2>Вариант выхода</h2>
                <div class="row">
                    <button type="submit" name="submode" value="std" id="std-btn"
                        class="btn-custom <?php if ($current_submode === 'std') echo 'active'; ?>">STD</button>
                    <button type="submit" name="submode" value="lr" id="lr-btn"
                        class="btn-custom <?php if ($current_submode === 'lr') echo 'active'; ?>">L/R</button>
                    <button type="submit" name="submode" value="plr" id="plr-btn"
                        class="btn-custom <?php if ($current_submode === 'plr') echo 'active'; ?>">±L/±R</button>
                    <button type="submit" name="submode" value="8ch" id="8ch-btn"
                        class="btn-custom <?php if ($current_submode === '8ch') echo 'active'; ?>">8CH</button>
                </div>
            </div>
            <div class="group">
                <h2>MCLK</h2>
                <div class="row">
                    <button type="submit" name="mclk" value="512" id="mclk-512-btn"
                        class="btn-custom <?php if ($current_mclk === '512') echo 'active'; ?>">512</button>
                    <button type="submit" name="mclk" value="1024" id="mclk-1024-btn"
                        class="btn-custom <?php if ($current_mclk === '1024') echo 'active'; ?>">1024</button>
                </div>
            </div>
            <div class="warning">
                <span class="pulse">Внимание!</span> <br>
                Выход MCLK в режимах PLL и EXT имеет разные настройки (OUTPUT/INPUT). </br>
                После изменения настроек I2S необходима перезагрузка системы для вступления в силу.
            </div>
        </form>
        <div class="reboot-btn">
            <a href="#" onclick="confirmReboot(event)" class="reboot-link" title="Reboot">
                <img src="assets/img/reboot.svg" class="settings-icon reboot-icon" alt="Reboot">
            </a>
        </div>
    </div>

    <!-- Custom confirm dialog -->
    <div id="custom-confirm" class="confirm-overlay">
        <div class="confirm-content">
            <div id="confirm-message" class="confirm-message"></div>
            <div class="confirm-buttons">
                <button id="confirm-yes" class="confirm-btn confirm-btn-yes">Да</button>
                <button id="confirm-no" class="confirm-btn confirm-btn-no">Отмена</button>
            </div>
        </div>
    </div>
</body>
</html>
