<?php
// apscream_settings.php
require_once 'config.php';
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>APscream Settings</title>
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
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    @media (max-width: 500px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
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
    
    .form-group select:disabled {
        background: #1a1a1a;
        color: #888;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    .help-text {
        font-size: 11px;
        color: #aaa;
        margin-top: 3px;
        line-height: 1.3;
    }
    
    .buttons {
        grid-column: 1 / -1;
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
    
    .save-btn:hover {
        background-color: #6d6d6d !important;
    }
    
    .home-button {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>APscream Settings</h1>
    </div>

    <form id="apscream-settings-form">
      <div class="form-grid">
        <div class="form-group">
          <label for="ap-mode">AP_MODE:</label>
          <select id="ap-mode" name="AP_MODE">
            <option value="0">0 - Disabled</option>
            <option value="1">1 - Enabled</option>
          </select>
          <div class="help-text">Enable/disable APscream mode</div>
        </div>

        <div class="form-group">
          <label for="tcp-mode">TCP_MODE:</label>
          <select id="tcp-mode" name="TCP_MODE" disabled>
            <option value="0">0 - Disabled</option>
            <option value="1">1 - Enabled</option>
          </select>
          <div class="help-text">TCP mode status (read-only)</div>
        </div>

        <div class="form-group">
          <label for="alsa-period-frames">ALSA_PERIOD_FRAMES:</label>
          <input type="number" id="alsa-period-frames" name="ALSA_PERIOD_FRAMES" min="256" max="65536" step="256">
          <div class="help-text">Frames per period (256-65536)</div>
        </div>

        <div class="form-group">
          <label for="alsa-buffer-frames">ALSA_BUFFER_FRAMES:</label>
          <input type="number" id="alsa-buffer-frames" name="ALSA_BUFFER_FRAMES" min="1024" max="131072" step="1024">
          <div class="help-text">Buffer size in frames (1024-131072)</div>
        </div>

        <div class="form-group">
          <label for="alsa-period-time">ALSA_PERIOD_TIME:</label>
          <input type="number" id="alsa-period-time" name="ALSA_PERIOD_TIME" min="-1" max="100000">
          <div class="help-text">Period time μs (-1 = auto)</div>
        </div>

        <div class="form-group">
          <label for="alsa-buffer-time">ALSA_BUFFER_TIME:</label>
          <input type="number" id="alsa-buffer-time" name="ALSA_BUFFER_TIME" min="-1" max="100000">
          <div class="help-text">Buffer time μs (-1 = auto)</div>
        </div>

        <div class="form-group">
          <label for="preload-buffer-frames">PRELOAD_BUFFER_FRAMES:</label>
          <input type="number" id="preload-buffer-frames" name="PRELOAD_BUFFER_FRAMES" min="1000" max="500000" step="1000">
          <div class="help-text">Preload frames (1000-500000)</div>
        </div>

        <div class="form-group">
          <label for="scream-latency">SCREAM_LATENCY:</label>
          <input type="number" id="scream-latency" name="SCREAM_LATENCY" min="50" max="1000" step="10">
          <div class="help-text">Latency in ms (50-1000)</div>
        </div>

        <div class="buttons">
          <a href="index.php" class="home-button">
            <img src="assets/img/home.svg" class="settings-icon" alt="">
          </a>
          <div style="flex: 1;"></div>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </div>
      <div id="status-message" class="status-message"></div>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Load current settings
      loadSettings();

      // Handle form submission
      document.getElementById('apscream-settings-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings();
      });
    });

    function loadSettings() {
      fetch('get_apscream_settings.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('ap-mode').value = data.settings.AP_MODE;
            document.getElementById('tcp-mode').value = data.settings.TCP_MODE;
            document.getElementById('alsa-period-frames').value = data.settings.ALSA_PERIOD_FRAMES;
            document.getElementById('alsa-buffer-frames').value = data.settings.ALSA_BUFFER_FRAMES;
            document.getElementById('alsa-period-time').value = data.settings.ALSA_PERIOD_TIME;
            document.getElementById('alsa-buffer-time').value = data.settings.ALSA_BUFFER_TIME;
            document.getElementById('preload-buffer-frames').value = data.settings.PRELOAD_BUFFER_FRAMES;
            document.getElementById('scream-latency').value = data.settings.SCREAM_LATENCY;
          } else {
            showMessage('Error loading settings: ' + data.message, 'error');
          }
        })
        .catch(error => {
          showMessage('Error loading settings: ' + error, 'error');
        });
    }

    function saveSettings() {
      const formData = new FormData(document.getElementById('apscream-settings-form'));
      const settings = {};
      formData.forEach((value, key) => {
        settings[key] = value;
      });

      fetch('save_apscream_settings.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(settings)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showMessage('Settings saved successfully', 'success');
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 2000);
        } else {
          showMessage('Error saving settings: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showMessage('Error saving settings: ' + error, 'error');
      });
    }

    function showMessage(message, type) {
      const statusMessage = document.getElementById('status-message');
      statusMessage.textContent = message;
      statusMessage.className = 'status-message ' + type;
      statusMessage.style.display = 'block';
      setTimeout(() => {
        statusMessage.style.display = 'none';
      }, 5000);
    }
  </script>
</body>
</html>

