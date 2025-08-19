<?php
header('Content-Type: application/json; charset=utf-8');

// CORRECT JSON reading from C-monitor
$status_file = '/tmp/system_status.json';

// First try to read file from C-monitor
if (file_exists($status_file)) {
    $age = time() - filemtime($status_file);
    
    // Increase time to 10 seconds for debugging
    if ($age < 10) {
        $content = file_get_contents($status_file);
        if ($content) {
            $decoded = json_decode($content, true);
            if ($decoded && is_array($decoded)) {
                // Check that all required fields are present
                if (isset($decoded['active_service'], $decoded['alsa_state'], $decoded['usb_dac'], $decoded['volume'], $decoded['muted'])) {
                    // Override control availability for USB mode without physical DAC
                    if ($decoded['alsa_state'] === 'usb' && !$decoded['usb_dac']) {
                        // USB mode but no physical USB DAC - disable controls
                        $decoded['volume'] = '100%';
                        $decoded['volume_control_available'] = false;
                        $decoded['mute_control_available'] = false;
                        $decoded['muted'] = false;
                    }
                    
                    // C-monitor now provides control availability, but add fallback for older versions
                    if (!isset($decoded['volume_control_available'])) {
                        if ($decoded['alsa_state'] === 'usb' && $decoded['usb_dac']) {
                            // Cache file for USB control check
                            $usb_controls_cache = '/tmp/usb_controls_cache';
                            $use_cache = false;
                            
                            if (file_exists($usb_controls_cache)) {
                                $cache_age = time() - filemtime($usb_controls_cache);
                                if ($cache_age < 300) { // Cache for 5 minutes
                                    $cached_result = file_get_contents($usb_controls_cache);
                                    $cache_data = explode('|', $cached_result);
                                    $volume_found = ($cache_data[0] === '1');
                                    $mute_found = isset($cache_data[1]) ? ($cache_data[1] === '1') : false;
                                    $decoded['volume_control_available'] = $volume_found;
                                    $decoded['mute_control_available'] = $mute_found;
                                    
                                    // If no working volume controls, force volume to 100%
                                    if (!$volume_found) {
                                        $decoded['volume'] = '100%';
                                        $decoded['muted'] = false;
                                    }
                                    
                                    $use_cache = true;
                                }
                            }
                            
                            if (!$use_cache) {
                                // Check if USB DAC has working volume controls
                                $card_number = 1; // USB card
                                $volume_controls = ['PCM', 'Master'];
                                $volume_found = false;
                                $mute_found = false;
                                
                                // Get available controls and try them
                                exec('/usr/bin/sudo /usr/bin/amixer -c ' . $card_number . ' scontrols 2>/dev/null', $controls_output, $controls_code);
                                if ($controls_code === 0 && !empty($controls_output)) {
                                    foreach ($controls_output as $control_line) {
                                        if (preg_match("/Simple mixer control '([^']+)',/", $control_line, $control_matches)) {
                                            $control_name = $control_matches[1];
                                            $name_lower = strtolower($control_name);
                                            
                                            // Skip technical controls that aren't real volume controls
                                            if (strpos($name_lower, 'clock') !== false ||
                                                strpos($name_lower, 'rate') !== false ||
                                                strpos($name_lower, 'selector') !== false ||
                                                strpos($name_lower, 'source') !== false ||
                                                strpos($name_lower, 'format') !== false ||
                                                strpos($name_lower, 'jack') !== false ||
                                                strpos($name_lower, 'channel') !== false) {
                                                continue; // Skip these technical controls
                                            }
                                            
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
                                }
                                
                                // Test if any controls actually work for audio volume (not technical controls)
                                foreach ($volume_controls as $control) {
                                    // Skip known technical controls
                                    $control_lower = strtolower($control);
                                    if (strpos($control_lower, 'clock') !== false ||
                                        strpos($control_lower, 'rate') !== false ||
                                        strpos($control_lower, 'selector') !== false) {
                                        continue;
                                    }
                                    
                                    exec('/usr/bin/sudo /usr/bin/amixer -c ' . $card_number . ' sget "' . $control . '" 2>/dev/null', $volume_output, $volume_code);
                                    if ($volume_code === 0 && !empty($volume_output)) {
                                        $volume_line = implode(' ', $volume_output);
                                        
                                        // Check if this is a real audio volume control
                                        $has_pvolume = (strpos($volume_line, 'Capabilities:') !== false && 
                                                       strpos($volume_line, 'pvolume') !== false);
                                        $has_audio_channels = (strpos($volume_line, 'Playback channels: Front Left - Front Right') !== false ||
                                                              strpos($volume_line, 'Playback channels: Mono') !== false ||
                                                              strpos($volume_line, 'Playback channels: Left - Right') !== false);
                                        $has_limits = strpos($volume_line, 'Limits:') !== false;
                                        
                                        // Real audio volume control must have pvolume capability AND proper audio channels AND limits
                                        if ($has_pvolume && $has_audio_channels && $has_limits) {
                                            $volume_found = true;
                                        }
                                        
                                        if (strpos($volume_line, '[off]') !== false || strpos($volume_line, '[on]') !== false) {
                                            $mute_found = true;
                                        }
                                        
                                        if ($volume_found) break;
                                    }
                                }
                                
                                $decoded['volume_control_available'] = $volume_found;
                                $decoded['mute_control_available'] = $mute_found;
                                
                                // If no working controls, force volume to 100%
                                if (!$volume_found) {
                                    $decoded['volume'] = '100%';
                                    $decoded['muted'] = false;
                                }
                                
                                // Cache the result
                                file_put_contents($usb_controls_cache, ($volume_found ? '1' : '0') . '|' . ($mute_found ? '1' : '0'));
                            }
                        } else {
                            // I2S - assume controls are available
                            $decoded['volume_control_available'] = true;
                            $decoded['mute_control_available'] = true;
                        }
                    }
                    
                    // Add marker that data is from C-monitor
                    $decoded['source'] = 'c_monitor';
                    echo json_encode($decoded);
                    exit;
                }
            }
        }
    }
}

// Fallback - if C-monitor not working, use old PHP approach
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
    'muted' => false,
    'source' => 'php_fallback'
];

// Determine active service
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

// ALSA state check
$output_file = '/etc/output';
if (file_exists($output_file)) {
    $output_content = trim(file_get_contents($output_file));
    if (strtoupper($output_content) === 'USB') {
        $status['alsa_state'] = 'usb';
    } elseif (strtoupper($output_content) === 'I2S') {
        $status['alsa_state'] = 'i2s';
    }
}

// USB DAC check
$status['usb_dac'] = file_exists('/sys/class/sound/card1');

// Get volume - try available controls
// Determine which card to check based on ALSA state and physical USB DAC presence
$card_number = ($status['alsa_state'] === 'usb' && $status['usb_dac']) ? 1 : 0;

$volume_controls = ['PCM', 'Master'];

// Get available controls and try them on the active card
exec('/usr/bin/sudo /usr/bin/amixer -c ' . $card_number . ' scontrols 2>/dev/null', $controls_output, $controls_code);
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
        exec('/usr/bin/sudo /usr/bin/amixer -c ' . $card_number . ' sget "' . $control . '" 2>/dev/null', $volume_output, $volume_code);
        if ($volume_code === 0 && !empty($volume_output)) {
            $volume_line = implode(' ', $volume_output);
            
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

// Check control availability  
if ($status['alsa_state'] === 'usb' && !$status['usb_dac']) {
    // USB mode but no physical USB DAC - disable controls
    $status['volume'] = '100%';
    $status['volume_control_available'] = false;
    $status['mute_control_available'] = false;
    $status['muted'] = false;
} elseif (empty($volume_controls) && $status['usb_dac'] && $status['alsa_state'] === 'usb') {
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

echo json_encode($status);
?>