<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio System Control</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo VERSION; ?>&t=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Кнопки управления плеерами -->
        <button class="btn-custom warning" data-service="naa" data-process="naa_process">HQPlayer (NAA)</button>
        <button class="btn-custom warning" data-service="raat" data-process="raat_process">Roon (RAAT)</button>
        
        <div class="streaming-buttons">
            <button class="btn-custom warning" data-service="lms" data-process="squeezelite">Squeezelite (LMS)</button>
            <button class="btn-custom warning" data-service="shairport" data-process="shairport_process">AirPlay</button>
        </div>
        
        <button class="btn-custom warning" data-service="mpd" data-process="mpd">MPD (UPnP)</button>

        <div class="player-buttons">
            <button class="btn-custom primary" data-service="aprenderer" data-process="aprenderer">
                APrender (UPnP)
                <a href="http://" class="settings-link" data-lang="settings">
                    <img src="assets/img/settings.svg" class="settings-icon" alt="Settings">
                </a>
            </button>
            <button class="btn-custom primary" data-service="squeeze2upn" data-process="squeeze2upn">APSqueeze bridge</button>
        </div>

        <div class="ap-buttons">
            <button class="btn-custom primary" data-service="aplayer" data-process="aplayer">
                APlayer
                <a href="http://" class="settings-link" data-lang="settings">
                    <img src="assets/img/radio.svg" class="settings-icon" alt="Radio">
                </a>
            </button>
            <button class="btn-custom primary" data-service="apscream" data-process="apscream">
                APScream (ASIO)
                <a href="apscream_settings.php" class="settings-link" data-lang="settings">
                    <img src="assets/img/settings.svg" class="settings-icon" alt="Settings">
                </a>
            </button>
        </div>
        <button class="btn-custom success" data-service="spotify" data-process="spotify">Spotify Connect</button>
        <button class="btn-custom success" data-service="qobuz" data-process="qobuz">Qobuz Connect</button>
        

        <!-- Переключатель ALSA и регулятор громкости -->
        <div class="alsa-toggle">
            <div class="alsa-left">
                <div class="toggle-switch">
                    <input type="checkbox" id="alsa-toggle" class="toggle-input">
                    <label for="alsa-toggle" class="toggle-label">
                        <span class="toggle-option left">USB</span>
                        <span class="toggle-slider"></span>
                        <span class="toggle-option right">I2S</span>
                    </label>
                </div>
                <a href="#" id="i2s-settings-link" class="i2s-settings-link">
                    <img src="assets/img/settings.svg" class="settings-icon" alt="I2S Settings">
                </a>
            </div>
            <div class="volume-control">
                <img src="assets/img/volume.svg" class="settings-icon volume-icon" alt="Volume" id="volume-icon">
                <input type="range" id="volume-slider" class="volume-slider" min="0" max="100" value="0">
                <span id="volume-display" class="volume-display">--</span>
            </div>
        </div>

        <!-- Кнопки обновления и управления питанием -->
        <div class="power-controls">
            <button id="update-firmware" class="btn-custom danger firmware-btn">
                <div class="firmware-text">
                    <div class="firmware-title">PureFox v<?php echo VERSION; ?></div>
                    <div class="firmware-subtitle">for LuckFox Pico MAX</div>
                </div>
                <img src="assets/img/firmware.svg" class="settings-icon firmware-icon" alt="Firmware">
            </button>
            <div class="system-buttons">
                <button type="button" class="btn-custom system-btn" id="reboot-link">
                    <img src="assets/img/reboot.svg" class="settings-icon reboot-icon" alt="Reboot">
                </button>
                <button type="button" class="btn-custom system-btn" id="shutdown-link">
                    <img src="assets/img/shutdown.svg" class="settings-icon shutdown-icon" alt="Shutdown">
                </button>
            </div>
        </div>
    </div>

    <!-- Всплывающее окно с логом обновления -->
    <div id="update-log-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 class="modal-header" data-lang="firmware_update">Обновление прошивки</h2>
            <pre id="update-log-content" class="modal-log-content"></pre>
            <button class="close-modal modal-close-btn" data-lang="close">Закрыть</button>
        </div>
    </div>

    <!-- Спиннер переключения плеера -->
    <div class="spinner-overlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <div class="spinner-text" data-lang="switching_player">Переключение плеера...</div>
        </div>
    </div>

    <!-- Кастомный confirm диалог -->
    <div id="custom-confirm" class="confirm-overlay">
        <div class="confirm-content">
            <div id="confirm-message" class="confirm-message"></div>
            <div class="confirm-buttons">
                <button id="confirm-yes" class="confirm-btn confirm-btn-yes">Да</button>
                <button id="confirm-no" class="confirm-btn confirm-btn-no">Отмена</button>
            </div>
        </div>
    </div>

    <!-- Кастомный alert диалог -->
    <div id="custom-alert" class="confirm-overlay">
        <div class="confirm-content">
            <div id="alert-message" class="confirm-message"></div>
            <div class="confirm-buttons">
                <button id="alert-ok" class="confirm-btn confirm-btn-yes">OK</button>
            </div>
        </div>
    </div>

    <!-- Подключение JavaScript -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js?v=<?php echo VERSION; ?>&t=<?php echo time(); ?>"></script>
</body>
</html>
