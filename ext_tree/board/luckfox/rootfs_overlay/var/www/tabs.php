<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Luckfox</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link type="text/css" rel="stylesheet" href="style.css">
<?php
$update = 0;
include 'variables.php';
$audioDataDisplay = "none";
$rebootButtonDisplay = "none";
if($valuesArray[1] == true){
	$audioDataDisplay = "table-cell";
}
if($valuesArray[0] == true){
	$rebootButtonDisplay = "table-cell";
}
$filename = "/tmp/update/";

?>
<script src="javascript.js"></script>
</head>


<body bgcolor="#565656" onload='pageLoad()'>
<div id='clickDisabler'>
</div>
<div id='fullContainer'>
	<div id='middleContainer'>
		<div id='preloadBack' >
			<div id='preload' >
			</div>
		</div>
		<table id='mainContent' cellspacing="0" cellpadding="0">
			<tr id='optionsTop' >
				<td id='audioTab' >
					<span>Audio</span>
				</td>
				<td>
					<span id='settingsLink'>
						<?php
						 $server = $_SERVER['HTTP_HOST']; 
						 echo "&emsp;<a href=http://" ;   
						 echo $server ;                   
						 echo ":7779>Settings</a>";
						?>
					</span>
				</td>
				<td>
				</td>
				<td  id='systemTab'>
					<span>System</span>
				</td>
			</tr>
			<tr>
				<td colspan=4 id='contentCell' >
					<div id='protocolSel'>
						<form action="" method="post">
							<div id='iconContainer'>
								<div class='row'>
									<div class='cell'>
										<?php
										 $a = `pidof networkaudiod`;
										 if (empty($a)){
										 echo '<input type="submit" name="naaButton" value="" id="naaButton" class="unselButton"/><input type="submit" id="naaText" name="naaButton" value="NAA (HQPlayer)" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="naaButton" value="" id="naaButton" class="selButton"/><input type="submit"  id="naaText" name="naaButton" value="NAA (HQPlayer)" class="selButtonText"/>';
										 }
										?>
									</div>
									<div class='cell'>
										<?php
										 $a = `pidof raat_app`;
										 if (empty($a)){
										 echo '<input type="submit" name="raatButton" value="" id="raatButton" class="unselButton"/><input type="submit" id="raatText" name="raatButton" value="RAAT (Roon)" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="raatButton" value="" id="raatButton" class="selButton"/><input type="submit"  id="raatText" name="raatButton" value="RAAT (Roon)" class="selButtonText"/>';
										 }
										?>
									</div>
									<div class='cell'>
										<?php
										$a = `pidof aplayer`;
										if (empty($a)){
										 echo '<input type="submit" name="mpdButton" value="" id="mpdButton" class="unselButton"/><input type="submit" id="mpdText" name="mpdButton" value="APlayer" class="unselButtonText"/>';
										} else {
										 echo '<input type="submit" name="mpdButton" value="" id="mpdButton" class="selButton"/><input type="submit" id="mpdText" name="mpdButton" value="APlayer" class="selButtonText"/>';
										}
										?>
									</div>
								</div>										

								<div class='row'>
									<div class='cell'>
										<?php
										 $a = `pidof ap2renderer`;
										 if (empty($a)){
										 echo '<input type="submit" name="aplayerButton" value="" id="aplayerButton" class="unselButton"/><input type="submit" id="aplayerText" name="aplayerButton" value="UPNP (APlayer)" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="aplayerButton" value="" id="aplayerButton" class="selButton"/><input type="submit" id="aplayerText" name="aplayerButton" value="UPNP (APlayer)" class="selButtonText"/>';
										 echo '<script type="text/javascript">document.getElementById("settingsLink").style.display="block";</script>';
										 }
										?>
									</div>
									<div class='cell'>
										<?php
										 $a = `pidof shairport-sync`;
										 if (empty($a)){
										 echo '<input type="submit" name="airPlayButton" value="" id="airPlayButton" class="unselButton"/><input type="submit" id="airPlayText" name="airPlayButton" value="AirPlay (ShairportSync)" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="airPlayButton" value="" id="airPlayButton" class="selButton"/><input type="submit" id="airPlayText" name="airPlayButton" value="AirPlay (ShairportSync)" class="selButtonText"/>';
										 }
										?>
									</div>
									<div class='cell'>
										<?php
										 $a = `pidof squeezelite`;
										 if (empty($a)){
										 echo '<input type="submit" name="lmsButton" value="" id="lmsButton" class="unselButton"/><input type="submit" id="lmsText" name="lmsButton" value="LMS (Squeezelite)" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="lmsButton" value="" id="lmsButton" class="selButton"/><input type="submit" id="lmsText" name="lmsButton" value="LMS (Squeezelite)" class="selButtonText"/>';
										 }
										?>
									</div>
								</div>

								<div class='row'>
									<div class='cell'>
										<?php
										 $a = `pidof apscream`;
										 if (empty($a)){
										 echo '<input type="submit" name="apscreamButton" value="" id="apscreamButton" class="unselButton"/><input type="submit" id="apscreamText" name="apscreamButton" value="apScream audio" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="apscreamButton" value="" id="apscreamButton" class="selButton"/><input type="submit" id="apscreamText" name="apscreamButton" value="apScream audio" class="selButtonText"/>';
										 }
										?>    
									</div>
									<div class='cell'>
										<?php
										 $a = `pidof librespot`;
										 if (empty($a)){
										 echo '<input type="submit" name="spotifyButton" value="" id="spotifyButton" class="unselButton"/><input type="submit" id="spotifyText" name="spotifyButton" value="Spotify Connect" class="unselButtonText"/>';
										 } else {
										 echo '<input type="submit" name="spotifyButton" value="" id="spotifyButton" class="selButton"/><input type="submit" id="spotifyText" name="spotifyButton" value="Spotify Connect" class="selButtonText"/>';
										 }
										?>    
									</div>
									<div class='cell'>
										<?php
										 $tf = '/etc/init.d/S95tidal';
										 if (file_exists($tf)) {
										 echo '<input type="submit" name="tidalButton" value="" id="tidalButton" class="selButton"/><input type="submit" id="tidalText" name="tidalButton" value="Tidal Connect" class="selButtonText"/>';
										 } else {
										 echo '<input type="submit" name="tidalButton" value="" id="tidalButton" class="unselButton"/><input type="submit" id="tidalText" name="tidalButton" value="Tidal Connect" class="unselButtonText"/>';
										 }
										?>    
									</div>
								</div>
							</div>
						</form>
							
					</div>
					<div id='systemSel'>
						<div class='row'>

						</div>
						<div class='row'>
							<div id='output'>
								<span class="title">Output type selection:</span>
								<div>
									<form method="post">
										<div>
                                                                                <?php
                                                                                $outFlagPath = '/etc/output';
                                                                                if (file_exists($outFlagPath)) {
                                                                                        if( exec('grep '.escapeshellarg($_GET['']).' /etc/output') == 'USB' ) {
                                                                                                echo "<div class='buttonSpacer'><span class='unselOutputText'>USB</span></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='spdifOutput' value='SPDIF'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='i2sOutput' value='I2S'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='i2slrOutput' value='I2S(L/R)'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='+-i2slrOutput' value='I2S(±L/±R)'/></div>";

                                                                                                }
                                                                                        else if ( exec('grep '.escapeshellarg($_GET['']).' /etc/output') == 'SPDIF' ) {
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='usbOutput' value='USB'/></div>";
                                                                                                echo "<div class='buttonSpacer'><span class='unselOutputText'>SPDIF</span></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='i2sOutput' value='I2S'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='i2slrOutput' value='I2S(L/R)'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='+-i2slrOutput' value='I2S(±L/±R)'/></div>";
                                                                                                }
                                                                                        else if ( exec('grep '.escapeshellarg($_GET['']).' /etc/output') == 'I2S' ) {
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='usbOutput' value=' USB'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='spdifOutput' value='SPDIF'/></div>";
                                                                                                echo "<div class='buttonSpacer'><span class='unselOutputText'>I2S</span></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='i2slrOutput' value='I2S(L/R)'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='+-i2slrOutput' value='I2S(±L/±R)'/></div>";
                                                                                                }

                                                                                        else if ( exec('grep '.escapeshellarg($_GET['']).' /etc/output') == 'I2SLR' ) {
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='usbOutput' value='USB'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='spdifOutput' value='SPDIF'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='i2sOutput' value='I2S'/></div>";
                                                                                                echo "<div class='buttonSpacer'><span class='unselOutputText'>I2S(L/R)</span></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='+-i2slrOutput' value='I2S(±L/±R)'/></div>";
                                                                                                }
                                                                                        else if ( exec('grep '.escapeshellarg($_GET['']).' /etc/output') == '+-I2SLR' ) {
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='usbOutput' value='USB'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='spdifOutput' value='SPDIF'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='i2sOutput' value='I2S'/></div>";
                                                                                                echo "<div class='buttonSpacer'><input type='submit' class='selOutputText' name='i2slrOutput' value='I2S(L/R)'/></div>";
                                                                                                echo "<div class='buttonSpacer'><span class='unselOutputText'>I2S(±L/±R)</span></div>";
                                                                                                }
                                                                                                
                                                                                } else {
                                                                                        echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='usbOutput' value='USB'/></div>";
                                                                                        echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='spdifOutput' value='SPDIF'/></div>";
                                                                                        echo "<div class='buttonSpacer'><span class='unselOutputText'>I2S</span></div>";
                                                                                        echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='i2srlOutput' value='I2S(R/L)'/></div>";
                                                                                        echo "<div class='buttonSpacer'><input type='submit' class='selOutputText'  name='+-i2srlOutput' value='I2S(±L/±R)'/></div>";
                                                                                }
                                                                                echo "<br>" ;
                                                                                ?>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div class='row'>
						    <div id='wifi'>
							<span class="title">Hostname:</span>
							<?php
							$HOST = `cat /etc/hostname` ;
							echo '<form method="post">' ;
							echo 'http://<input type="text" size="5" id="fname" name="hostN" value="'.$HOST.'">.local<br><br>' ;
							echo '<div class="buttonSpacer"><input type="submit" name="hostname" id="hostname" value="Apply settings and reboot" /></div>' ;
							echo '</form>' ;
							?>
							<br>
						    </div>
						</div>

						<div class='row'>
							<div id='eMMC' >
								<span class="warn title">Attention!</font></span>
								<span>This command will format and destroy old data!</span><br>
								<span>The D2-D5 leds indicates that copying is in progress.</span>
								<form method="post" id="memory_actions">
									<div class='buttonSpacer'><input type="submit" name="emmc" id="emmc" value="SD to eMMC" /><br></div>
									<div class='buttonSpacer'><value="     " /><br></div>
									<div class='buttonSpacer'><input type="submit" name="sd" id="sd" value="eMMC to SD" /><br></div>
								</form>
								<?php
								function testfun()
									{
									$EMMC = `ls /dev/mmcblk1` ;
									$ROOT = `grep mmcblk0 /proc/cmdline` ;
									if (empty($EMMC)) { 
									echo '<br><font color = "white">You don`t have built-in eMMC memory.</font>' ;
									}
									elseif (empty($ROOT)) { echo '<br><span class="title">Your system is already loaded from eMMC.</span>' ; }
									else { echo '<script type="text/javascript">updatePage();</script>' ; 
									echo '<script type="text/javascript">setTimeout(function() {window.location = window.location.href; }, 30000);</script>';
									exec ('/opt/copytoemmc.sh' . '>/dev/null &'); 
									 } ;
									}	
								function testfun2()
									{
									$ROOT = `grep mmcblk1 /proc/cmdline` ;
									if (empty($ROOT)) { 
									echo '<br><span class="title">You have already booted from the SD. Boot from eMMC and install a blank SD to write it.</span>' ;
									}
									else { 
									echo '<script type="text/javascript">updatePage();</script>' ; 
									echo '<script type="text/javascript">setTimeout(function() {window.location = window.location.href; }, 30000);</script>';
									exec ('/opt/copytosd.sh' . '>/dev/null &');
									 } ;
									}
								if(array_key_exists('emmc',$_POST)){ testfun(); }
								if(array_key_exists('sd',$_POST)){ testfun2(); }
								?>
							</div>
						</div>
						
						
						
						<div class='row'>
								<span class="title">ver.</span>
								<div id='upd'>
									<form method="post">
										<?php
										echo "<div><input type='submit' class='selOutputText' id='update' name='updateFirmW' value='Upgrade to latest version' /></div>";
										?>
									</form>
								</div>
						</div>
					</div>
					<div id='sign'>
						<div></div><span>Pure</span>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>
