<?php
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
            $script = ($mclk === '1024') ? '/opt/2_1024_ext.sh' : '/opt/2_512_ext.sh';
            exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
        }
    }
    
    // Reboot
    if (isset($_POST['reboot'])) {
        exec('/usr/bin/sudo /sbin/reboot 2>&1', $output, $returnVar);
    }

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
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
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
            text-align: center;
        }
        .btn-reboot {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-reboot:hover {
            background-color: #c82333;
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
            document.querySelector('.spinner-overlay').style.display = 'flex';
        }
        function confirmReboot() {
            return confirm("Are you sure you want to reboot the device?");
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
                    
                    document.getElementById('mclk-512-btn').disabled = (data.mode === 'pll');
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
            <a href="index.php" class="home-button">
                <svg class="home-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 5.69l5 4.5V18h-2v-6H9v6H7v-7.81l5-4.5M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/>
                </svg>
                Main Menu
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
                        class="btn-custom <?php if ($current_mclk === '512') echo 'active'; ?>"
                        <?php if ($current_mode === 'pll') echo 'disabled'; ?>>512</button>
                    <button type="submit" name="mclk" value="1024" id="mclk-1024-btn"
                        class="btn-custom <?php if ($current_mclk === '1024') echo 'active'; ?>">1024</button>
                </div>
            </div>
            <div class="warning">
                <span class="pulse">Warning!</span> <br>
                MCLK output has different settings in PLL and EXT modes (OUTPUT/INPUT). <br>
                Only MCLK = 1024 is available in PLL mode. <br>
                Device reboot required after any changes.
            </div>
        </form>
        <form method="post" onsubmit="return confirmReboot() && showOverlay()">
            <div class="reboot-btn">
                <button type="submit" name="reboot" value="1" class="btn-reboot">Reboot</button>
            </div>
        </form>
    </div>
</body>
</html>
