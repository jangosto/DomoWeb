<?php
	include "../../config.php";

	switch($_POST['tipoPeticion']){
		case "valores":
			$fechas = $_POST['fechas'];
			$zona = $_POST['zona'];
			$variable = $_POST['variable'];

			$respuesta = peticionValores($fechas,$zona,$variable);
			//file_put_contents("/tmp/query.txt",print_r($respuesta,true));
			echo $respuesta;
			break;
		case "variables":
			$respuesta = peticionVariables();
			echo $respuesta;
			break;
		case "zonas":
			$respuesta = peticionZonas();
			echo $respuesta;
			break;
		case "valorActual":
			$zona = $_POST['zona'];
			$variable = $_POST['variable'];
			$respuesta = peticionValorActual($zona,$variable);
			echo $respuesta;
	}

	function peticionZonas(){
		global $BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name;
	}

	function peticionVariables(){
		global $BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name;
	}

	function peticionValores($fechas,$zona,$variable){
		global $BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name, $numMaxPuntosCurva;

		$jsonDatos = "";

		$datos = array();

		$manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);

		if(strlen($fechas['fechaIni'])>0 && strlen($fechas['fechaFin'])>0){
			$whereTemporal =" WHERE fecha BETWEEN '".$fechas['fechaIni']."' AND '".$fechas['fechaFin']."'";
		}
		elseif(strlen($fechas['fechaIni'])>0){
			$whereTemporal = " WHERE fecha > '".$fechas['fechaIni']."'";
		}
		elseif(strlen($fechas['fechaFin'])>0){
			$aux = explode(" ",$fechas['fechaFin']);
			$fechaAux = explode("-",$aux[0]);
			$horaAux = explode(":",$aux[1]);

			$whereTemporal = " WHERE fecha BETWEEN '".$aux[0]." 00:00:01' AND '".$aux[0]." 23:59:59'";
		}
		else{

		}

		$whereVariable = "";

		if($variable != ""){
			//$contadorVariables = 0;
			$whereVariable .= " WHERE variables.alias='".$variable."'";
			/*$contadorVariables++;
			while(isset($variable[$contadorVariables])){
				$whereVariable .= " OR variables.alias='".$variable[$contadorVariables]."'";

				$contadorVariables++;
			}*/
		}

		$query = "SELECT valores.valor AS valor, valores.fecha AS fecha, valores.idvariable AS idvariable, valores.idcontrolador AS idcontrolador, variables.nombre AS nombrevariable FROM (SELECT valores.valor AS valor, valores.fecha AS fecha, valores.idvariable AS idvariable, zonas.idcontrolador AS idcontrolador FROM (SELECT valor, fecha, idvariable, idzona FROM registrovars".$whereTemporal.") AS valores LEFT JOIN zonas ON zonas.id = valores.idzona WHERE zonas.idcontrolador=0x".$zona.") AS valores LEFT JOIN variables AS variables ON valores.idvariable=variables.id".$whereVariable;

		//file_put_contents("/tmp/query.txt",print_r($query,true));

		//echo $query;

		if($resultado = $manejadorBBDD->query($query)){
			//file_put_contents("/tmp/query.txt",print_r($resultado,true));
			$contadorDatosValidos = 1;
			$contadorAniadidos = 1;
			$i = 1;
			$j = 0;
			$variablesAniadidas = array();

			if(($numLineas = $resultado->num_rows) > $numMaxPuntosCurva){
				$contadorDatosValidos = $numLineas / $numMaxPuntosCurva;
			}

			while($linea = $resultado->fetch_object()){
				$aux = explode(" ",$linea->fecha);
				$fechaAux = explode("-",$aux[0]);
				$horaAux = explode(":",$aux[1]);
				if($j%$contadorDatosValidos == 0 || ($horaAux[0]=="00" && ($horaAux[1]=="00" || $horaAux[1]=="01"))){
					/*if($encontrado = array_search($linea->nombrevariable,$variablesAniadidas) !== false){
						$datos[$i][$encontrado] = $linea->valor;
					}
					else{
						$datos[0][$contadorAniadidos] = $linea->nombrevariable;
						$contadorAniadidos++;
					}*/
					$datos[$i]['nomVariable'] = utf8_encode($linea->nombrevariable);
		            $datos[$i]['valor'] = utf8_encode($linea->valor);
		            $datos[$i]['fecha'] = utf8_encode($linea->fecha);
		            $i++;
		        }
		        $j++;
	        }
	        //file_put_contents("/tmp/query.txt",print_r($datos,true));

	        $jsonDatos = json_encode($datos);
	        //file_put_contents("/tmp/query.txt",print_r($jsonDatos,true));

	        //echo $jsonDatos;
		}

		$manejadorBBDD->close();

		return $jsonDatos;
	}

	function peticionValorActual($zona,$variable){
		global $BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name;

		$jsonDatos = "";

		$datos = array();

		$manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);

		$query = "SELECT valores.* FROM (SELECT a.id AS idvalor, a.valor AS valor, b.simbolounidad AS simbolounidad, a.idzona AS idzona FROM registrovars AS a LEFT JOIN variables AS b ON a.idvariable=b.id WHERE b.alias='".$variable."') AS valores LEFT JOIN zonas as zonas ON valores.idzona=zonas.id WHERE zonas.idcontrolador=0x".$zona." ORDER BY valores.idvalor DESC LIMIT 1";

		if($resultado = $manejadorBBDD->query($query)){
			$i=0;
			while($linea = $resultado->fetch_object()){
				$datos[$i]['valor'] = utf8_encode($linea->valor);
				$datos[$i]['simbolounidad'] = utf8_encode($linea->simbolounidad);
				$i++;
	        }

	        $jsonDatos = json_encode($datos);
	        		file_put_contents("/tmp/query.txt",print_r($jsonDatos,true));

	    }

        $manejadorBBDD->close();

		return $jsonDatos;
	}
?>
