<?php
	session_start();

	require_once './classes/Mobile-Detect-2.7.1/Mobile_Detect.php';
	$detect = new Mobile_Detect;

	if($detect->isMobile()){
		header("Location: mobile/index.php?seccion=planoprincipal");
	}
	else{
//		header("Location: mobile/index.php?seccion=planoprincipal");
		header("Location: normal/index.php?seccion=planoprincipal");
	}
?>
