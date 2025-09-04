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
                <a href="#" id="i2s-settings-link" class="i2s-settings-link" onclick="event.preventDefault(); openI2SModal(); return false;">
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

    <!-- I2S Settings Modal -->
    <div id="i2s-modal" class="modal-overlay">
        <div class="modal-content i2s-modal-content">
            <div class="header">
                <h1 data-lang="i2s_title">I2S Settings</h1>
            </div>
            <form id="i2s-form" method="post" action="i2s.php">
                <div class="i2s-group">
                    <div class="i2s-group-header">
                        <h3 data-lang="mode_title">Режим</h3>
                        <div class="toggle-switch-compact">
                            <input type="radio" name="mode" value="pll" id="modal-mode-pll" class="toggle-input-compact">
                            <input type="radio" name="mode" value="ext" id="modal-mode-ext" class="toggle-input-compact">
                            <label class="toggle-label-compact">
                                <div class="toggle-slider-compact"></div>
                                <span class="toggle-option-compact left" data-lang="pll_mode" onclick="document.getElementById('modal-mode-pll').click()">PLL</span>
                                <span class="toggle-option-compact right" data-lang="ext_mode" onclick="document.getElementById('modal-mode-ext').click()">EXT</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="i2s-group">
                    <div class="i2s-submode-rows">
                        <div class="i2s-submode-row">
                            <button type="button" name="submode" value="std" class="btn-custom i2s-submode-btn" data-lang="std_mode">STD</button>
                            <button type="button" name="submode" value="8ch" class="btn-custom i2s-submode-btn" data-lang="8ch_mode">8CH</button>
                        </div>
                        <div class="i2s-submode-row">
                            <button type="button" name="submode" value="lr" class="btn-custom i2s-submode-btn" data-lang="lr_mode">L/R</button>
                            <button type="button" name="submode" value="plr" class="btn-custom i2s-submode-btn" data-lang="plr_mode">±L/±R</button>
                        </div>
                    </div>
                </div>
                <div class="i2s-group">
                    <div class="i2s-group-header">
                        <h3 data-lang="mclk_title">MCLK</h3>
                        <div class="toggle-switch-compact">
                            <input type="radio" name="mclk" value="512" id="modal-mclk-512" class="toggle-input-compact">
                            <input type="radio" name="mclk" value="1024" id="modal-mclk-1024" class="toggle-input-compact">
                            <label class="toggle-label-compact">
                                <div class="toggle-slider-compact"></div>
                                <span class="toggle-option-compact left" onclick="document.getElementById('modal-mclk-512').click()">512</span>
                                <span class="toggle-option-compact right" onclick="document.getElementById('modal-mclk-1024').click()">1024</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="i2s-group">
                    <div class="i2s-group-header">
                        <h3 data-lang="pcm_swap_title">PCM Swap</h3>
                        <div class="toggle-switch-compact">
                            <input type="radio" name="pcm_swap" value="0" id="modal-pcm-normal" class="toggle-input-compact">
                            <input type="radio" name="pcm_swap" value="1" id="modal-pcm-swap" class="toggle-input-compact">
                            <label class="toggle-label-compact">
                                <div class="toggle-slider-compact"></div>
                                <span class="toggle-option-compact left" onclick="document.getElementById('modal-pcm-normal').click()">OFF</span>
                                <span class="toggle-option-compact right" onclick="document.getElementById('modal-pcm-swap').click()">ON</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="i2s-group">
                    <div class="i2s-group-header">
                        <h3 data-lang="dsd_swap_title">DSD Swap</h3>
                        <div class="toggle-switch-compact">
                            <input type="radio" name="dsd_swap" value="0" id="modal-dsd-normal" class="toggle-input-compact">
                            <input type="radio" name="dsd_swap" value="1" id="modal-dsd-swap" class="toggle-input-compact">
                            <label class="toggle-label-compact">
                                <div class="toggle-slider-compact"></div>
                                <span class="toggle-option-compact left" onclick="document.getElementById('modal-dsd-normal').click()">OFF</span>
                                <span class="toggle-option-compact right" onclick="document.getElementById('modal-dsd-swap').click()">ON</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="i2s-warning">
                    <span class="i2s-pulse" data-lang="warning_attention">Внимание!</span>
                    <span data-lang="warning_text1">Выход MCLK в режимах PLL и EXT имеет разные настройки (OUTPUT/INPUT).</span> <span data-lang="warning_text2">После изменения настроек I2S необходима перезагрузка системы для вступления в силу.</span>
                </div>
                <div class="modal-bottom-buttons">
                    <a href="#" onclick="closeI2SModal(); return false;" class="close-link">
                        <img src="assets/img/home.svg" class="settings-icon close-icon" alt="">
                    </a>
                    <a href="#" onclick="confirmRebootI2S(event)" class="reboot-link">
                        <img src="assets/img/reboot.svg" class="settings-icon reboot-icon" alt="">
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Подключение JavaScript -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js?v=<?php echo VERSION; ?>&t=<?php echo time(); ?>"></script>
</body>
</html>
