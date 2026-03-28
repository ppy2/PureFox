<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Clear cache
    $cache_file = '/tmp/combined_status_cache';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }

    if ($action === 'enable') {
        // Enable USBtoI2S mode: I2S output + USB gadget mode
        // First switch I2S to STD mode (required for USB to I2S)
        exec('/usr/bin/sudo /opt/2_std.sh 2>&1');

        $script = '/opt/usb_to_i2s.sh';
        if (!file_exists($script)) {
            http_response_code(500);
            die("Script $script not found");
        }

        // Execute script
        $output = [];
        $returnVar = 0;
        exec(escapeshellcmd($script) . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            http_response_code(500);
            die("Error: " . implode("\n", $output));
        }

        echo "USBtoI2S mode enabled successfully";
    } elseif ($action === 'disable') {
        // Disable USBtoI2S mode: USB host mode
        $script = '/opt/usb_unlock.sh';
        if (!file_exists($script)) {
            http_response_code(500);
            die("Script $script not found");
        }

        // Execute script
        $output = [];
        $returnVar = 0;
        exec(escapeshellcmd($script) . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            http_response_code(500);
            die("Error: " . implode("\n", $output));
        }

        echo "USBtoI2S mode disabled successfully";
    } elseif ($action === 'status') {
        // Check current mode
        $enabled = file_exists('/etc/usb_to_i2s.state');
        echo json_encode(['enabled' => $enabled]);
    } else {
        http_response_code(400);
        die('Invalid action. Allowed values: enable, disable, status');
    }
} else {
    http_response_code(405);
    header('Allow: POST');
    echo 'Use POST request';
}
?>
