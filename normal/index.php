<?php
	session_start();

	if(isset($_SESSION['valido']) && $_SESSION['valido']=true){
		include "../config.php";
		include "../libreria/funciones.php";
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
			}
		}
		else{
			$tituloSeccion="Inicio";
			$seccion = "planoprincipal";
		} ?>

		<html>

			<?php include "cabecera.php";?>

			<body>
				<div class="container-fluid">
					<div class="row-fluid">
						<div id="contenido-titulo" class="span7">
							<h3 id="titulo-admin" class="text-center">Administraci&oacute;n: <?php echo $tituloSeccion ?></h3>
						</div>
					</div>
					<div class="row-fluid">
						<div id="contenido-seccion" class="span7">
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
						} ?>
						</div>
						<div id="contenido-menu" class="span3">
							<?php include "menu.php"; ?>
						</div>
					</div>
				</div>
			</body>
		</html>
		<?php
	}
	else{
		header("Location: logueo.php");
	}
?>
