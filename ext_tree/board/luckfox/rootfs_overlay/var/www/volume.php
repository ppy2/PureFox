<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Function to get the first working volume control with priority ranking
function getWorkingVolumeControl() {
    $high_priority = ['PCM', 'Master'];
    $medium_priority = [];
    $low_priority = [];
    
    // Get available controls
    exec('/usr/bin/sudo /usr/bin/amixer scontrols 2>/dev/null', $controls, $return_code);
    if ($return_code === 0 && !empty($controls)) {
        foreach ($controls as $control_line) {
            if (preg_match("/Simple mixer control '([^']+)',/", $control_line, $control_matches)) {
                $control_name = $control_matches[1];
                $name_lower = strtolower($control_name);
                
                // Skip if already in high priority
                if (in_array($control_name, $high_priority)) continue;
                
                // Check actual capabilities using amixer sget, not just name
                exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control_name . '" 2>/dev/null', $control_output, $control_code);
                if ($control_code === 0 && !empty($control_output)) {
                    $control_text = implode(' ', $control_output);
                    
                    // High priority: has volume capabilities
                    if (preg_match('/\[(\d+%|\d+dB)\]/', $control_text)) {
                        $high_priority[] = $control_name;
                    }
                    // Also check by name patterns
                    elseif (strpos($name_lower, 'volume') !== false) {
                        $high_priority[] = $control_name;
                    }
                    // Medium priority: main output controls
                    elseif (strpos($name_lower, 'playback') !== false ||
                            strpos($name_lower, 'master') !== false ||
                            strpos($name_lower, 'speaker') !== false ||
                            strpos($name_lower, 'headphone') !== false) {
                        $medium_priority[] = $control_name;
                    }
                    // Low priority: DAC-specific or other controls  
                    elseif (strpos($name_lower, 'dac') !== false ||
                            strpos($name_lower, 'output') !== false ||
                            strpos($name_lower, 'digital') !== false) {
                        $low_priority[] = $control_name;
                    }
                }
            }
        }
    }
    
    // Test controls in priority order
    $all_controls = array_merge($high_priority, $medium_priority, $low_priority);
    
    foreach ($all_controls as $control) {
        exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control . '" 2>/dev/null', $test_output, $test_code);
        if ($test_code === 0 && !empty($test_output)) {
            $output_text = implode(' ', $test_output);
            // Check if this control actually supports volume (has percentage or dB values)
            if (preg_match('/\[(\d+%|\d+dB)\]/', $output_text)) {
                // Additional check: make sure it's not just a fixed value (some controls show [0%] always)
                if (preg_match('/\[(\d+)%\]/', $output_text, $matches) && intval($matches[1]) > 0) {
                    return $control;
                }
                // Or if it has dB values, it's likely real
                elseif (strpos($output_text, 'dB') !== false) {
                    return $control;
                }
            }
        }
    }
    
    // Final fallback: return first working control even with 0% 
    foreach ($all_controls as $control) {
        exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control . '" 2>/dev/null', $test_output, $test_code);
        if ($test_code === 0 && !empty($test_output)) {
            $output_text = implode(' ', $test_output);
            if (preg_match('/\[(\d+%|\d+dB)\]/', $output_text)) {
                return $control;
            }
        }
    }
    
    return 'PCM'; // ultimate fallback
}

