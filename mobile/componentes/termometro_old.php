<div id="termometro">
	<div id="nombretermo" class="titulocomponente"><h6>Term&oacute;metro</h6></div>
	<div id="barratermo" class="barra-progreso espejo">
		<div id="barrafrio" class="barra barraazul"></div>
		<div id="barrabien" class="barra barraverde"></div>
		<div id="barracalor" class="barra barrarojo"></div>
	</div>
	<div id="indicatermo">
		<h3 id="tempactual">-25.5&deg;C</h3>
		<form class="form-search" action="enviaTemperatura()">
			<div class="input-append">
				<input type="text" class="input-mini search-query">
				<button type="submit" class="btn btn-success btn-mini">Fijar</button>
			</div>
		</form>
		<h3 id="tempdeseada">26&deg;C</h3>
	</div>
	<!--<div id="metricatermo" class="metrica">
		<ul id="listametricatermo" class="lista">
			<li class="valor valor-extremo"><div>50</div></li>
			<li class="valor valor-extremo"><div>40</div></li>
			<li class="valor valor-bien"><div>30</div></li>
			<li class="valor valor-bien"><div>20</div></li>
			<li class="valor valor-bien"><div>10</div></li>
			<li class="valor valor-extremo"><div>0</div></li>
			<li class="valor valor-extremo"><div>-10</div></li>
			<li class="valor valor-extremo"><div>-20</div></li>
			<li class="valor valor-extremo"><div>-30</div></li>
		</ul>
	</div>-->
</div>
<script>
	if(window.opera){
		document.getElementById("termometro").setAttribute("style","left:-145px;");
	}
</script>
