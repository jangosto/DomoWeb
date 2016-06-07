<div id="divgrafico">
	<canvas id="grafico" height="<?php echo $altoGrafico;?>" width="<?php echo $anchoGrafico;?>"></canvas>
</div>

<script>
	var coordenadas=new Array();

	coordenadas[0]=new Array();
	coordenadas[0][0]=10;
	coordenadas[0][1]=400;

	coordenadas[1]=new Array();
	coordenadas[1][0]=40;
	coordenadas[1][1]=200;

	coordenadas[2]=new Array();
	coordenadas[2][0]=70;
	coordenadas[2][1]=300;

	var canvas = document.getElementById('grafico');
	var context = canvas.getContext('2d');

	dibujar(context, coordenadas);

	function dibujar(contexto,puntos){
		var longCoordenadas = puntos.length;
		var anchoGrafico = $("#grafico").width();
		var altoGrafico = $("#grafico").height();
		var espacioPuntosAbcisas = <?php echo ($largoEjeAbcisas/$numPuntosAbcisas);?>;
		var espacioPuntosOrdenadas = <?php echo ($largoEjeOrdenadas/$numPuntosOrdenadas);?>;
		var contadorMargenPuntos = 0;
		var contadorPuntos = 0;

		var i=1;

		context.beginPath();

		//Pintando los ejes de coordenadas
		context.moveTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?>);
		context.lineTo(<?php echo ($anchoGrafico/2+$largoEjeAbcisas/2);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?>);
		context.moveTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?>);
		context.lineTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?>, <?php echo ($altoGrafico/2-$largoEjeOrdenadas/2);?>);

		//Pintando puntos del eje de Abcisas
		contadorMargenPuntos = espacioPuntosAbcisas;
		contadorPuntos = 0;
		while(contadorMargenPuntos < <?php echo $largoEjeAbcisas;?>){
			context.moveTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?> + contadorMargenPuntos, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?>);
			if(contadorPuntos%<?php echo $margenPuntosImportantes;?> == 0){
				context.lineTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?> + contadorMargenPuntos, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2+$largoLineasPuntosImportantes);?>);
			}
			else{
				context.lineTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?> + contadorMargenPuntos, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2+$largoLineasPuntos);?>);
			}
			contadorMargenPuntos = contadorMargenPuntos + espacioPuntosAbcisas;
			contadorPuntos++;
		}

		//Pintando puntos del eje de Ordenadas
		contadorMargenPuntos = espacioPuntosOrdenadas;
		contadorPuntos = 0;
		while(contadorMargenPuntos < <?php echo $largoEjeOrdenadas;?>){
			context.moveTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?> - contadorMargenPuntos);
			if(contadorPuntos%<?php echo $margenPuntosImportantes;?> == 0){
				context.lineTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2-$largoLineasPuntosImportantes);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?> - contadorMargenPuntos);
			}
			else{
				context.lineTo(<?php echo ($anchoGrafico/2-$largoEjeAbcisas/2-$largoLineasPuntos);?>, <?php echo ($altoGrafico/2+$largoEjeOrdenadas/2);?> - contadorMargenPuntos);
			}
			contadorMargenPuntos = contadorMargenPuntos + espacioPuntosOrdenadas;
			contadorPuntos++;
		}

		//Pintando la gráfica
		while(i<longCoordenadas){			
			context.moveTo(puntos[i-1][0], puntos[i-1][1]);
			context.lineTo(puntos[i][0], puntos[i][1]);
			context.strokeStyle = '#000';

			i++;
		}
		context.stroke();
	}
</script>
