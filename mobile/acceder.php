<?php

	include "../config.php";

	//if($_POST["nombre"] && $_POST["pass"] && $_POST["nombre"]=$usuarioAdmin && $_POST["pass"]=$passAdmin){
		session_start();
		$_SESSION["valido"] = true;
		header("Location: ./index.php?seccion=planoprincipal");
	//}
	//else{
	//	header("Location: logueo.php");
	//}

?>
