<?php
header('Content-Type: application/json; charset=utf-8');

// Self-cleaning cache for systems without cron
$cache_file = '/tmp/combined_status_cache';
$cache_ttl = 3; // Cache TTL in seconds
$cleanup_chance = 10; // 10% probability of cleaning old files

// Cache self-cleanup (performed randomly to not burden every request)
if (rand(1, 100) <= $cleanup_chance) {
    $old_files = glob('/tmp/*_cache');
    $now = time();
    foreach ($old_files as $file) {
        if (file_exists($file) && ($now - filemtime($file)) > 3600) { // Delete files older than hour
            unlink($file);
        }
    }
}

$services = [
    'naa'         => 'networkaudiod',
    'raat'        => 'raat_app',
    'mpd'         => 'mpd', 
    'squeeze2upn' => 'squeeze2upnp',
    'aprenderer'  => 'ap2renderer',
    'aplayer'     => 'aplayer',
    'apscream'    => 'apscream',
    'lms'         => 'squeezelite',
    'shairport'   => 'shairport-sync',
    'spotify'     => 'librespot',
    'qobuz'       => 'qobuz-connect',
    'tidalconnect'=> 'tidalconnect',
];

$status = [
    'active_service' => '',
    'alsa_state' => 'unknown',
    'usb_dac' => false,
    'volume' => '100%',
    'muted' => false
];

// Check current cache ONLY for service data
$cached_services = null;
if (file_exists($cache_file)) {
    $cache_age = time() - filemtime($cache_file);
    if ($cache_age < $cache_ttl) {
        $cache_content = file_get_contents($cache_file);
        if ($cache_content) {
            $cached_services = json_decode($cache_content, true);
            // Use cached service data, but NOT volume data
            if ($cached_services) {
                $status['active_service'] = $cached_services['active_service'];
                $status['alsa_state'] = $cached_services['alsa_state'];
                $status['usb_dac'] = $cached_services['usb_dac'];
            }
        }
    }
}

// Check service data only if no fresh cache
if (!$cached_services) {
    // One command to check all processes - maximum efficiency
    $all_processes = implode('|', array_values($services));
    $output = shell_exec("ps -eo comm | grep -E '^($all_processes)$' | head -1");

    if (!empty($output)) {
        $found_process = trim($output);
        foreach ($services as $key => $process) {
            if ($found_process === $process) {
                $status['active_service'] = $key;
                break;
            }
        }
    }

    // ALSA check - first via /etc/output, then via /etc/asound.conf
    $output_file = '/etc/output';
    if (file_exists($output_file)) {
        $output_content = trim(file_get_contents($output_file));
        if (strtoupper($output_content) === 'USB') {
            $status['alsa_state'] = 'usb';
        } elseif (strtoupper($output_content) === 'I2S') {
            $status['alsa_state'] = 'i2s';
        }
    } else {
        // Fallback via /etc/asound.conf
        $asound_conf = '/etc/asound.conf';
        if (file_exists($asound_conf)) {
            $content = file_get_contents($asound_conf);
            if (strpos($content, 'card 1') !== false) {
                $status['alsa_state'] = 'usb';
            } elseif (strpos($content, 'card 0') !== false) {
                $status['alsa_state'] = 'i2s';
            }
        }
    }

    // USB DAC check independent of ALSA state
    // USB device may be connected but not active in configuration
    $status['usb_dac'] = file_exists('/sys/class/sound/card1');
}

// Get volume information - ALWAYS fresh data
// Try common volume controls in order of preference
$volume_controls = ['PCM', 'Master'];

// Get available controls and try them
exec('/usr/bin/sudo /usr/bin/amixer -c $card_number scontrols 2>/dev/null', $controls_output, $controls_code);
if ($controls_code === 0 && !empty($controls_output)) {
    foreach ($controls_output as $control_line) {
        if (preg_match("/Simple mixer control '([^']+)',/", $control_line, $control_matches)) {
            $control_name = $control_matches[1];
            // Case-insensitive search for volume-related keywords (NOT switch - that's mute)
            $name_lower = strtolower($control_name);
            if (strpos($name_lower, 'volume') !== false || 
                strpos($name_lower, 'playback') !== false ||
                strpos($name_lower, 'master') !== false ||
                strpos($name_lower, 'speaker') !== false ||
                strpos($name_lower, 'headphone') !== false ||
                strpos($name_lower, 'dac') !== false ||
                !in_array($control_name, $volume_controls)) {
                $volume_controls[] = $control_name;
            }
        }
    }
} else {
    // No simple controls available - this is a USB DAC without mixer controls
    $volume_controls = [];
}

$volume_found = false;
$mute_found = false;
$fallback_50_percent_control = null;

// If no controls available, skip the loop
if (empty($volume_controls)) {
    $volume_found = false;
    $mute_found = false;
} else {
    foreach ($volume_controls as $control) {
        exec('/usr/bin/sudo /usr/bin/amixer -c $card_number sget "' . $control . '" 2>/dev/null', $volume_output, $volume_code);
        if ($volume_code === 0 && !empty($volume_output)) {
            $volume_line = implode(' ', $volume_output);
            
            // Get volume level
            if (preg_match('/\[(\d+)%\]/', $volume_line, $volume_matches)) {
                $volume_percent = intval($volume_matches[1]);
                
                // Skip controls that show exactly 50% (likely non-functional)
                if ($volume_percent == 50) {
                    $fallback_50_percent_control = $control;
                    // Don't break, keep looking for a real control
                } else {
                    $status['volume'] = $volume_percent . '%';
                    $volume_found = true;
                }
            }
            
            // Check mute state (separate from volume)
            if (strpos($volume_line, '[off]') !== false || strpos($volume_line, '[on]') !== false) {
                $status['muted'] = (strpos($volume_line, '[off]') !== false);
                $mute_found = true;
            }
            
            if ($volume_found) break;
        }
    }
}


// Debug: check volume_controls content
$status['debug_volume_controls'] = $volume_controls;
$status['debug_empty'] = empty($volume_controls);

// Check if this is a USB DAC without mixer controls
if (empty($volume_controls) && $status['usb_dac'] && $status['alsa_state'] === 'usb') {
    // No mixer controls at all - typical for USB DACs without hardware volume
    $status['volume'] = '100%';
    $status['volume_control_available'] = false;
    $status['mute_control_available'] = false;
    $status['muted'] = false;
} elseif (!$volume_found && $fallback_50_percent_control && $status['usb_dac'] && $status['alsa_state'] === 'usb') {
    // Only 50% controls found - likely non-functional
    $status['volume'] = '100%';
    $status['volume_control_available'] = false;
    $status['mute_control_available'] = false;
    $status['muted'] = false;
} else {
    $status['volume_control_available'] = $volume_found;
    $status['mute_control_available'] = $mute_found;
}

// Save to cache ONLY service data (without volume) if data was updated
if (!$cached_services) {
    $cache_data = [
        'active_service' => $status['active_service'],
        'alsa_state' => $status['alsa_state'],
        'usb_dac' => $status['usb_dac']
    ];
    file_put_contents($cache_file, json_encode($cache_data), LOCK_EX);
}

// Return full response with current volume data
$json_response = json_encode($status);

echo $json_response;
?>