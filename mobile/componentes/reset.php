<div id="reset">
	<div class="relativizador">
		<div id="divreset">
			<a id="boton-reset" href="#" data-role="button" data-inline="true">Resetear Sistema</a>
		</div>
	</div>
</div>

<script>
	var ipServidor = location.host;

	$("#boton-reset").click(function(){
        $.ajax({
            type:"GET",
            url:"http://"+ipServidor+":11000/",
            data:{comando: "FFFF_RST"}
        });
    });
</script>