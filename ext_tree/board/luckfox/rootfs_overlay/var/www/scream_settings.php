<?php
// scream_settings.php
require_once 'config.php';

$configFile = '/etc/scream.conf';
$defaults   = ['LATENCY' => 50, 'MODE' => 'unicast', 'PORT' => 4010];
$minLatency = 20;
$maxLatency = 1000;
$minPort    = 1024;
$maxPort    = 65535;

function readSettings($file, $defaults) {
    $settings = $defaults;
    if (is_readable($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (preg_match('/^\s*(LATENCY|PORT)\s*=\s*(\d+)\s*$/', $line, $m)) {
                    $settings[$m[1]] = (int)$m[2];
                } elseif (preg_match('/^\s*MODE\s*=\s*(unicast|multicast)\s*$/', $line, $m)) {
                    $settings['MODE'] = $m[1];
                }
            }
        }
    }
    return $settings;
}

// GET settings
if (isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json');
    echo json_encode([
        'success'  => true,
        'settings' => readSettings($configFile, $defaults)
    ]);
    exit;
}

// SAVE settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) ||
            !isset($input['LATENCY'], $input['MODE'], $input['PORT']) ||
            !preg_match('/^\d+$/', (string)$input['LATENCY']) ||
            !preg_match('/^\d+$/', (string)$input['PORT']) ||
            !in_array($input['MODE'], ['unicast', 'multicast'], true)) {
            throw new Exception('Invalid value');
        }

        $latency = (int)$input['LATENCY'];
        $port    = (int)$input['PORT'];
        $mode    = $input['MODE'];

        if ($latency < $minLatency || $latency > $maxLatency) {
            throw new Exception("Latency must be between $minLatency and $maxLatency ms");
        }
        if ($port < $minPort || $port > $maxPort) {
            throw new Exception("Port must be between $minPort and $maxPort");
        }

        $content = "LATENCY=$latency\nMODE=$mode\nPORT=$port\n";
        if (file_put_contents($configFile, $content, LOCK_EX) === false) {
            throw new Exception('Cannot write to config file');
        }
        shell_exec('sync');

        // Restart the receiver only if Scream is the active player
        $restarted = false;
        exec('pidof scream', $pidOut, $pidRc);
        if ($pidRc === 0) {
            shell_exec('/usr/bin/sudo /etc/rc.pure/S95scream restart > /dev/null 2>&1');
            $restarted = true;
        }

        echo json_encode([
            'success'   => true,
            'restarted' => $restarted,
            'message'   => $restarted ? 'Settings saved, Scream restarted'
                                      : 'Settings saved, will apply at next Scream start'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Display HTML page
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scream Settings</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo VERSION; ?>">
  <style>
    .container {
        max-width: 530px;
        width: calc(100% - 40px);
        margin: auto;
        padding: 25px;
        background-color: #2a2a2a;
        border-radius: 12px;
        box-shadow:
            0 30px 60px rgba(0, 0, 0, 0.9),
            0 20px 40px rgba(0, 0, 0, 0.8),
            0 10px 20px rgba(0, 0, 0, 0.7),
            0 0 0 1px rgba(255, 255, 255, 0.15);
        position: relative;
        top: 50%;
        transform: translateY(-50%);
    }
    .header {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: 25px;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: #e0e0e0;
        font-size: 14px;
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #555;
        border-radius: 4px;
        background: #2a2a2a;
        color: #e0e0e0;
        box-sizing: border-box;
    }
    .help-text {
        font-size: 11px;
        color: #aaa;
        margin-top: 3px;
        line-height: 1.3;
    }
    .buttons {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 20px;
        position: relative;
    }
    .save-btn {
        background-color: #5d5d5d !important;
        color: white !important;
        border: none !important;
        padding: 10px 20px !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        font-weight: bold !important;
    }
    .save-btn:hover { background-color: #6d6d6d !important; }
    .home-button {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .status-message {
        display: none;
        padding: 10px;
        margin-top: 15px;
        border-radius: 4px;
        text-align: center;
        font-weight: bold;
    }
    .status-message.success {
        background-color: #2d5a2d;
        color: #90ee90;
        border: 1px solid #4a8a4a;
    }
    .status-message.error {
        background-color: #5a2d2d;
        color: #ff9090;
        border: 1px solid #8a4a4a;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Scream Settings</h1>
    </div>

    <form id="scream-settings-form">
      <div class="form-group">
        <label for="mode">Mode:</label>
        <select id="mode" name="MODE">
          <option value="unicast">Unicast</option>
          <option value="multicast">Multicast</option>
        </select>
        <div class="help-text">Unicast: the sender must be configured with this board's IP. Multicast: group 239.255.77.77.</div>
      </div>

      <div class="form-group">
        <label for="port">Port:</label>
        <input type="number" id="port" name="PORT"
               min="<?php echo $minPort; ?>" max="<?php echo $maxPort; ?>" step="1">
        <div class="help-text">UDP port, <?php echo $minPort; ?>-<?php echo $maxPort; ?> (default 4010). Must match the sender.</div>
      </div>

      <div class="form-group">
        <label for="latency">Latency (-t), ms:</label>
        <input type="number" id="latency" name="LATENCY"
               min="<?php echo $minLatency; ?>" max="<?php echo $maxLatency; ?>" step="10">
        <div class="help-text">Target latency, <?php echo $minLatency; ?>-<?php echo $maxLatency; ?> ms. Higher values reduce dropouts but add delay. Scream restarts after saving.</div>
      </div>

      <div class="buttons">
        <a href="index.php" class="home-button">
          <img src="assets/img/home.svg" class="settings-icon" alt="">
        </a>
        <div style="flex: 1;"></div>
        <button type="submit" class="save-btn">Save</button>
      </div>
      <div id="status-message" class="status-message"></div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      loadSettings();
      document.getElementById('scream-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings();
      });
    });

    function loadSettings() {
      fetch('?action=get')
        .then(response => response.text())
        .then(text => {
          try {
            const data = JSON.parse(text);
            if (data.success) {
              document.getElementById('mode').value = data.settings.MODE;
              document.getElementById('port').value = data.settings.PORT;
              document.getElementById('latency').value = data.settings.LATENCY;
            } else {
              showMessage('Error loading settings: ' + data.message, 'error');
            }
          } catch (e) {
            console.error('Server response:', text);
            showMessage('Error: Server returned invalid response. Check console for details.', 'error');
          }
        })
        .catch(error => showMessage('Error loading settings: ' + error.message, 'error'));
    }

    function saveSettings() {
      const settings = {
        MODE: document.getElementById('mode').value,
        PORT: document.getElementById('port').value,
        LATENCY: document.getElementById('latency').value
      };

      fetch('scream_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings)
      })
      .then(response => response.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => { window.location.href = 'index.php'; }, 2000);
          } else {
            showMessage('Error saving settings: ' + data.message, 'error');
          }
        } catch (e) {
          console.error('Server response:', text);
          showMessage('Error: Server returned invalid response. Check console (F12) for details.', 'error');
        }
      })
      .catch(error => showMessage('Error saving settings: ' + error.message, 'error'));
    }

    function showMessage(message, type) {
      const statusMessage = document.getElementById('status-message');
      statusMessage.textContent = message;
      statusMessage.className = 'status-message ' + type;
      statusMessage.style.display = 'block';
      setTimeout(() => { statusMessage.style.display = 'none'; }, 5000);
    }
  </script>
</body>
</html>
