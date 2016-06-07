<div id="persiana">
	<div id="nombrepers" class="titulocomponente"><h6>Persiana</h6></div>
	<input id="controlpersiana" type="text" data-slider="true" data-slider-range="0,100" data-slider-step="1" />

	<h3 id="resultadopersiana"></h3>
</div>

<script>
	$("document").ready(function () {
		$("#controlpersiana").simpleSlider("setValue", 50);
		$("#resultadopersiana").html(($("#controlpersiana").val())+"%");
	});

	$("#controlpersiana").bind("slider:changed", function (event, data) {
		$("#resultadopersiana").html((data.value)+"%");
	});
</script>
