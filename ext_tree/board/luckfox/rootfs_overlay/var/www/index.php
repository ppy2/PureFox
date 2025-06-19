<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio System Control</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>
    <div class="container">
        <!-- Кнопки управления плеерами -->
        <button class="btn-custom warning" data-service="naa" data-process="naa_process">HQPlayer (NAA)</button>
        <button class="btn-custom warning" data-service="raat" data-process="raat_process">Roon (RAAT)</button>
	<button class="btn-custom warning" data-service="shairport" data-process="shairport_process">AirPlay</button>
        <button class="btn-custom warning" data-service="lms" data-process="squeezelite">Squeezelite (LMS)</button>
        <button class="btn-custom warning" data-service="mpd" data-process="mpd">MPD (UPNP)</button>

        <div class="player-buttons" style="display: flex; gap: 10px;">
            <button class="btn-custom primary" data-service="aprenderer" data-process="aprenderer">
                APrender (UPNP)
                <a href="http://" class="settings-link" data-lang="settings">
                    <img src="assets/img/settings.png" class="settings-icon" alt="Settings">
                </a>
            </button>
            <button class="btn-custom primary" data-service="squeeze2upn" data-process="squeeze2upn">APsqueeze bridge</button>
        </div>

        <div class="ap-buttons" style="display: flex; gap: 10px;">
            <button class="btn-custom primary" data-service="aplayer" data-process="aplayer">
                APlayer (webradio)
                <a href="http://" class="settings-link" data-lang="settings">
                    <img src="assets/img/settings.png" class="settings-icon" alt="Settings">
                </a>
            </button>
            <button class="btn-custom primary" data-service="apscream" data-process="apscream">
                APscream (Asio)
                <a href="apscream_settings.php" class="settings-link" data-lang="settings">
                    <img src="assets/img/settings.png" class="settings-icon" alt="Settings">
                </a>
            </button>
        </div>
        <button class="btn-custom success" data-service="spotify" data-process="spotify">Spotify Connect</button>
	<button class="btn-custom success" data-service="tidalconnect" data-process="tidalconnect">Tidal Connect</button>
        <button class="btn-custom success" data-service="qobuz" data-process="qobuz">Qobuz Connect</button>
        

        <!-- Переключатель ALSA -->
        <div class="alsa-toggle">
            <label data-lang="alsa_output">Выход:</label>
            <div class="alsa-buttons">
                <button id="alsa-usb" class="alsa-button">USB</button>
                <button id="alsa-i2s" class="alsa-button">I2S</button>
            </div>
            <a href="#" id="i2s-settings-link" class="i2s-settings-link">
                <img src="assets/img/settings.png" class="settings-icon" alt="I2S Settings">
            </a>
        </div>

        <!-- Кнопка обновления прошивки -->
        <button id="update-firmware" class="btn-custom danger" data-lang="update_firmware">
            Обновить прошивку
            <span style="float: right; font-size: 0.9em;"></span>
        </button>
        PureFox v0.5.4 beta for LuckFox Pico Ultra
    </div>

    <!-- Всплывающее окно с логом обновления -->
    <div id="update-log-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #2a2a2a; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; height: auto; max-height: 80vh; overflow-y: auto; box-shadow: 0 0 10px rgba(0,0,0,0.5); color: #ddd; border: 2px solid #555;">
            <h2 style="margin-top: 0; color: #fff; text-align: center;" data-lang="firmware_update">Обновление прошивки</h2>
            <pre id="update-log-content" style="background: #181818; color: #0f0; padding: 10px; text-align: left; height: 300px; overflow-y: auto; white-space: pre-wrap; border: 1px solid #666;"></pre>
            <button class="close-modal" style="display: block; width: 100%; padding: 10px; margin-top: 10px; background: #444; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-transform: uppercase;" data-lang="close">Закрыть</button>
        </div>
    </div>

    <!-- Спиннер переключения плеера -->
    <div class="spinner-overlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <div class="spinner-text" data-lang="switching_player">Переключение плеера...</div>
        </div>
    </div>

    <!-- Подключение JavaScript -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js?v=<?php echo filemtime('app.js'); ?>"></script>
</body>
</html>
