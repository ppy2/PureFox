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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-lang="i2s_title">I2S Settings</title>
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
            font-size: 20px;
            font-weight: bold;
            display: block;
            text-align: center;
            margin-bottom: 10px;
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
            customConfirm("Are you sure you want to reboot the device?", function(confirmed) {
                if (confirmed) {
                    document.querySelector('.spinner-overlay').classList.add('show');
                    document.querySelector('.spinner-text').textContent = 'Rebooting...';
                    
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
    </script>
</head>
<body>
    <!-- Updated spinner -->
    <div class="spinner-overlay">
	<div class="spinner-container">
        <div class="spinner"></div>
        <div class="spinner-text">Loading...</div>
        </div>
    </div>
    <div id="statusIndicator" class="status-indicator"></div>
    <div class="container">
        <div class="header">
            <a href="index.php" class="home-button" title="Home">
                <img src="assets/img/home.svg" class="settings-icon" alt="Home">
            </a>
            <h1 data-lang="i2s_title">I2S Settings</h1>
        </div>
        
        <form method="post" onsubmit="showOverlay()">
            <div class="group">
                <h2 data-lang="mode_title">Main Mode</h2>
                <div class="row">
                    <button type="submit" name="mode" value="pll" id="pll-btn"
                        class="btn-custom <?php if ($current_mode === 'pll') echo 'active'; ?>" data-lang="pll_mode">PLL</button>
                    <button type="submit" name="mode" value="ext" id="ext-btn"
                        class="btn-custom <?php if ($current_mode === 'ext') echo 'active'; ?>" data-lang="ext_mode">EXT</button>
                </div>
            </div>
            <div class="group">
                <h2 data-lang="submode_title">Output Mode</h2>
                <div class="row">
                    <button type="submit" name="submode" value="std" id="std-btn"
                        class="btn-custom <?php if ($current_submode === 'std') echo 'active'; ?>" data-lang="std_mode">STD</button>
                    <button type="submit" name="submode" value="lr" id="lr-btn"
                        class="btn-custom <?php if ($current_submode === 'lr') echo 'active'; ?>" data-lang="lr_mode">L/R</button>
                    <button type="submit" name="submode" value="plr" id="plr-btn"
                        class="btn-custom <?php if ($current_submode === 'plr') echo 'active'; ?>" data-lang="plr_mode">±L/±R</button>
                    <button type="submit" name="submode" value="8ch" id="8ch-btn"
                        class="btn-custom <?php if ($current_submode === '8ch') echo 'active'; ?>" data-lang="8ch_mode">8CH</button>
                </div>
            </div>
            <div class="group">
                <h2 data-lang="mclk_title">MCLK</h2>
                <div class="row">
                    <button type="submit" name="mclk" value="512" id="mclk-512-btn"
                        class="btn-custom <?php if ($current_mclk === '512') echo 'active'; ?>">512</button>
                    <button type="submit" name="mclk" value="1024" id="mclk-1024-btn"
                        class="btn-custom <?php if ($current_mclk === '1024') echo 'active'; ?>">1024</button>
                </div>
            </div>
            <div class="warning">
                <span class="pulse" data-lang="warning_attention">Warning!</span> <br>
                <span data-lang="warning_text1">MCLK output has different settings in PLL and EXT modes (OUTPUT/INPUT).</span> </br>
                <span data-lang="warning_text2">System reboot is required after changing I2S settings to apply them.</span>
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
                <button id="confirm-yes" class="confirm-btn confirm-btn-yes" data-lang="yes_btn">Yes</button>
                <button id="confirm-no" class="confirm-btn confirm-btn-no" data-lang="cancel_btn">Cancel</button>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script>
        // Only load translations, no button state management
        $(document).ready(function() {
            // Simple language detection without app.js interference
            function detectLanguage() {
                const lang = navigator.language || navigator.userLanguage;
                if (lang.startsWith('ru')) return 'ru';
                if (lang.startsWith('de')) return 'de';
                if (lang.startsWith('fr')) return 'fr';
                if (lang.startsWith('zh')) return 'zh';
                return 'en';
            }
            
            const translations = {
                'ru': {
                    'i2s_title': 'Настройки I2S',
                    'mode_title': 'Основной режим',
                    'pll_mode': 'PLL',
                    'ext_mode': 'EXT',
                    'submode_title': 'Вариант выхода',
                    'std_mode': 'STD',
                    'lr_mode': 'L/R',
                    'plr_mode': '±L/±R',
                    '8ch_mode': '8CH',
                    'mclk_title': 'MCLK',
                    'warning_attention': 'Внимание!',
                    'warning_text1': 'Выход MCLK в режимах PLL и EXT имеет разные настройки (OUTPUT/INPUT).',
                    'warning_text2': 'После изменения настроек I2S необходима перезагрузка системы для вступления в силу.',
                    'yes_btn': 'Да',
                    'cancel_btn': 'Отмена'
                },
                'en': {
                    'i2s_title': 'I2S Settings',
                    'mode_title': 'Main Mode',
                    'pll_mode': 'PLL',
                    'ext_mode': 'EXT',
                    'submode_title': 'Output Mode',
                    'std_mode': 'STD',
                    'lr_mode': 'L/R',
                    'plr_mode': '±L/±R',
                    '8ch_mode': '8CH',
                    'mclk_title': 'MCLK',
                    'warning_attention': 'Warning!',
                    'warning_text1': 'MCLK output has different settings in PLL and EXT modes (OUTPUT/INPUT).',
                    'warning_text2': 'System reboot is required after changing I2S settings to apply them.',
                    'yes_btn': 'Yes',
                    'cancel_btn': 'Cancel'
                },
                'de': {
                    'i2s_title': 'I2S Einstellungen',
                    'mode_title': 'Hauptmodus',
                    'pll_mode': 'PLL',
                    'ext_mode': 'EXT',
                    'submode_title': 'Ausgangsmodus',
                    'std_mode': 'STD',
                    'lr_mode': 'L/R',
                    'plr_mode': '±L/±R',
                    '8ch_mode': '8CH',
                    'mclk_title': 'MCLK',
                    'warning_attention': 'Achtung!',
                    'warning_text1': 'MCLK-Ausgang hat unterschiedliche Einstellungen in PLL- und EXT-Modi (OUTPUT/INPUT).',
                    'warning_text2': 'Systemneustart ist erforderlich, nachdem I2S-Einstellungen geändert wurden.',
                    'yes_btn': 'Ja',
                    'cancel_btn': 'Abbrechen'
                },
                'fr': {
                    'i2s_title': 'Paramètres I2S',
                    'mode_title': 'Mode principal',
                    'pll_mode': 'PLL',
                    'ext_mode': 'EXT',
                    'submode_title': 'Mode de sortie',
                    'std_mode': 'STD',
                    'lr_mode': 'L/R',
                    'plr_mode': '±L/±R',
                    '8ch_mode': '8CH',
                    'mclk_title': 'MCLK',
                    'warning_attention': 'Attention!',
                    'warning_text1': 'La sortie MCLK a des paramètres différents en modes PLL et EXT (OUTPUT/INPUT).',
                    'warning_text2': 'Un redémarrage du système est nécessaire après modification des paramètres I2S.',
                    'yes_btn': 'Oui',
                    'cancel_btn': 'Annuler'
                },
                'zh': {
                    'i2s_title': 'I2S 设置',
                    'mode_title': '主模式',
                    'pll_mode': 'PLL',
                    'ext_mode': 'EXT',
                    'submode_title': '输出模式',
                    'std_mode': 'STD',
                    'lr_mode': 'L/R',
                    'plr_mode': '±L/±R',
                    '8ch_mode': '8CH',
                    'mclk_title': 'MCLK',
                    'warning_attention': '注意！',
                    'warning_text1': 'MCLK输出在PLL和EXT模式下具有不同的设置（OUTPUT/INPUT）。',
                    'warning_text2': '更改I2S设置后需要重启系统才能生效。',
                    'yes_btn': '是',
                    'cancel_btn': '取消'
                }
            };
            
            const currentLang = detectLanguage();
            
            // Apply translations without touching button states
            $('[data-lang]').each(function() {
                const key = $(this).data('lang');
                if (translations[currentLang] && translations[currentLang][key]) {
                    $(this).text(translations[currentLang][key]);
                }
            });
        });
    </script>
</body>
</html>