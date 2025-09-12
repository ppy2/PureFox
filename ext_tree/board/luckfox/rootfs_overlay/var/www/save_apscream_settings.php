<?php
// save_apscream_settings.php

// Set content type to JSON
header('Content-Type: application/json');

// Path to the APscream config file
$configFile = '/usr/apscream/config.txt';

try {
    // Get JSON data from POST request
    $input = file_get_contents('php://input');
    $settings = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    if (empty($settings)) {
        throw new Exception('No settings data received');
    }
    
    // Validate settings
    $validSettings = [
        'AP_MODE' => ['0', '1'],
        'TCP_MODE' => ['0', '1'],
        'MMAP_MODE' => ['0', '1'],
        'ALSA_PERIOD_FRAMES' => ['min' => 256, 'max' => 65536],
        'ALSA_BUFFER_FRAMES' => ['min' => 1024, 'max' => 131072],
        'ALSA_PERIOD_TIME' => ['min' => -1, 'max' => 100000],
        'ALSA_BUFFER_TIME' => ['min' => -1, 'max' => 100000],
        'PRELOAD_BUFFER_FRAMES' => ['min' => 1000, 'max' => 500000],
        'SCREAM_LATENCY' => ['min' => 50, 'max' => 1000]
    ];
    
    $configData = [];
    
    foreach ($settings as $key => $value) {
        if (!isset($validSettings[$key])) {
            continue; // Skip unknown settings
        }
        
        if (in_array($key, ['AP_MODE', 'TCP_MODE', 'MMAP_MODE'])) {
            // Check for mode settings (0 or 1)
            if (!in_array($value, $validSettings[$key])) {
                throw new Exception("Invalid value for $key: $value");
            }
            $configData[$key] = $value;
        } else {
            // Check for numeric values
            $numValue = intval($value);
            if ($numValue < $validSettings[$key]['min'] || $numValue > $validSettings[$key]['max']) {
                throw new Exception("Value for $key must be between {$validSettings[$key]['min']} and {$validSettings[$key]['max']}");
            }
            $configData[$key] = $numValue;
        }
    }
    
    // Create config directory if it doesn't exist
    $configDir = dirname($configFile);
    if (!file_exists($configDir)) {
        if (!mkdir($configDir, 0755, true)) {
            throw new Exception("Cannot create config directory: $configDir");
        }
    }
    
    // Read existing config file
    $existingConfig = [];
    if (file_exists($configFile)) {
        $configContent = file_get_contents($configFile);
        $lines = explode("\n", $configContent);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines or comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Parse key=value pairs
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $existingConfig[$key] = $value;
            }
        }
    }
    
    // Update existing config with new settings
    foreach ($configData as $key => $value) {
        $existingConfig[$key] = $value;
    }
    
    // Build new config file content
    $newConfigContent = '';
    foreach ($existingConfig as $key => $value) {
        $newConfigContent .= "$key=$value\n";
    }
    
    // Save the config file
    if (file_put_contents($configFile, $newConfigContent) === false) {
        throw new Exception("Cannot write to config file: $configFile");
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully'
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>