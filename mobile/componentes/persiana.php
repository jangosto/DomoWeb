<div id="persiana">
	<div class="relativizador">
		<div id="divcontrolpersiana">
			<div class="demo">
				<input id="controlpersiana" class="knob" data-max="100" data-min="0" data-displayPrevious=true value="44" data-role="none"/>
			</div>
		</div>
		<div id="divindicapersiana">
			
		</div>
	</div>
</div>

<script>
	$("document").ready(function () {
		//$("#controlpersiana").simpleSlider("setValue", 50);
		//$("#resultadopersiana").html($("#controlpersiana").value);
	});

	$("#controlpersiana").bind("slider:changed", function (event, data) {
		//$("#resultadopersiana").html(data.value);
	});
</script>
