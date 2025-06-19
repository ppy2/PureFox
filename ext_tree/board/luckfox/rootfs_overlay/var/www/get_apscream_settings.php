<?php
// get_apscream_settings.php

// Set content type to JSON
header('Content-Type: application/json');

// Path to the APscream config file
$configFile = '/usr/apscream/config.txt';

// Check if the config file exists
if (!file_exists($configFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Config file not found'
    ]);
    exit;
}

try {
    // Read the config file
    $configContent = file_get_contents($configFile);
    
    // Parse config settings
    $settings = [];
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
            $settings[$key] = $value;
        }
    }
    
    // Return success response with settings
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error reading config file: ' . $e->getMessage()
    ]);
}
?>
