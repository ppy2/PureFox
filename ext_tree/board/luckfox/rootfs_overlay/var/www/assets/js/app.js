$(document).ready(function () {
    // ОПТИМИЗИРОВАННЫЕ интервалы опроса (сохраняем весь функционал!)
    const POLLING_CONFIG = {
        ACTIVE: 8000,        // 8 сек когда пользователь активен
        IDLE: 20000,         // 20 сек когда неактивен
        HIDDEN: 60000,       // 1 мин для скрытых вкладок
        SWITCHING: 500,      // 0.5 сек при переключении
        MONITORING: 2000,    // 2 сек для мониторинга
        USER_TIMEOUT: 45000, // 45 сек до неактивного состояния
        INITIAL_DELAY: 2000  // 2 сек начальная задержка после команды переключения
    };

    // Глобальные переменные (сохраняем все!)
    window.serviceMonitorInterval = null;
    let userActiveTimeout = null;
    let isUserActive = true;
    let previousActiveService = null;
    let isPageVisible = true;
    let lastStatusCheck = null; // ДОБАВИЛИ кэш последней проверки

    // Динамическая установка ссылок на основе текущего хоста (СОХРАНЕНО!)
    var currentHost = window.location.hostname;
    $('button[data-service="aprenderer"] .settings-link').attr('href', 'http://' + currentHost + ':7779');
    $('button[data-service="aplayer"] .settings-link').attr('href', 'http://' + currentHost + ':7778');

    // Языковые словари (ПОЛНОСТЬЮ СОХРАНЕНЫ!)
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
            'usb_dac_missing': 'USB ЦАП не обнаружен. Пожалуйста, подключите USB ЦАП'
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
            'usb_dac_missing': 'USB DAC not detected. Please connect USB DAC'
        }
    };

    // Определение языка браузера (СОХРАНЕНО!)
    function detectLanguage() {
        const lang = navigator.language || navigator.userLanguage;
        return lang.startsWith('ru') ? 'ru' : 'en';
    }

    // Применение языка (СОХРАНЕНО!)
    const currentLang = detectLanguage();
    const i2sLink = currentLang === 'ru' ? 'i2s_ru.php' : 'i2s_en.php';
    $('#i2s-settings-link').attr('href', i2sLink);

    function applyTranslations() {
        $('[data-lang]').each(function() {
            const key = $(this).data('lang');
            if (translations[currentLang][key] !== undefined) {
                if (key !== 'settings' || !$(this).find('img').length) {
                    $(this).text(translations[currentLang][key]);
                }
            }
        });
    }

    applyTranslations();

    // Адаптивные стили (ПОЛНОСТЬЮ СОХРАНЕНЫ!)
    function setResponsiveStyles() {
        if (window.innerWidth < 500) {
            $('.player-buttons, .ap-buttons').css({
                'display': 'block',
                'gap': '0'
            });
            $('.player-buttons .btn-custom, .ap-buttons .btn-custom').css({
                'width': '100%',
                'margin-top': '10px',
                'margin-bottom': '10px'
            });
            $('.alsa-button').css('padding', '10px');
        } else {
            $('.player-buttons, .ap-buttons').css({
                'display': 'flex',
                'gap': '10px'
            });
            $('.player-buttons .btn-custom, .ap-buttons .btn-custom').css({
                'width': 'calc(50% - 5px)',
                'margin-top': '10px',
                'margin-bottom': '10px'
            });
            $('.alsa-button').css('padding', '10px 20px');
        }
    }

    setResponsiveStyles();
    $(window).resize(function() {
        setResponsiveStyles();
    });

    // ОПТИМИЗИРОВАННОЕ отслеживание активности пользователя
    function trackUserActivity() {
        const wasActive = isUserActive;
        isUserActive = true;
        clearTimeout(userActiveTimeout);
        userActiveTimeout = setTimeout(() => {
            isUserActive = false;
        }, POLLING_CONFIG.USER_TIMEOUT);
        
        // Если пользователь стал активным, ускоряем проверки
        if (!wasActive) {
            reschedulePolling();
        }
    }

    // Отслеживание видимости страницы (ОПТИМИЗИРОВАННОЕ)
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        if (isPageVisible) {
            // Страница стала видимой - быстрая проверка состояния
            checkActiveService();
            checkAlsaState();
        }
        reschedulePolling();
    });

    // События активности пользователя (СОХРАНЕНЫ)
    $(document).on('mousemove click keypress', trackUserActivity);
    trackUserActivity();

    // Управление спиннером (СОХРАНЕНО!)
    function showSpinner() {
        $('.spinner-overlay').css('display', 'flex');
    }

    function hideSpinner() {
        $('.spinner-overlay').css('display', 'none');
    }

    // ОПТИМИЗИРОВАННАЯ функция получения интервала
    function getOptimalPollingInterval() {
        if (!isPageVisible) return POLLING_CONFIG.HIDDEN;
        return isUserActive ? POLLING_CONFIG.ACTIVE : POLLING_CONFIG.IDLE;
    }

    // ОПТИМИЗИРОВАННАЯ проверка USB DAC с кэшированием (БЕЗ отдельного запроса!)
    function checkUsbDac(successCallback, errorCallback) {
        // Используем кэшированные данные если есть
        if (lastStatusCheck && (Date.now() - lastStatusCheck.timestamp) < 3000) {
            if (lastStatusCheck.usb_dac) {
                if (successCallback) successCallback();
            } else {
                alert(translations[currentLang]['usb_dac_missing']);
                if (errorCallback) errorCallback();
            }
            return;
        }
        
        // Получаем актуальные данные через объединенный запрос
        $.ajax({
            url: 'status.php',
            method: 'GET',
            timeout: 3000,
            dataType: 'json',
            success: function(response) {
                // Обновляем кэш
                lastStatusCheck = {
                    service: response.active_service || '',
                    alsa: response.alsa_state,
                    usb_dac: response.usb_dac,
                    timestamp: Date.now()
                };
                
                if (response.usb_dac) {
                    if (successCallback) successCallback();
                } else {
                    alert(translations[currentLang]['usb_dac_missing']);
                    if (errorCallback) errorCallback();
                }
            },
            error: function() {
                if (errorCallback) errorCallback();
                else alert(translations[currentLang]['service_error']);
            }
        });
    }

    // ОПТИМИЗИРОВАННАЯ проверка состояния сервисов с кэшированием
    function checkActiveService(callback) {
        const now = Date.now();
        // Используем кэш если прошло меньше 2 секунд
        if (lastStatusCheck && (now - lastStatusCheck.timestamp) < 2000) {
            if (callback) callback(lastStatusCheck.service);
            return;
        }

        $.ajax({
            url: 'status.php', // ИСПОЛЬЗУЕМ ОБЪЕДИНЕННЫЙ ЗАПРОС!
            method: 'GET',
            timeout: 3000,
            dataType: 'json',
            success: function(response) {
                const activeService = response.active_service || '';
                
                // Кэшируем результат
                lastStatusCheck = {
                    service: activeService,
                    alsa: response.alsa_state,
                    usb_dac: response.usb_dac,
                    timestamp: now
                };
                
                // Обновляем UI только если состояние изменилось
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
                
                // Также обновляем ALSA состояние
                updateAlsaUI(response.alsa_state);
                
                if (callback) callback(activeService);
            },
            error: function() {
                console.warn('Ошибка проверки состояния, повтор через', POLLING_CONFIG.IDLE, 'мс');
                // При ошибке увеличиваем интервал
                setTimeout(() => reschedulePolling(), POLLING_CONFIG.IDLE);
            }
        });
    }

    // НОВАЯ функция для обновления ALSA UI
    function updateAlsaUI(alsaState) {
        $('.alsa-button').removeClass('active');
        $('.alsa-toggle').removeClass('active-i2s');
        switch (alsaState) {
            case 'usb':
                $('#alsa-usb').addClass('active');
                break;
            case 'i2s':
                $('#alsa-i2s').addClass('active');
                $('.alsa-toggle').addClass('active-i2s');
                break;
            case 'error':
                console.error('Ошибка при чтении конфигурации ALSA');
                break;
        }
    }

    // ОПТИМИЗИРОВАННАЯ проверка состояния ALSA (теперь через объединенный запрос)
    function checkAlsaState() {
        // Используем кэшированные данные если есть
        if (lastStatusCheck && (Date.now() - lastStatusCheck.timestamp) < 2000) {
            updateAlsaUI(lastStatusCheck.alsa);
            return;
        }
        
        // Иначе вызываем checkActiveService, который обновит и ALSA
        checkActiveService();
    }

    // Планирование следующей проверки с оптимальным интервалом
    function reschedulePolling() {
        clearTimeout(window.serviceMonitorInterval);
        const interval = getOptimalPollingInterval();
        window.serviceMonitorInterval = setTimeout(() => {
            checkActiveService();
            reschedulePolling(); // Рекурсивно планируем следующую
        }, interval);
    }

    // Обработка кликов по кнопкам ALSA (ПОЛНОСТЬЮ СОХРАНЕНА!)
    $('.alsa-button').click(function() {
        const cardType = $(this).attr('id').replace('alsa-', '');
        if ($(this).hasClass('active')) return;

        trackUserActivity(); // Пользователь активен

        if (cardType === 'usb') {
            checkUsbDac(
                function() { switchAlsa(cardType); },
                function() { alert(translations[currentLang]['alsa_error']); }
            );
        } else {
            switchAlsa(cardType);
        }
    });

    // ИСПРАВЛЕННАЯ функция переключения ALSA (увеличена начальная задержка)
    function switchAlsa(cardType) {
        showSpinner();
        clearTimeout(window.serviceMonitorInterval); // Останавливаем polling на время переключения
        
        $.ajax({
            url: 'handle_alsa.php',
            method: 'POST',
            data: { card: cardType },
            timeout: 10000,
            success: function() {
                // ДОБАВЛЕНА начальная задержка перед первой проверкой
                setTimeout(() => {
                    function checkAlsaStateChange() {
                        $.ajax({
                            url: 'status.php', // Используем объединенный запрос!
                            method: 'GET',
                            timeout: 3000,
                            dataType: 'json',
                            success: function(response) {
                                const newState = response.alsa_state;
                                if (newState === cardType) {
                                    // Обновляем кэш
                                    lastStatusCheck = {
                                        service: response.active_service || '',
                                        alsa: response.alsa_state,
                                        usb_dac: response.usb_dac,
                                        timestamp: Date.now()
                                    };
                                    updateAlsaUI(newState);
                                    hideSpinner();
                                    reschedulePolling(); // Возобновляем polling
                                } else if (newState === 'error') {
                                    hideSpinner();
                                    alert(translations[currentLang]['alsa_error']);
                                    reschedulePolling();
                                } else {
                                    setTimeout(checkAlsaStateChange, 500);
                                }
                            },
                            error: function() {
                                hideSpinner();
                                alert(translations[currentLang]['alsa_error']);
                                reschedulePolling();
                            }
                        });
                    }
                    checkAlsaStateChange();
                }, POLLING_CONFIG.INITIAL_DELAY); // Ждем 2 секунды перед первой проверкой
            },
            error: function() {
                hideSpinner();
                alert(translations[currentLang]['alsa_error']);
                reschedulePolling();
            }
        });
    }

    // Обработка кликов по кнопкам сервисов (ПОЛНОСТЬЮ СОХРАНЕНА!)
    $('.btn-custom').click(function(e) {
        if ($(e.target).is('a') || $(e.target).is('img')) return true;
        if (!$(this).data('service') || $(this).hasClass('active')) return;

        const service = $(this).data('service');
        trackUserActivity(); // Пользователь активен
        
        // Используем кэшированное состояние ALSA
        const alsaState = lastStatusCheck ? lastStatusCheck.alsa : null;
        if (alsaState === 'usb') {
            checkUsbDac(function() { switchPlayerService(service); });
        } else {
            switchPlayerService(service);
        }
    });

    // ИСПРАВЛЕННАЯ функция переключения сервиса (увеличена начальная задержка!)
    function switchPlayerService(service) {
        showSpinner();
        clearTimeout(window.serviceMonitorInterval); // Останавливаем polling
        
        $.ajax({
            url: 'handle_service.php',
            method: 'POST',
            data: { service: service },
            timeout: 15000,
            success: function() {
                console.log('Команда переключения на', service, 'отправлена, ждем', POLLING_CONFIG.INITIAL_DELAY, 'мс...');
                
                // ДОБАВЛЕНА начальная задержка - даем сервису время запуститься!
                setTimeout(() => {
                    const checkInterval = POLLING_CONFIG.SWITCHING;
                    let checkCount = 0;
                    const maxChecks = 60; // 30 секунд максимум

                    function checkServiceStatusChange() {
                        $.ajax({
                            url: 'status.php', // Используем оптимизированный запрос
                            method: 'GET',
                            timeout: 3000,
                            dataType: 'json',
                            success: function(response) {
                                const activeService = response.active_service || '';
                                checkCount++;
                                
                                console.log('Проверка', checkCount, ': активный сервис =', activeService, ', ожидаемый =', service);
                                
                                if (!activeService) {
                                    console.warn('Нет активных сервисов, продолжаем проверку...');
                                    if (checkCount >= maxChecks) {
                                        $('.btn-custom').removeClass('active');
                                        hideSpinner();
                                        alert(translations[currentLang]['service_error']);
                                        reschedulePolling();
                                        return;
                                    }
                                    setTimeout(checkServiceStatusChange, checkInterval);
                                    return;
                                }
                                
                                if (activeService === service) {
                                    console.log('Успешно переключен на', service);
                                    $('.btn-custom').removeClass('active');
                                    $(`button[data-service="${service}"]`).addClass('active');
                                    hideSpinner();
                                    startServiceMonitoring(service);
                                } else if (activeService !== service) {
                                    console.log("Активирован сервис " + activeService + " вместо " + service);
                                    $('.btn-custom').removeClass('active');
                                    $(`button[data-service="${activeService}"]`).addClass('active');
                                    hideSpinner();
                                    startServiceMonitoring(activeService);
                                } else if (checkCount >= maxChecks) {
                                    console.error("Тайм-аут при переключении на сервис " + service);
                                    $('.btn-custom').removeClass('active');
                                    hideSpinner();
                                    alert(translations[currentLang]['service_error']);
                                    reschedulePolling();
                                } else {
                                    setTimeout(checkServiceStatusChange, checkInterval);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Ошибка проверки состояния:', status, error);
                                $('.btn-custom').removeClass('active');
                                hideSpinner();
                                alert(translations[currentLang]['service_error']);
                                reschedulePolling();
                            }
                        });
                    }

                    // СОХРАНЕНА функция мониторинга после переключения
                    function startServiceMonitoring(serviceToMonitor) {
                        let monitorCount = 0;
                        const maxMonitorCount = 15; // 30 секунд мониторинга
                        
                        function monitorService() {
                            monitorCount++;
                            checkActiveService(function(activeService) {
                                if (!activeService || activeService !== serviceToMonitor || monitorCount >= maxMonitorCount) {
                                    console.log('Завершаем мониторинг сервиса', serviceToMonitor);
                                    reschedulePolling(); // Возобновляем нормальный polling
                                } else {
                                    setTimeout(monitorService, POLLING_CONFIG.MONITORING);
                                }
                            });
                        }
                        monitorService();
                    }

                    checkServiceStatusChange();
                }, POLLING_CONFIG.INITIAL_DELAY); // Ждем 2 секунды перед первой проверкой
            },
            error: function() {
                hideSpinner();
                alert(translations[currentLang]['service_error']);
                reschedulePolling();
            }
        });
    }

    // Инициализация (СОХРАНЕНА!)
    checkActiveService(function(activeService) {
        previousActiveService = activeService;
    });
    checkAlsaState();
    
    // Запускаем оптимизированный polling
    reschedulePolling();

    // ПОЛНОСТЬЮ СОХРАНЕНА функция обновления прошивки!
    $('#update-firmware').click(function(e) {
        if ($(e.target).is('span')) return;
        if (!confirm(translations[currentLang]['confirm_update'])) return;

        $('#update-log-modal').show();
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

    // СОХРАНЕНА функция закрытия модального окна
    $('.close-modal').click(function() {
        $('#update-log-modal').fadeOut();
    });
});