// Function to get the first working mute control
function getWorkingMuteControl() {
    $mute_controls = ['PCM', 'Master'];
    
    // Get available controls
    exec('/usr/bin/sudo /usr/bin/amixer scontrols 2>/dev/null', $controls, $return_code);
    if ($return_code === 0 && !empty($controls)) {
        foreach ($controls as $control_line) {
            if (preg_match("/Simple mixer control '([^']+)',/", $control_line, $control_matches)) {
                $control_name = $control_matches[1];
                // Optimized mute control search
                $name_lower = strtolower($control_name);
                if ($name_lower === 'pcm' || $name_lower === 'master' || 
                    $name_lower === 'speaker' || $name_lower === 'headphone' || 
                    $name_lower === 'dac' || $name_lower === 'audio' ||
                    strpos($name_lower, 'switch') !== false ||
                    strpos($name_lower, 'playback') !== false ||
                    strpos($name_lower, 'volume') !== false ||
                    !in_array($control_name, $mute_controls)) {
                    $mute_controls[] = $control_name;
                }
            }
        }
    }
    
    // Test each control to find working one with mute capability
    foreach ($mute_controls as $control) {
        exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control . '" 2>/dev/null', $test_output, $test_code);
        if ($test_code === 0 && !empty($test_output)) {
            $output_text = implode(' ', $test_output);
            // Check if this control supports mute/unmute (has [on]/[off] states)
            if (preg_match('/\[(on|off)\]/', $output_text)) {
                return $control;
            }
        }
    }
    
    return 'PCM'; // fallback
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'volume_up':
        $control = getWorkingVolumeControl();
        exec('/usr/bin/sudo /usr/bin/amixer -q sset "' . $control . '" 5%+ 2>/dev/null', $output, $return_code);
        // Send D-Bus signal about volume change
        shell_exec('/opt/dbus_notify VolumeChanged "volume_up" 2>/dev/null &');
        break;
        
    case 'volume_down':
        $control = getWorkingVolumeControl();
        exec('/usr/bin/sudo /usr/bin/amixer -q sset "' . $control . '" 5%- 2>/dev/null', $output, $return_code);
        // Send D-Bus signal about volume change
        shell_exec('/opt/dbus_notify VolumeChanged "volume_down" 2>/dev/null &');
        break;
        
    case 'get_volume':
        // Try different paths to amixer
        $amixer_paths = ['/usr/bin/amixer', '/bin/amixer', 'amixer'];
        $controls_found = false;
        
        // Use full paths to sudo and amixer
        exec('/usr/bin/sudo /usr/bin/amixer scontrols 2>/dev/null', $controls, $return_code);
        if ($return_code === 0 && !empty($controls)) {
            // Build list of volume controls to try
            $volume_controls = ['PCM', 'Master'];
            
            // Parse available controls and add volume-related ones
            foreach ($controls as $control_line) {
                if (preg_match("/Simple mixer control '([^']+)',/", $control_line, $control_matches)) {
                    $control_name = $control_matches[1];
                    // Optimized volume control search
                    $name_lower = strtolower($control_name);
                    if ($name_lower === 'pcm' || $name_lower === 'master' || 
                        $name_lower === 'speaker' || $name_lower === 'headphone' || 
                        $name_lower === 'dac' || $name_lower === 'audio' ||
                        strpos($name_lower, 'volume') !== false || 
                        strpos($name_lower, 'playback') !== false ||
                        !in_array($control_name, $volume_controls)) {
                        $volume_controls[] = $control_name;
                    }
                }
            }
            
            // Try each volume control with improved detection
            $tried_results = [];
            foreach ($volume_controls as $control) {
                exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control . '" 2>/dev/null', $full_output, $volume_code);
                if ($volume_code === 0 && !empty($full_output)) {
                    $full_text = implode(' ', $full_output);
                    if (preg_match('/\[(\d+)%\]/', $full_text, $matches)) {
                        $volume_percent = $matches[1];
                        $tried_results[$control] = $volume_percent . '%';
                        
                        // Skip controls that show exactly 50% (likely non-functional)
                        if ($volume_percent != 50) {
                            echo json_encode(['volume' => $volume_percent . '%', 'control' => $control]);
                            exit;
                        }
                    }
                }
            }
            
            // If all controls show 50%, use the first working one but mark it
            foreach ($tried_results as $control => $volume) {
                if ($volume === '50%') {
                    echo json_encode(['volume' => '100%', 'control' => $control, 'note' => 'USB_DAC_NO_HW_VOLUME']);
                    exit;
                }
            }
            
            // Return available controls for debugging
            echo json_encode(['error' => 'No volume found', 'controls' => $controls, 'tried' => $volume_controls, 'results' => $tried_results]);
            exit;
        }
        
        echo json_encode(['error' => 'amixer not found or no permissions', 'tried_paths' => $amixer_paths]);
        exit;
        
    case 'set_volume':
        $volume = intval($_POST['volume'] ?? 0);
        if ($volume >= 0 && $volume <= 100) {
            $control = getWorkingVolumeControl();
            exec("/usr/bin/sudo /usr/bin/amixer -q sset \"$control\" {$volume}% 2>/dev/null", $output, $return_code);
            // Send D-Bus signal about volume change
            shell_exec("/opt/dbus_notify VolumeChanged \"set_volume_{$volume}\" 2>/dev/null &");
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid volume level']);
            exit;
        }
        break;
        
    case 'toggle_mute':
        $control = getWorkingMuteControl();
        // Toggle mute/unmute
        exec('/usr/bin/sudo /usr/bin/amixer sget "' . $control . '" 2>/dev/null | grep -o "\\[on\\]\\|\\[off\\]" | head -1', $mute_output, $mute_code);
        if ($mute_code === 0 && !empty($mute_output)) {
            $is_muted = (trim($mute_output[0]) === '[off]');
            if ($is_muted) {
                // Enable sound
                exec('/usr/bin/sudo /usr/bin/amixer -q sset "' . $control . '" unmute 2>/dev/null', $output, $return_code);
                shell_exec('/opt/dbus_notify VolumeChanged "unmute" 2>/dev/null &');
                echo json_encode(['success' => true, 'muted' => false]);
            } else {
                // Disable sound
                exec('/usr/bin/sudo /usr/bin/amixer -q sset "' . $control . '" mute 2>/dev/null', $output, $return_code);
                shell_exec('/opt/dbus_notify VolumeChanged "mute" 2>/dev/null &');
                echo json_encode(['success' => true, 'muted' => true]);
            }
            exit;
        }
        // If couldn't determine state, just mute
        exec('/usr/bin/sudo /usr/bin/amixer -q sset "' . $control . '" mute 2>/dev/null', $output, $return_code);
        echo json_encode(['success' => true, 'muted' => true]);
        exit;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

if ($return_code === 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Command failed']);
}
?>