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
	position: relative; 
	top: auto !important;
        transform: none !important;
        }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <a href="index.php" class="home-button" title="Main Menu">
        <img src="assets/img/home.svg" class="settings-icon" alt="Home">
      </a>
      <h1>APscream Settings</h1>
    </div>

    <form id="apscream-settings-form">
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
        <select id="tcp-mode" name="TCP_MODE">
          <option value="0">0 - Disabled</option>
          <option value="1">1 - Enabled</option>
        </select>
        <div class="help-text">Enable/disable TCP mode</div>
      </div>

      <div class="form-group">
        <label for="alsa-period-frames">ALSA_PERIOD_FRAMES:</label>
        <input type="number" id="alsa-period-frames" name="ALSA_PERIOD_FRAMES" min="256" max="65536" step="256">
        <div class="help-text">Number of frames per period (256-65536, in multiples of 256)</div>
      </div>

      <div class="form-group">
        <label for="alsa-buffer-frames">ALSA_BUFFER_FRAMES:</label>
        <input type="number" id="alsa-buffer-frames" name="ALSA_BUFFER_FRAMES" min="1024" max="131072" step="1024">
        <div class="help-text">Size of buffer in frames (1024-131072, in multiples of 1024)</div>
      </div>

      <div class="form-group">
        <label for="alsa-period-time">ALSA_PERIOD_TIME:</label>
        <input type="number" id="alsa-period-time" name="ALSA_PERIOD_TIME" min="-1" max="100000">
        <div class="help-text">Period time in microseconds (-1 to use ALSA default)</div>
      </div>

      <div class="form-group">
        <label for="alsa-buffer-time">ALSA_BUFFER_TIME:</label>
        <input type="number" id="alsa-buffer-time" name="ALSA_BUFFER_TIME" min="-1" max="100000">
        <div class="help-text">Buffer time in microseconds (-1 to use ALSA default)</div>
      </div>

      <div class="form-group">
        <label for="preload-buffer-frames">PRELOAD_BUFFER_FRAMES:</label>
        <input type="number" id="preload-buffer-frames" name="PRELOAD_BUFFER_FRAMES" min="1000" max="500000" step="1000">
        <div class="help-text">Number of frames to preload in buffer (1000-500000)</div>
      </div>

      <div class="form-group">
        <label for="scream-latency">SCREAM_LATENCY:</label>
        <input type="number" id="scream-latency" name="SCREAM_LATENCY" min="50" max="1000" step="10">
        <div class="help-text">Latency in milliseconds (50-1000)</div>
      </div>

      <div class="buttons">
        <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">Cancel</button>
        <button type="submit" class="save-btn">Save</button>
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

