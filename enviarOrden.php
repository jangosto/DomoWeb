<?php
	$comando=$_POST['comando'];
	$i=0;
	$ordenArray=array();
	$longitud=strlen($comando);
	while($i<$longitud){
		$ordenArray[$i] = ord($comando[$i]);
		$i++;
	}
	$ordenArray[$i] = "\n";
	$i=0;
	$longitud=count($ordenArray);
	$orden='';
	while($i<$longitud){
		$orden.=chr($ordenArray[$i]);
		$i++;
	}
	system("./programas/prueba ".$orden);
	//print_r($ordenArray);
	//echo "<h1>El dato recibido es: ".$orden." con longitud ".strlen($orden)."</h1>";
	echo "OK";
?>
