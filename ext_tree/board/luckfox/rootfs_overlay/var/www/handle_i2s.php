<?php
require_once 'config.php';

// Path to configuration file
$config_file = '/etc/i2s.conf';

// If mode is changed
if (isset($_POST['mode'])) {
    $mode = $_POST['mode'];
    if (in_array($mode, ['pll', 'ext'])) {
        $script = ($mode === 'pll') ? '/opt/2pll.sh' : '/opt/2ext.sh';
        exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
    }
}

// If submode is changed
if (isset($_POST['submode'])) {
    $submode = $_POST['submode'];
    if (in_array($submode, ['std', 'lr', 'plr', '8ch'])) {
        $script = "/opt/2_$submode.sh";
        exec('/usr/bin/sudo ' . escapeshellcmd($script) . ' 2>&1', $output, $returnVar);
    }
}

// If MCLK is changed
if (isset($_POST['mclk'])) {
    $mclk = $_POST['mclk'];
    if (in_array($mclk, ['512', '1024'])) {
        exec('/usr/bin/sudo /opt/2_' . $mclk . '.sh 2>&1', $output, $returnVar);
    }
}

// If PCM swap setting is changed
if (isset($_POST['pcm_swap'])) {
    $pcm_swap = $_POST['pcm_swap'];
    if (in_array($pcm_swap, ['0', '1'])) {
        file_put_contents('/sys/devices/platform/ffae0000.i2s/pcm_channel_swap', $pcm_swap);
        
        // Update config file
        $contents = '';
        if (file_exists($config_file)) {
            $contents = file_get_contents($config_file);
        }
        $pattern = '/^### PCM channel swap.*?\nPCM_SWAP=.*$/m';
        $replacement = "### PCM channel swap: 0 or 1 ###\nPCM_SWAP=$pcm_swap";
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents);
        } else {
            $contents .= "\n### PCM channel swap: 0 or 1 ###\nPCM_SWAP=$pcm_swap\n";
        }
        file_put_contents($config_file, $contents);
    }
}

// If DSD swap setting is changed
if (isset($_POST['dsd_swap'])) {
    $dsd_swap = $_POST['dsd_swap'];
    if (in_array($dsd_swap, ['0', '1'])) {
        file_put_contents('/sys/devices/platform/ffae0000.i2s/dsd_physical_swap', $dsd_swap);
        
        // Update config file
        $contents = '';
        if (file_exists($config_file)) {
            $contents = file_get_contents($config_file);
        }
        $pattern = '/^### DSD physical swap.*?\nDSD_SWAP=.*$/m';
        $replacement = "### DSD physical swap: 0 or 1 ###\nDSD_SWAP=$dsd_swap";
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents);
        } else {
            $contents .= "\n### DSD physical swap: 0 or 1 ###\nDSD_SWAP=$dsd_swap\n";
        }
        file_put_contents($config_file, $contents);
    }
}

// If frequency domain swap setting is changed
if (isset($_POST['freq_swap'])) {
    $freq_swap = $_POST['freq_swap'];
    if (in_array($freq_swap, ['0', '1'])) {
        file_put_contents('/sys/devices/platform/ffae0000.i2s/freq_domain_invert', $freq_swap);
        
        // Update config file
        $contents = '';
        if (file_exists($config_file)) {
            $contents = file_get_contents($config_file);
        }
        $pattern = '/^### Frequency domain swap.*?\nFREQ_SWAP=.*$/m';
        $replacement = "### Frequency domain swap (44/48): 0 or 1 ###\nFREQ_SWAP=$freq_swap";
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents);
        } else {
            $contents .= "\n### Frequency domain swap (44/48): 0 or 1 ###\nFREQ_SWAP=$freq_swap\n";
        }
        file_put_contents($config_file, $contents);
    }
}

// If LeftJust setting is changed
if (isset($_POST['leftjust'])) {
    $leftjust = $_POST['leftjust'];
    if (in_array($leftjust, ['0', '1'])) {
        // Update config file
        $contents = '';
        if (file_exists($config_file)) {
            $contents = file_get_contents($config_file);
        }
        $pattern = '/^### Left Justified.*?\nLEFTJUST=.*$/m';
        $replacement = "### Left Justified mode: 0 or 1 ###\nLEFTJUST=$leftjust";
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents);
        } else {
            $contents .= "\n### Left Justified mode: 0 or 1 ###\nLEFTJUST=$leftjust\n";
        }
        file_put_contents($config_file, $contents);
    }
}

// AJAX request for current status
if (isset($_GET['action']) && $_GET['action'] === 'getStatus') {
    $result = ['mode' => '', 'mclk' => '', 'submode' => '', 'pcm_swap' => '0', 'dsd_swap' => '1', 'freq_swap' => '0', 'leftjust' => '0'];

    if (file_exists($config_file)) {
        $contents = file_get_contents($config_file);
        if (preg_match('/^MODE=(\w+)/m', $contents, $matches)) {
            $result['mode'] = strtolower($matches[1]);
        }
        if (preg_match('/^MCLK=(\d+)/m', $contents, $matches)) {
            $result['mclk'] = $matches[1];
        }
        if (preg_match('/^SUBMODE=(\w+)/m', $contents, $matches)) {
            $result['submode'] = strtolower($matches[1]);
        } else {
            $result['submode'] = 'std';
        }
        if (preg_match('/^PCM_SWAP=([01])/m', $contents, $matches)) {
            $result['pcm_swap'] = $matches[1];
        }
        if (preg_match('/^DSD_SWAP=([01])/m', $contents, $matches)) {
            $result['dsd_swap'] = $matches[1];
        }
        if (preg_match('/^FREQ_SWAP=([01])/m', $contents, $matches)) {
            $result['freq_swap'] = $matches[1];
        }
        if (preg_match('/^LEFTJUST=([01])/m', $contents, $matches)) {
            $result['leftjust'] = $matches[1];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
