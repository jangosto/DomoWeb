<div id="reloj">
	<div id="hora1" class="digito"></div>
	<div id="hora0" class="digito"></div>
	<div id="minuto1" class="digito"></div>
	<div id="minuto0" class="digito"></div>
	<div id="segundo1" class="digito"></div>
	<div id="segundo0" class="digito"></div>
</div>

<script>
	var anchoSprite = -20;
	var altoSprite = -26;
	var indiceHora1 = 0;
	var indiceHora0 = 1;
	var indiceMinuto1 = 2;
	var indiceMinuto0 = 3;
	var indiceSegundo1 = 4;
	var indiceSegundo0 = 5;
	var tiempoTransicion = 25;

	var horas;
	var minutos;
	var segundos;

	var cuenta = 0;
	var transicion = [0,0,0,0,0,0];
	var intervalos = [0,0,0,0,0,0];

	iniciarReloj();

	function iniciarReloj(){
		fecha = new Date();
		horas=fecha.getHours();
		minutos=fecha.getMinutes();
		segundos=fecha.getSeconds();

		$("#segundo0").css("background-position","0px "+(segundos%10*altoSprite)+"px");
		$("#segundo1").css("background-position","0px "+(parseInt(segundos/10)*altoSprite)+"px");
		$("#minuto0").css("background-position","0px "+(minutos%10*altoSprite)+"px");
		$("#minuto1").css("background-position","0px "+(parseInt(minutos/10)*altoSprite)+"px");
		$("#hora0").css("background-position","0px "+(horas%10*altoSprite)+"px");
		$("#hora1").css("background-position","0px "+(parseInt(horas/10)*altoSprite)+"px");
	}

	function cuentaTiempo(){
		var horas1;
		var horas0;
		var minutos1;
		var minutos0;
		var segundos1;
		var segundos0;
		var horas1_anterior = parseInt(horas/10);
		var horas0_anterior = horas%10;
		var minutos1_anterior = minutos1=parseInt(minutos/10);
		var minutos0_anterior = minutos%10;
		var segundos1_anterior = parseInt(segundos/10);
		var segundos0_anterior = segundos%10;
		var cambioHora=false;
		var cambioMinuto=false;

		segundos++;

		if(segundos>59){
			segundos=0;
			minutos++;
			cambioMinuto=true;
		}
		if(minutos>59){
			minutos=0;
			horas++;
			cambioHora=true;
		}
		if(horas>23){
			horas=0;
		}

		horas1=parseInt(horas/10);
		horas0=horas%10;
		minutos1=parseInt(minutos/10);
		minutos0=minutos%10;
		segundos1=parseInt(segundos/10);
		segundos0=segundos%10;

		if(horas1_anterior != horas1){
			window.clearInterval(intervalos[indiceHora1]);
			intervalos[indiceHora1] = window.setInterval("cambioNumero("+horas1_anterior+","+horas1+",'hora1',"+indiceHora1+")",tiempoTransicion);
		}
		if(horas0_anterior != horas0){
			window.clearInterval(intervalos[indiceHora0]);
			intervalos[indiceHora0] = window.setInterval("cambioNumero("+horas0_anterior+","+horas0+",'hora0',"+indiceHora0+")",tiempoTransicion);
		}
		if(minutos1_anterior != minutos1){
			window.clearInterval(intervalos[indiceMinuto1]);
			intervalos[indiceMinuto1] = window.setInterval("cambioNumero("+minutos1_anterior+","+minutos1+",'minuto1',"+indiceMinuto1+")",tiempoTransicion);
		}
		if(minutos0_anterior != minutos0){
			window.clearInterval(intervalos[indiceMinuto0]);
			intervalos[indiceMinuto0] = window.setInterval("cambioNumero("+minutos0_anterior+","+minutos0+",'minuto0',"+indiceMinuto0+")",tiempoTransicion);
		}
		if(segundos1_anterior != segundos1){
			window.clearInterval(intervalos[indiceSegundo1]);
			intervalos[indiceSegundo1] = window.setInterval("cambioNumero("+segundos1_anterior+","+segundos1+",'segundo1',"+indiceSegundo1+")",tiempoTransicion);
		}
		if(segundos0_anterior != segundos0){
			window.clearInterval(intervalos[indiceSegundo0]);
			intervalos[indiceSegundo0] = window.setInterval("cambioNumero("+segundos0_anterior+","+segundos0+",'segundo0',"+indiceSegundo0+")",tiempoTransicion);
		}
	}

	function cambioNumero(anterior,actual,idElem,indice){
		if(transicion[indice] < 7){
			$("#"+idElem).css("background-position",(anchoSprite*transicion[indice])+"px "+(anterior*altoSprite)+"px");
			transicion[indice]++;
		}
		else{
			$("#"+idElem).css("background-position","0px "+(actual*altoSprite)+"px");
			transicion[indice] = 0;
			window.clearInterval(intervalos[indice]);
		}
	}

	$(window).load(function(){
		window.setInterval("cuentaTiempo()",1000);
		window.setInterval("iniciarReloj()",63500);
	});
</script>
