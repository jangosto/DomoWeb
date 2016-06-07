<?php
//FICHERO PRINCIPAL DE LA INTERFAZ WEB DE CONTROL DEL SISTEMA DOMÓTICO

	session_start();
	
	if(isset($_SESSION['valido']) && $_SESSION['valido']=true){ //El usuario ha sido validado
		
		include "../config.php"; //incluidos el fichero de configuración que contiene las variables globales del entorno
		include "../libreria/funciones.php"; //incluidos el fichero de funciones de uso global
		
		//DEFINICIÓN DE LA SECCIÓN (HABITACIÓN) SELECCIONADA PARA SER CONTROLADA
		if(isset($_GET['seccion']) && strlen($_GET['seccion']) > 0){
			$seccion = $_GET['seccion'];
			switch($seccion){
				case "planoprincipal":
					$tituloSeccion="Inicio";
					break;
				case "salon":
					$tituloSeccion="Sal&oacute;n";
					break;
				case "habitacion1":
					$tituloSeccion="Habitaci&oacute;n 1";
					break;
				case "habitacion2":
					$tituloSeccion="Habitaci&oacute;n 2";
					break;
				case "habitacion3":
					$tituloSeccion="Habitaci&oacute;n 3";
					break;
				case "banio1":
					$tituloSeccion="Ba&ntilde;o 1";
					break;
				case "banio2":
					$tituloSeccion="Ba&ntilde;o 2";
					break;
				case "cocina":
					$tituloSeccion="Cocina";
					break;
				case "terraza":
					$tituloSeccion="Terraza";
					break;
				case "pasillo":
					$tituloSeccion="Pasillo";
					break;
				case "recibidor":
					$tituloSeccion="Recibidor";
					break;
				case "graficas":
					$tituloSeccion="Gr&aacute;ficas";
					break;
				case "opciones":
					$tituloSeccion="Opciones";
					break;
			}
		}
		//EN CASO DE NO SELECCIONAR NINGUNA SECCION, PRESENTAR EL PLANO DE LA CASA
		else{
			$tituloSeccion="Inicio";
			$seccion = "planoprincipal";
		} ?>

		<html>

			<?php include "cabecera.php";//INCLUIDA LA CABECERA HTML?>

			<body>
				<!-- CONTENEDOR DE MENÚ QUE CONTIENE LA LISTA DE SECCIONES SELECCIONABLE, BOTÓN DE HOME Y BOTÓN DE SALIDA DE CIERRE DE SESIÓN -->
				<div id="contenedor-general">
					<div data-role="header">
						<a id="boton-home" href="index.php?seccion=planoprincipal" data-icon="home">Inicio</a>
						<div id="menu-secciones">
							<form action="index.php" method="get">
								<select name="seccion" id="select-choice-0" onchange="submit();">
									<option value="salon" <?php if($seccion=='salon'){?> selected="selected" <?php }?>>Salon</a></option>
									<option value="habitacion1" <?php if($seccion=='habitacion1'){?> selected="selected" <?php }?>>Habitacion 1</option>
									<option value="habitacion2" <?php if($seccion=='habitacion2'){?> selected="selected" <?php }?>>Habitacion 2</option>
									<option value="habitacion3" <?php if($seccion=='habitacion3'){?> selected="selected" <?php }?>>Habitacion 3</option>
									<option value="banio1" <?php if($seccion=='banio1'){?> selected="selected" <?php }?>>Banio 1</a></option>
									<option value="banio2" <?php if($seccion=='banio2'){?> selected="selected" <?php }?>>Banio 2</a></option>
									<option value="cocina" <?php if($seccion=='cocina'){?> selected="selected" <?php }?>>Cocina</a></option>
									<option value="terraza" <?php if($seccion=='terraza'){?> selected="selected" <?php }?>>Terraza</a></option>
									<option value="pasillo" <?php if($seccion=='pasillo'){?> selected="selected" <?php }?>>Pasillo</a></option>
									<option value="recibidor" <?php if($seccion=='recibidor'){?> selected="selected" <?php }?>>Recibidor</a></option>
									<option value="graficas" <?php if($seccion=='graficas'){?> selected="selected" <?php }?>>Gráficas</a></option>
									<option value="opciones" <?php if($seccion=='opciones'){?> selected="selected" <?php }?>>Opciones</a></option>
									<option value="planoprincipal" <?php if($seccion=='planoprincipal'){?> selected="selected" <?php }?>>Inicio</a></option>
								</select>
							</form>
						</div>
						<a id="boton-salir" href="salir.php" data-icon="delete" data-theme="b">Salir</a>
					</div>
					<!--CONTENEDOR DE MUESTRA DE CONTROLES DE VARIABLES EN CASO DE ENCONTRARSE EN UNA SECCIÓN O PLANO EN CASO DE ESTAR EN LA HOME-->
					<div id="contenido-seccion">
						<?php
						switch($seccion){
							case "planoprincipal":
								include "secciones/plano_principal.php";
								break;
							case "salon":
								include "secciones/salon.php";
								break;
							case "habitacion1":
								include "secciones/habitacion1.php";
								break;
							case "habitacion2":
								include "secciones/habitacion2.php";
								break;
							case "habitacion3":
								include "secciones/habitacion3.php";
								break;
							case "banio1":
								include "secciones/banio1.php";
								break;
							case "banio2":
								include "secciones/banio2.php";
								break;
							case "cocina":
								include "secciones/cocina.php";
								break;
							case "terraza":
								include "secciones/terraza.php";
								break;
							case "pasillo":
								include "secciones/pasillo.php";
								break;
							case "recibidor":
								include "secciones/recibidor.php";
								break;
							case "graficas":
								include "secciones/graficas.php";
								break;
							case "opciones":
								include "secciones/opciones.php";
								break;
						}?>
					</div>
				</div>
			</body>
		</html>
		<?php
	}
	//EN CASO DE NO ESTAR VALIDADO EL ACCESO
	else{
		header("Location: logueo.php");
	}
?>
