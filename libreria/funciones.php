<?php
	function limpiarTildes($cadena){
		$resultado = $cadena;
		$resultado = str_replace("á","&aacute;",$resultado);
		$resultado = str_replace("é","&aecute;",$resultado);
		$resultado = str_replace("í","&iacute;",$resultado);
		$resultado = str_replace("ó","&oacute;",$resultado);
		$resultado = str_replace("ú","&úacute;",$resultado);
		$resultado = str_replace("Á","&Aacute;",$resultado);
		$resultado = str_replace("É","&Eacute;",$resultado);
		$resultado = str_replace("Í","&Iacute;",$resultado);
		$resultado = str_replace("Ó","&Oacute;",$resultado);
		$resultado = str_replace("Ú","&Uacute;",$resultado);
		$resultado = str_replace("ñ","&ntilde;",$resultado);
		$resultado = str_replace("Ñ","&Ntilde;",$resultado);

		echo $resultado;

		return $resultado;
	}
?>