<?php
	if(isset($_POST['naaButton'])){
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app shairport-sync squeezelite spotifyd librespot apscream networkaudiod ;
                /etc/rc.pure/S95aprenderer stop ;
                rm /etc/init.d/S95* ;
                /etc/rc.pure/S95tidal stop ;
                cp /etc/rc.pure/S95naa /etc/init.d/ && sync ; 
                /etc/rc.pure/S95naa start ;
                /opt/unmute.sh` ;
                }
    		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("naaButton").classList.remove("unselButton"); document.getElementById("naaButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("naaText").classList.remove("unselButtonText"); document.getElementById("naaText").classList.add("selButtonText");</script>';
		echo '<script type="text/javascript">console.log("button1");</script>';
	} else if(isset($_POST['raatButton'])){ 
		{`/opt/mute.sh ;
		killall -9 networkaudiod aplayer shairport-sync squeezelite spotifyd librespot apscream raat_app;
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
		cp /etc/rc.pure/S95roonready /etc/init.d/ && sync ; 
		/etc/rc.pure/S95roonready start ;
		/opt/unmute.sh` ;
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("raatButton").classList.remove("unselButton"); document.getElementById("raatButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("raatText").classList.remove("unselButtonText"); document.getElementById("raatText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['mpdButton'])){ 
                {`/opt/mute.sh ;
		killall -9 networkaudiod raat_app shairport-sync squeezelite spotifyd librespot apscream aplayer ; 
    		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
                cp /etc/rc.pure/S95aplayer /etc/init.d/ /etc/init.d/ && sync ;
                /etc/rc.pure/S95mpd start  ;
                renice 0 -u aplayer ;
		/opt/unmute.sh` ;
                }
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("mpdButton").classList.remove("unselButton"); document.getElementById("mpdButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("mpdText").classList.remove("unselButtonText"); document.getElementById("mpdText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['aplayerButton'])){ 
                {`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod shairport-sync squeezelite spotifyd librespot apscream ; 
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
                cp /etc/rc.pure/S95aprenderer /etc/init.d/ && sync ; 
                /etc/init.d/S95aprenderer start ;
                /opt/unmute.sh` ;
                }
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("aplayerButton").classList.remove("unselButton"); document.getElementById("aplayerButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("aplayerText").classList.remove("unselButtonText"); document.getElementById("aplayerText").classList.add("selButtonText");</script>';
		echo '<script type="text/javascript">document.getElementById("settingsLink").style.display="block";</script>';
	} else if(isset($_POST['airPlayButton'])){ 
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod squeezelite spotifyd librespot apscream shairport-sync ; 
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
		cp /etc/rc.pure/S95shairport-sync /etc/init.d/ && sync ; 
		/etc/init.d/S95shairport-sync start ;
		/opt/unmute.sh` ;
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("airPlayButton").classList.remove("unselButton"); document.getElementById("airPlayButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("airPlayText").classList.remove("unselButtonText"); document.getElementById("airPlayText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['lmsButton'])){ 
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod shairport-sync spotifyd librespot apscream squeezelite ; 
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
		cp /etc/rc.pure/S95squeezelite /etc/init.d/ && sync ; 
		/etc/init.d/S95squeezelite start ;
		/opt/unmute.sh` ;
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("lmsButton").classList.remove("unselButton"); document.getElementById("lmsButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("lmsText").classList.remove("unselButtonText"); document.getElementById("lmsText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['spotifyButton'])){ 
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod shairport-sync squeezelite apscream spotifyd librespot ; 
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
		cp /etc/rc.pure/S95spotify /etc/init.d/ && sync ; 
		/etc/rc.pure/S95spotify start ;
		/opt/unmute.sh` ;
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("spotifyButton").classList.remove("unselButton"); document.getElementById("spotifyButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("spotifyText").classList.remove("unselButtonText"); document.getElementById("spotifyText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['apscreamButton'])){ 
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod shairport-sync squeezelite spotifyd librespot apscream ; 
		/etc/rc.pure/S95aprenderer stop ;
		rm /etc/init.d/S95* ;
		/etc/rc.pure/S95tidal stop ;
		cp /etc/rc.pure/S95scream /etc/init.d/ && sync ; 
		nohup /usr/apscream/apscream > /dev/null 2>&1 &
		/opt/unmute.sh ` ; 
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("apscreamButton").classList.remove("unselButton"); document.getElementById("apscreamButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("apscreamText").classList.remove("unselButtonText"); document.getElementById("apscreamText").classList.add("selButtonText");</script>';
	} else if(isset($_POST['tidalButton'])){ 
		{`/opt/mute.sh ;
		killall -9 aplayer raat_app networkaudiod shairport-sync squeezelite spotifyd librespot apscream; 
		/etc/rc.pure/S95aprenderer stop ;
		/etc/rc.pure/S95tidal stop ;
		rm /etc/init.d/S95* ;
		cp /etc/rc.pure/S95tidal /etc/init.d/ && sync ; 
		/etc/rc.pure/S95tidal start ; 
		/opt/unmute.sh` ;
		}
		echo '<script type="text/javascript">removeSelButtons();</script>';
		echo '<script type="text/javascript">document.getElementById("tidalButton").classList.remove("unselButton"); document.getElementById("tidalButton").classList.add("selButton");</script>';
		echo '<script type="text/javascript">document.getElementById("tidalText").classList.remove("unselButtonText"); document.getElementById("tidalText").classList.add("selButtonText");</script>';
        
        } else if(isset($_POST['i2sOutput']) && $_POST["i2sOutput"] != "" ){
                echo '<script type="text/javascript">updatePage();</script>';
                echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
                exec ('/opt/to_i2s.sh' . '>/dev/null &');
        
        } else if(isset($_POST['i2slrOutput']) && $_POST["i2slrOutput"] != "" ){
                echo '<script type="text/javascript">updatePage();</script>';
                echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
                exec ('/opt/to_i2slr.sh' . '>/dev/null &');
        
        } else if(isset($_POST['+-i2slrOutput']) && $_POST["+-i2slrOutput"] != "" ){
                echo '<script type="text/javascript">updatePage();</script>';
                echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
                exec ('/opt/to_+-i2slr.sh' . '>/dev/null &');
        
        } else if(isset($_POST['spdifOutput']) && $_POST["spdifOutput"] != "" ){
                echo '<script type="text/javascript">updatePage();</script>';
                echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
                exec ('/opt/to_spdif.sh' . '>/dev/null &');
        
        } else if(isset($_POST['usbOutput']) && $_POST["usbOutput"] != "" ){
                echo '<script type="text/javascript">updatePage();</script>';
                echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
                exec ('/opt/to_usb.sh' . '>/dev/null &');
	
	} else if(isset($_POST['updateFirmW']) && $_POST["updateFirmW"] != "" ){
		echo '<script type="text/javascript">updatePage();</script>';
		echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 25000);</script>';
		exec ('/opt/update' . '>/dev/null &');
	
	} else if(isset($_POST['hostname']) && $_POST["hostname"] != "" ){
		echo '<script type="text/javascript">updatePage();</script>';
		echo '<script type="text/javascript">setTimeout(function() {getUpdateOutput(); }, 3000);</script>';
		$name = $_POST["hostN"] ;
		exec ( 'echo '.$name.' > /etc/hostname' );
                exec ('/opt/reboot.sh' . '>/dev/null &');
	} ; 
	
?>

<script type="text/javascript">
function getUpdateOutput() {
    setInterval(function() {
    if (navigator.onLine) {
    window.location = window.location.href;
    };
    console.log("waiting for refresh");
    }, 1000); }
		
	var audio_cell = document.getElementById("audioTab");
  audio_cell.addEventListener('click', function () { selAudioTab() ; });
	var system_cell = document.getElementById("systemTab");
  system_cell.addEventListener('click', function () { selSystemTab(); });
</script>

<?php
include ($_SERVER['DOCUMENT_ROOT']."/var/www/plugins.php");
?>

</body>   
<br>
</html>



 
