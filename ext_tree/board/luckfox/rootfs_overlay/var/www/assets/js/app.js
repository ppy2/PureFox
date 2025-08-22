$(document).ready(function () {
    // Polling system configuration
    const POLLING_CONFIG = {
        NORMAL_INTERVAL: 3000,    // 3 seconds normal interval
        SWITCHING_INTERVAL: 1000  // 1 second when switching
    };

    // Global variables
    let lastKnownStatus = null;
    let isServiceSwitching = false; // Flag to block updates during switching
    let isVolumeChanging = false; // Flag to block volume updates during user changes
    let isAlsaSwitching = false; // Flag to block ALSA updates during switching
    let statusInterval = null;
    
    // Universal interface update function
    function updateInterfaceFromStatus(data) {
        // Update active service ONLY if switching is not in progress
        if (data.active_service !== undefined && !isServiceSwitching) {
            $('button[data-service]').removeClass('active');
            if (data.active_service) {
                $(`button[data-service="${data.active_service}"]`).addClass('active');
            }
        }
        
        // Update ALSA state ONLY if not switching
        if (data.alsa_state !== undefined && !isAlsaSwitching) {
            updateAlsaUI(data.alsa_state);
        }
        
        // Update volume only if user is not changing it at the moment
        if (!isVolumeChanging) {
            updateVolumeFromStatus(data);
        }
    }
    let previousActiveService = null;


    // Dynamic URL setup based on current host (PRESERVED!)
    var currentHost = window.location.hostname;
    $('button[data-service="aprenderer"] .settings-link').attr('href', 'http://' + currentHost + ':7779');
    $('button[data-service="aplayer"] .settings-link').attr('href', 'http://' + currentHost + ':7778');

    // Language dictionaries (FULLY PRESERVED!)
    const translations = {
        'ru': {
            'Output': 'Выход:',
            'i2s_settings': 'Настройки I2S',
            'update_firmware': 'Обновить прошивку',
            'firmware_update': 'Обновление прошивки',
            'close': 'Закрыть',
            'update_start': 'Запуск обновления...',
            'update_finish': 'Обновление завершено. Перезагрузите LuckFox',
            'update_error': 'Ошибка при обновлении.',
            'confirm_update': 'Вы уверены, что хотите обновить прошивку?',
            'alsa_error': 'Ошибка при переключении ALSA',
            'service_error': 'Ошибка при переключении сервиса',
            'settings': '',
            'switching_player': 'Переключение плеера...',
            'switching_output': 'Переключение выхода...',
            'usb_dac_missing': 'USB ЦАП не обнаружен.<br>Пожалуйста, подключите USB ЦАП',
            'confirm_reboot': 'Вы уверены, что хотите перезагрузить систему?',
            'confirm_shutdown': 'Вы уверены, что хотите выключить систему?',
            'shutdown_complete': 'Система выключена. Можете отключить питание.'
        },
        'en': {
            'alsa_output': 'ALSA Output:',
            'i2s_settings': 'I2S Settings',
            'update_firmware': 'Update Firmware',
            'firmware_update': 'Firmware Update',
            'close': 'Close',
            'update_start': 'Starting update...',
            'update_finish': 'Update completed. Please reboot LuckFox',
            'update_error': 'Update error.',
            'confirm_update': 'Are you sure you want to update the firmware?',
            'alsa_error': 'Error switching ALSA',
            'service_error': 'Error switching service',
            'settings': '',
            'switching_player': 'Switching player...',
            'switching_output': 'Switching output...',
            'usb_dac_missing': 'USB DAC not detected.<br>Please connect USB DAC',
            'confirm_reboot': 'Are you sure you want to reboot the system?',
            'confirm_shutdown': 'Are you sure you want to shutdown the system?',
            'shutdown_complete': 'System has been shut down. You can disconnect power.'
        }
    };

    // Browser language detection (PRESERVED!)
    function detectLanguage() {
        const lang = navigator.language || navigator.userLanguage;
        return lang.startsWith('ru') ? 'ru' : 'en';
    }

    // Language application (PRESERVED!)
    const currentLang = detectLanguage();
    const i2sLink = currentLang === 'ru' ? 'i2s_ru.php' : 'i2s_en.php';
    $('#i2s-settings-link').attr('href', i2sLink);

    function applyTranslations() {
        $('[data-lang]').each(function() {
            const key = $(this).data('lang');
            if (translations[currentLang][key] !== undefined) {
                if (key !== 'settings' || !$(this).find('img').length) {
                    // Special handling for update_firmware button to preserve icon
                    if (key === 'update_firmware' && $(this).find('img').length) {
                        const icon = $(this).find('img').detach();
                        $(this).text(translations[currentLang][key]);
                        $(this).append(icon);
                    } else {
                        $(this).text(translations[currentLang][key]);
                    }
                }
            }
        });
    }

    applyTranslations();

    // Adaptive styles (FULLY PRESERVED!)
    function setResponsiveStyles() {
        if (window.innerWidth < 500) {
            $('.player-buttons, .ap-buttons').css({
                'display': 'block',
                'gap': '0'
            });
            $('.player-buttons .btn-custom, .ap-buttons .btn-custom').css({
                'width': '100%',
                'margin-top': '10px',
                'margin-bottom': '0'
            });
            $('.alsa-button').css('padding', '10px');
        } else {
            $('.player-buttons, .ap-buttons').css({
                'display': 'flex',
                'gap': '10px'
            });
            $('.player-buttons .btn-custom, .ap-buttons .btn-custom').css({
                'width': 'calc(50% - 5px)',
                'margin-top': '0',
                'margin-bottom': '0'
            });
            $('.alsa-button').css('padding', '10px 20px');
        }
    }

    setResponsiveStyles();
    $(window).resize(function() {
        setResponsiveStyles();
    });
    
    // Check CSS gap support
    function checkGapSupport() {
        const testElement = document.createElement('div');
        testElement.style.display = 'flex';
        testElement.style.gap = '10px';
        if (testElement.style.gap === '10px') {
            document.documentElement.classList.add('supports-gap');
        }
    }
    checkGapSupport();

    // Force status check on user actions
    function forceStatusCheck() {
        console.log('Force checking status...');
        $.ajax({
            url: 'status_fast.php',
            method: 'GET',
            timeout: 3000,
            dataType: 'json',
            success: function(response) {
                console.log('Force check:', response);
                updateInterfaceFromStatus(response);
                lastKnownStatus = response;
            },
            error: function() {
                console.warn('Force check error');
            }
        });
    }

    // Track page visibility
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible - force check
            forceStatusCheck();
        }
    });

    // Spinner control (PRESERVED!)
    function showSpinner(text = null) {
        if (text) {
            $('.spinner-text').text(text);
        }
        $('.spinner-overlay').css('display', 'flex');
    }

    function hideSpinner() {
        $('.spinner-overlay').css('display', 'none');
        // Restore original text
        $('.spinner-text').text(translations[currentLang]['switching_player']);
    }

    // Check USB DAC via forced status check
    function checkUsbDac(successCallback, errorCallback) {
        // Always do fresh check for USB DAC - it can be connected/disconnected
        $.ajax({
            url: 'status_fast.php',
            method: 'GET',
            timeout: 3000,
            dataType: 'json',
            cache: false, // Force disable caching
            success: function(response) {
                lastKnownStatus = response;
                updateInterfaceFromStatus(response);
                
                if (response.usb_dac) {
                    if (successCallback) successCallback();
                } else {
                    customAlert(translations[currentLang]['usb_dac_missing']);
                    if (errorCallback) errorCallback();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error in checkUsbDac:', status, error, 'Response:', xhr.responseText);
                customAlert(translations[currentLang]['service_error'] + ': ' + status);
                if (errorCallback) errorCallback();
            }
        });
    }

    // Force service status check (on demand only)
    function checkActiveService(callback) {
        console.log('Checking active service...');
        $.ajax({
            url: 'status_fast.php',
            method: 'GET',
            timeout: 3000,
            dataType: 'json',
            success: function(response) {
                const activeService = response.active_service || '';
                
                lastKnownStatus = response;
                
                // Update UI
                if (activeService !== previousActiveService) {
                    if (previousActiveService !== null && previousActiveService !== activeService) {
                        hideSpinner();
                    }
                    previousActiveService = activeService;
                    $('.btn-custom').removeClass('active');
                    if (activeService) {
                        $(`button[data-service="${activeService}"]`).addClass('active');
                    }
                }
                
                updateAlsaUI(response.alsa_state);
                updateVolumeFromStatus(response);
                
                if (callback) callback(activeService);
            },
            error: function() {
                console.warn('Status check error');
            }
        });
    }

    // NEW function for updating ALSA UI
    function updateAlsaUI(alsaState) {
        const toggleInput = $('#alsa-toggle');
        const i2sSettingsLink = $('#i2s-settings-link');
        
        $('.alsa-toggle').removeClass('active-i2s');
        
        switch (alsaState) {
            case 'usb':
                toggleInput.prop('checked', false);
                // Disable I2S settings when USB - instantly!
                i2sSettingsLink.addClass('no-transition').css({
                    'opacity': '0.3',
                    'pointer-events': 'none',
                    'cursor': 'not-allowed'
                });
                setTimeout(() => i2sSettingsLink.removeClass('no-transition'), 10);
                break;
            case 'i2s':
                toggleInput.prop('checked', true);
                $('.alsa-toggle').addClass('active-i2s');
                // Enable I2S settings when I2S - instantly!
                i2sSettingsLink.addClass('no-transition').css({
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'cursor': 'pointer'
                });
                setTimeout(() => i2sSettingsLink.removeClass('no-transition'), 10);
                break;
            case 'error':
                console.error('Error reading ALSA configuration');
                break;
        }
    }

    // Check ALSA status via force check
    function checkAlsaState() {
        // Use last known status
        if (lastKnownStatus && lastKnownStatus.alsa_state) {
            updateAlsaUI(lastKnownStatus.alsa_state);
            return;
        }
        
        // Otherwise force check
        checkActiveService();
    }

    // Handle ALSA toggle switching
    $('#alsa-toggle').change(function(e) {
        e.preventDefault();
        const checkbox = $(this);
        const isChecked = checkbox.is(':checked');
        const cardType = isChecked ? 'i2s' : 'usb';
        
        // IMMEDIATELY update UI settings icons when clicking toggle!
        updateAlsaUI(cardType);
        
        // Block ALSA updates during switching
        isAlsaSwitching = true;
        
        forceStatusCheck(); // Force check on click

        if (cardType === 'usb') {
            checkUsbDac(
                function() { switchAlsa(cardType); },
                function() { 
                    // On error, return toggle to original state
                    checkbox.prop('checked', !isChecked);
                    updateAlsaUI(!isChecked ? 'i2s' : 'usb'); // Rollback UI icons
                    isAlsaSwitching = false; // Unblock updates
                }
            );
        } else {
            switchAlsa(cardType);
        }
    });

    // Simplified ALSA switching function
    function switchAlsa(cardType) {
        // UI already updated by user (toggle switch), just send command
        $.ajax({
            url: 'handle_alsa.php',
            method: 'POST',
            data: { card: cardType },
            timeout: 10000,
            success: function() {
                // UI already updated on click, just unblock updates
                setTimeout(() => {
                    isAlsaSwitching = false;
                }, 2000);
            },
            error: function() {
                // On error, return toggle to original state
                const oppositeState = (cardType === 'usb') ? 'i2s' : 'usb';
                updateAlsaUI(oppositeState);
                isAlsaSwitching = false; // Unblock updates
            }
        });
    }

    // Handle clicks on service buttons (FULLY PRESERVED!)
    $('.btn-custom').click(function(e) {
        if ($(e.target).is('a') || $(e.target).is('img')) return true;
        if (!$(this).data('service') || $(this).hasClass('active')) return;

        const service = $(this).data('service');
        forceStatusCheck(); // Force check on click
        
        // Use last known ALSA state
        const alsaState = lastKnownStatus ? lastKnownStatus.alsa_state : null;
        if (alsaState === 'usb') {
            checkUsbDac(function() { switchPlayerService(service); });
        } else {
            switchPlayerService(service);
        }
    });

    // Service switching function with instant button activation
    function switchPlayerService(service) {
        // Block button updates during switching
        isServiceSwitching = true;
        
        // IMMEDIATELY make button active for UI responsiveness
        $('.btn-custom').removeClass('active');
        $(`button[data-service="${service}"]`).addClass('active');
        console.log('Button', service, 'activated instantly, waiting for service startup...');
        
        // Service switching
        
        // Increased timeout for services with two-stage startup
        const timeoutDuration = (service === 'qobuz') ? 15000 : (service === 'tidalconnect') ? 12000 : 8000;
        
        // Timeout for button deactivation on failure
        let switchingTimeout = setTimeout(() => {
            console.warn('Switching timeout to', service, '- deactivating button');
            isServiceSwitching = false; // Unblock updates
            $('.btn-custom').removeClass('active');
            customAlert(translations[currentLang]['service_error']);
        }, timeoutDuration);
        
        $.ajax({
            url: 'handle_service.php',
            method: 'POST',
            data: { service: service },
            timeout: 15000,
            success: function() {
                // Give time for service startup - increased time for two-stage launches
                const initialDelay = (service === 'qobuz') ? 5000 : (service === 'tidalconnect') ? 4000 : 2000;
                
                console.log('Switch command to', service, 'sent, waiting', initialDelay, 'ms...');
                if (service === 'qobuz') {
                    console.log('Qobuz: using maximum delay for two-stage startup (trial + working)');
                } else if (service === 'tidalconnect') {
                    console.log('Tidal: using increased delay for two-stage startup');
                }
                
                // ADDED initial delay - give service time to start!
                setTimeout(() => {
                    const checkInterval = POLLING_CONFIG.SWITCHING_INTERVAL;
                    let checkCount = 0;
                    const maxChecks = 60; // 30 seconds maximum

                    function checkServiceStatusChange() {
                        $.ajax({
                            url: 'status_fast.php', // Use optimized request
                            method: 'GET',
                            timeout: 3000,
                            dataType: 'json',
                            success: function(response) {
                                const activeService = response.active_service || '';
                                checkCount++;
                                
                                console.log('Check', checkCount, ': active service =', activeService, ', expected =', service);
                                
                                if (!activeService) {
                                    console.warn('No active services, continuing check... (attempt', checkCount, 'of', maxChecks, ')');
                                    if (checkCount >= maxChecks) {
                                        console.error('Service', service, 'did not start after maximum attempts');
                                        clearTimeout(switchingTimeout);
                                        $('.btn-custom').removeClass('active');
                                        customAlert(translations[currentLang]['service_error']);
                                        return;
                                    }
                                    // Continue checking, button remains active
                                    setTimeout(checkServiceStatusChange, checkInterval);
                                    return;
                                }
                                
                                if (activeService === service) {
                                    console.log('Successfully switched to', service);
                                    clearTimeout(switchingTimeout); // Cancel failure timeout
                                    isServiceSwitching = false; // Unblock updates
                                    lastKnownStatus = response;
                                    updateInterfaceFromStatus(response);
                                    console.log('Service switched successfully');
                                } else if (activeService !== service) {
                                    console.log("Activated service " + activeService + " instead of " + service);
                                    clearTimeout(switchingTimeout); // Cancel failure timeout
                                    isServiceSwitching = false; // Unblock updates
                                    $('.btn-custom').removeClass('active');
                                    $(`button[data-service="${activeService}"]`).addClass('active');
                                    lastKnownStatus = response;
                                    updateInterfaceFromStatus(response);
                                } else if (checkCount >= maxChecks) {
                                    console.error("Timeout switching to service " + service);
                                    clearTimeout(switchingTimeout);
                                    isServiceSwitching = false; // Unblock updates
                                    $('.btn-custom').removeClass('active');
                                    customAlert(translations[currentLang]['service_error']);
                                } else {
                                    setTimeout(checkServiceStatusChange, checkInterval);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Status check error:', status, error);
                                clearTimeout(switchingTimeout);
                                isServiceSwitching = false; // Unblock updates
                                $('.btn-custom').removeClass('active');
                                customAlert(translations[currentLang]['service_error']);
                            }
                        });
                    }

                    // Monitoring removed - relying on polling

                    checkServiceStatusChange();
                }, initialDelay); // Use adaptive delay
            },
            error: function(xhr, status, error) {
                console.error('AJAX error switching to', service, ':', status, error, 'Response:', xhr.responseText);
                clearTimeout(switchingTimeout); // Cancel timeout on AJAX error
                isServiceSwitching = false; // Unblock updates
                $('.btn-custom').removeClass('active');
                customAlert(translations[currentLang]['service_error'] + ': ' + status);
            }
        });
    }

    // Initialization
    checkActiveService(function(activeService) {
        previousActiveService = activeService;
    });
    checkAlsaState();

    // Custom confirm dialog
    function customConfirm(message, callback) {
        $('#confirm-message').text(message);
        $('#custom-confirm').addClass('show');
        
        // Update button text based on language
        $('#confirm-yes').text(currentLang === 'ru' ? 'Да' : 'Yes');
        $('#confirm-no').text(currentLang === 'ru' ? 'Отмена' : 'Cancel');
        
        // Button handlers
        $('#confirm-yes').off('click').on('click', function() {
            $('#custom-confirm').removeClass('show');
            callback(true);
        });
        
        $('#confirm-no').off('click').on('click', function() {
            $('#custom-confirm').removeClass('show');
            callback(false);
        });
    }
    
    function customAlert(message) {
        $('#alert-message').html(message);
        $('#custom-alert').addClass('show');
        
        // Update button text based on language
        $('#alert-ok').text(currentLang === 'ru' ? 'OK' : 'OK');
        
        // Button handler
        $('#alert-ok').off('click').on('click', function() {
            $('#custom-alert').removeClass('show');
        });
        
        // Close on background click
        $('#custom-alert').off('click').on('click', function(e) {
            if (e.target === this) {
                $('#custom-alert').removeClass('show');
            }
        });
    }

    // FULLY PRESERVED firmware update function!
    $('#update-firmware').click(function(e) {
        
        customConfirm(translations[currentLang]['confirm_update'], function(confirmed) {
            if (!confirmed) return;

        $('#update-log-modal').addClass('show');
        $('#update-log-content').text(translations[currentLang]['update_start'] + "\n");
        let logContent = $('#update-log-content');

        $.ajax({
            url: 'run_update.php',
            method: 'GET',
            xhrFields: {
                onprogress: function(e) {
                    let newText = e.currentTarget.responseText;
                    logContent.append(newText);
                    setTimeout(() => { logContent.scrollTop(logContent.prop("scrollHeight")); }, 100);
                }
            },
            success: function() {
                logContent.html(logContent.html());
                logContent.append('\n<span class="update-finish">' + translations[currentLang]['update_finish'] + '</span>');
            },
            error: function() {
                logContent.append('\n<span class="update-error">' + translations[currentLang]['update_error'] + '</span>');
                logContent.html(logContent.html());
            }
        });
        });
    });

    // PRESERVED modal window close function
    $('.close-modal').click(function() {
        $('#update-log-modal').removeClass('show');
    });

    // Handlers for reboot and shutdown
    $('#reboot-link').click(function(e) {
        e.preventDefault();
        customConfirm(translations[currentLang]['confirm_reboot'], function(confirmed) {
            if (confirmed) {
            $('.spinner-overlay').addClass('show');
            $('.spinner-text').text(currentLang === 'ru' ? 'Перезагрузка...' : 'Rebooting...');
            
            $.ajax({
                url: 'reboot.php',
                method: 'POST',
                success: function() {
                    // Wait for connection restoration after reboot
                    setTimeout(checkConnectionAfterReboot, 3000);
                },
                error: function() {
                    // If request failed, still wait for restoration
                    setTimeout(checkConnectionAfterReboot, 3000);
                }
            });
            }
        });
    });

    $('#shutdown-link').click(function(e) {
        e.preventDefault();
        customConfirm(translations[currentLang]['confirm_shutdown'], function(confirmed) {
            if (confirmed) {
            $('.spinner-overlay').addClass('show');
            $('.spinner-text').text(currentLang === 'ru' ? 'Выключение...' : 'Shutting down...');
            
            $.ajax({
                url: 'shutdown.php',
                method: 'POST',
                success: function() {
                    // Show completion message after 3 seconds
                    setTimeout(function() {
                        $('.spinner').hide();
                        $('.spinner-text').text(translations[currentLang]['shutdown_complete']);
                    }, 3000);
                },
                error: function() {
                    customAlert(currentLang === 'ru' ? 'Ошибка при выключении' : 'Shutdown error');
                    $('.spinner-overlay').removeClass('show');
                }
            });
            }
        });
    });

    // Function for checking connection after reboot
    function checkConnectionAfterReboot() {
        $.ajax({
            url: 'status_fast.php',
            method: 'GET',
            timeout: 2000,
            success: function() {
                // Connection restored, reload page
                location.reload();
            },
            error: function() {
                // Connection not yet restored, wait more
                setTimeout(checkConnectionAfterReboot, 2000);
            }
        });
    }

    // Volume Control
    let volumeSlider = document.getElementById('volume-slider');
    let volumeDisplay = document.getElementById('volume-display');
    let volumeIcon = document.getElementById('volume-icon');
    let isMuted = false;

    // Update volume from already obtained status_fast.php data (NOT a separate request!)
    function updateVolumeFromStatus(data) {
        // Handle volume and mute control availability separately
        let volumeControlsAvailable = true;
        let muteControlsAvailable = true;
        
        console.log('Volume update:', data.volume, 'available:', data.volume_control_available, 'changing:', isVolumeChanging);
        
        if (data.volume_control_available !== undefined) {
            volumeControlsAvailable = data.volume_control_available;
            if (volumeSlider) {
                volumeSlider.disabled = !volumeControlsAvailable;
                volumeSlider.style.opacity = volumeControlsAvailable ? '1' : '0.5';
                volumeSlider.style.cursor = volumeControlsAvailable ? 'pointer' : 'not-allowed';
            }
        }
        
        if (data.mute_control_available !== undefined) {
            muteControlsAvailable = data.mute_control_available;
            if (volumeIcon) {
                volumeIcon.style.opacity = muteControlsAvailable ? '1' : '0.5';
                volumeIcon.style.cursor = muteControlsAvailable ? 'pointer' : 'not-allowed';
                volumeIcon.style.pointerEvents = muteControlsAvailable ? 'auto' : 'none';
            }
        }
        
        // Always update volume display if volume data is available
        if (data.volume) {
            if (volumeControlsAvailable) {
                let volume = parseInt(data.volume.replace('%', ''));
                if (volumeSlider && volumeSlider.value != volume) {
                    volumeSlider.value = volume;
                }
                if (volumeDisplay) {
                    volumeDisplay.textContent = volume.toString();
                }
            } else {
                // For non-controllable DACs, show 100 in both display and slider
                if (volumeDisplay) {
                    volumeDisplay.textContent = '100';
                }
                if (volumeSlider && volumeSlider.value != 100) {
                    volumeSlider.value = 100;
                }
            }
        } else {
            // If no volume data available, show 100 (for DACs without volume controls)
            if (volumeDisplay && volumeDisplay.textContent === '--') {
                volumeDisplay.textContent = '100';
            }
            if (volumeSlider && volumeSlider.value != 100) {
                volumeSlider.value = 100;
            }
        }
        
        // Set correct icon depending on mute state
        if (volumeIcon) {
            const newMuted = data.muted || false;
            if (isMuted !== newMuted) {
                isMuted = newMuted;
                if (isMuted) {
                    volumeIcon.src = 'assets/img/mute.svg';
                    volumeIcon.title = 'Включить звук';
                } else {
                    volumeIcon.src = 'assets/img/volume.svg';
                    volumeIcon.title = 'Громкость';
                }
            }
        }
    }

    // Set volume
    function setVolume(volume) {
        isVolumeChanging = true; // Block volume updates
        
        fetch('volume.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=set_volume&volume=' + volume
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                volumeDisplay.textContent = volume;
            }
            // Unblock after 1 second to give system time to update
            setTimeout(() => {
                isVolumeChanging = false;
            }, 1000);
        })
        .catch(error => {
            console.error('Error setting volume:', error);
            // Unblock even on error
            setTimeout(() => {
                isVolumeChanging = false;
            }, 1000);
        });
    }

    // Toggle mute
    function toggleMute() {
        isVolumeChanging = true; // Block volume updates
        
        fetch('volume.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=toggle_mute'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                isMuted = data.muted;
                if (isMuted) {
                    volumeIcon.src = 'assets/img/mute.svg';
                    volumeIcon.title = 'Включить звук';
                } else {
                    volumeIcon.src = 'assets/img/volume.svg';
                    volumeIcon.title = 'Громкость';
                }
            }
            // Unblock after 1 second
            setTimeout(() => {
                isVolumeChanging = false;
            }, 1000);
        })
        .catch(error => {
            console.error('Error toggling mute:', error);
            // Unblock even on error
            setTimeout(() => {
                isVolumeChanging = false;
            }, 1000);
        });
    }

    // Volume slider event listener
    if (volumeSlider) {
        volumeSlider.addEventListener('input', function() {
            let volume = this.value;
            volumeDisplay.textContent = volume;
            // Block updates while user moves slider
            isVolumeChanging = true;
        });

        volumeSlider.addEventListener('change', function() {
            let volume = this.value;
            setVolume(volume); // setVolume manages blocking itself
        });

        // Initial state will load through polling
    }

    // Volume icon click listener
    if (volumeIcon) {
        volumeIcon.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMute();
        });
    }
    
    // Clear intervals when leaving page
    window.addEventListener('beforeunload', function() {
        if (statusInterval) {
            clearInterval(statusInterval);
        }
    });
    
    // Initialize polling system
    function startPolling() {
        // First status check
        forceStatusCheck();
        
        // Regular polling every 3 seconds - use fast request
        statusInterval = setInterval(function() {
            if (!isServiceSwitching && !isVolumeChanging) {
                // Normal monitoring through fast status_fast.php (C-monitor)
                $.ajax({
                    url: 'status_fast.php',
                    method: 'GET',
                    timeout: 3000,
                    dataType: 'json',
                    success: function(response) {
                        updateInterfaceFromStatus(response);
                    }
                });
            }
        }, POLLING_CONFIG.NORMAL_INTERVAL);
    }
    
    // Start polling 2 seconds after loading
    setTimeout(startPolling, 2000);
});
/* Cache bust version: 1753367743 */
