<?php
	require_once  __DIR__ .'\wsaa\authenticator.php';

	$auth = new authenticator();
	$auth->createTra();
	$cms = $auth->SignTRA();
	$auth->CallWSAA($cms);
	$auth->generateTAFile();
?>