<?php
require_once 'config.php';
// Path to configuration file
$config_file = '/etc/i2s.conf';

/**
 * Function to read current mode (MODE), MCLK and SUBMODE from config file
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

// --- AJAX request handling for status ---
if (isset($_GET['action']) && $_GET['action'] === 'getStatus') {
    $config = readConfig($config_file);
    header('Content-Type: application/json');
    echo json_encode($config);
    exit;
}

// --- POST request handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mode change (PLL/EXT)
    if (isset($_POST['mode'])) {
        $mode = $_POST['mode'];
        if (in_array($mode, ['pll', 'ext'])) {
            $script = ($mode === 'pll') ? '/opt/2pll.sh' : '/opt/2ext.sh';
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }

    // Submode change (STD/L/R/±L/±R/8CH)
    if (isset($_POST['submode'])) {
        $submode = $_POST['submode'];
        if (in_array($submode, ['std', 'lr', 'plr', '8ch'])) {
            $script = "/opt/2_$submode.sh";
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }

    // MCLK change
    if (isset($_POST['mclk'])) {
        $mclk = $_POST['mclk'];
        if (in_array($mclk, ['512', '1024'])) {
            // Choose script based on current mode
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
    
    // Reboot
    // Reboot handling removed - now using reboot.php

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- GET request: read current state ---
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
    <title>I2S Mode Settings</title>
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
            customConfirm("Are you sure you want to reboot the device?", function(confirmed) {
                if (confirmed) {
                    document.querySelector('.spinner-overlay').classList.add('show');
                    document.querySelector('.spinner-text').textContent = 'Rebooting...';
                    
                    fetch('reboot.php', {
                        method: 'POST'
                    }).then(() => {
                        // Wait for connection restoration after reboot
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
    <!-- Spinner -->
    <div class="spinner-overlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <div class="spinner-text">Loading...</div>
        </div>
    </div>
    <div id="statusIndicator" class="status-indicator"></div>
    <div class="container">
        <div class="header">
            <a href="index.php" class="home-button" title="Main Menu">
                <img src="assets/img/home.svg" class="settings-icon" alt="Home">
            </a>
            <h1>I2S Settings</h1>
        </div>
        <form method="post" onsubmit="showOverlay()">
            <div class="group">
                <h2>Main Mode</h2>
                <div class="row">
                    <button type="submit" name="mode" value="pll" id="pll-btn"
                        class="btn-custom <?php if ($current_mode === 'pll') echo 'active'; ?>">PLL</button>
                    <button type="submit" name="mode" value="ext" id="ext-btn"
                        class="btn-custom <?php if ($current_mode === 'ext') echo 'active'; ?>">EXT</button>
                </div>
            </div>
            <div class="group">
                <h2>Output Mode</h2>
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
                <span class="pulse">Warning!</span> <br>
                MCLK output has different settings in PLL and EXT modes (OUTPUT/INPUT). <br>
                System reboot is required after changing I2S settings to apply them.
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
                <button id="confirm-yes" class="confirm-btn confirm-btn-yes">Yes</button>
                <button id="confirm-no" class="confirm-btn confirm-btn-no">Cancel</button>
            </div>
        </div>
    </div>
</body>
</html>
