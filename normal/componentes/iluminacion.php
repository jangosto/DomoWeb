<?php
    $procesoCompletado = 0;
    $procesoAutoLuzCompletado = 0;
    $luzEncendida = "false";
    $valorLuz = 0;
    $autoLuzEncendido = false;
    $manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);
    $query = "SELECT b.alias AS alias, tabla.valor AS valor FROM (SELECT a.id AS idvalor, a.idvariable AS idvariable, a.valor AS valor FROM registrovars AS a LEFT JOIN zonas AS b ON a.idzona=b.id WHERE b.idcontrolador=0x".$idZonaAcuadorLuz." OR b.idcontrolador=0x".$idZonaSensor.") AS tabla LEFT JOIN variables as b ON tabla.idvariable=b.id WHERE b.alias='intensidad luz fijada usuario' OR b.alias='autoluz on' ORDER BY tabla.idvalor DESC;";
    //$query = "SELECT tabla.valor FROM (SELECT a.id AS idvalor, a.idzona AS idzona, a.valor AS valor FROM registrovars AS a LEFT JOIN variables AS b ON a.idvariable=b.id WHERE b.alias='intensidad luz fijada usuario' ORDER BY a.id) AS tabla LEFT JOIN zonas AS b ON tabla.idzona=b.id WHERE b.idcontrolador=0x".$idZonaAcuadorLuz.";";

    if($resultado = $manejadorBBDD->query($query)){
        $i = 0;
        while(($linea = $resultado->fetch_object()) && ($procesoCompletado == 0 || $procesoAutoLuzCompletado == 0)){
            if($linea->alias == "intensidad luz fijada usuario" && $procesoCompletado == 0){
                $valorLuz = round(sqrt(((int) $linea->valor)*100));
                if($valorLuz != 0){
                    $procesoCompletado = 1;
                    if($i>0){
                        $luzEncendida = "false";
                    }
                    else{
                        $luzEncendida = "true";
                    }
                }
            }
            elseif($linea->alias == "autoluz on" && $procesoAutoLuzCompletado == 0){
                $procesoAutoLuzCompletado = 1;
                if($linea->valor != 0){
                    $autoLuzEncendido = true;
                }
                else{
                    $autoLuzEncendido = false;
                }
            }
            $i++;
        }
    }
?>

<div id="iluminacion">
	<div class="relativizador">
		<div id="divcontroluz">
			<div class="demo">
				<!-- input id="controluz" class="knob" data-max="100" data-min="0" data-displayPrevious=true value="44" data-role="none"/-->
                <input id="controluz" class="knob" data-angleOffset=45 data-angleArc=270 data-max="100" data-min="0" data-displayPrevious=true value="<?php echo $valorLuz; ?>" data-role="none"/>
                <div id="boton-encender-luz"></div>
                <input id="indicador-luz-estado" type="hidden" value="<?php echo $luzEncendida; ?>"></input>
			</div>
		</div>
        <div id="divcontrolautoluz">
            <button value="<?php echo $autoLuzEncendido?>" type="button" class="btn btn-<?php if($autoLuzEncendido===true){echo "success";}else{echo "danger";}?> navbar-btn"><?php if($autoLuzEncendido===true){echo "Desactivar";}else{echo "Activar";}?> Autoluz</button>
        </div>
		<!--div id="divindicaluz">
			<div id="divbombilla"></div>
		</div-->
	</div>
</div>

<script>
	var valorAnterior=0;
	
	$( document ).ready(function(value){
		var valorInicialLuzDeseada = $('#controluz').val();
        if($("#indicador-luz-estado").val()=="true"){
            $('#controluz').trigger('configure',{"fgColor":"#FF9933","inputColor":"#FF9933"});
        }
        else{
            $('#controluz').trigger('configure',{"fgColor":"#CCCCCC","inputColor":"#CCCCCC"});
        }
	});

    $("#boton-encender-luz").click(function(){
        var mensaje = "";
        var datoEnvio;

        if($("#indicador-luz-estado").val()=="true"){
            mensaje = "<?php echo $idZonaAcuadorLuz;?>_LZOFF";
        }
        else{
            mensaje = "<?php echo $idZonaAcuadorLuz;?>_LZON";
        }

        $.ajax({
            type:"GET",
            url:"http://"+ipServidor+":11000/",
            data:{comando: mensaje}
        });
            
        if($("#indicador-luz-estado").val()=="true"){
            $('#controluz').trigger('configure',{"fgColor":"#CCCCCC","inputColor":"#CCCCCC"});
            $("#indicador-luz-estado").val("false");
        }
        else{
            $('#controluz').trigger('configure',{"fgColor":"#FF9933","inputColor":"#FF9933"});
            $("#indicador-luz-estado").val("true");

            datoEnvio = $("#controluz").val();

            $.ajax({
                type:"GET",
                url:"http://"+ipServidor+":11000/",
                data:{comando: "<?php echo $idZonaAcuadorLuz;?>_LZFR_"+Math.round(Math.pow(parseInt(datoEnvio),2)/100)}
            });
        }
    });
    
    $("#divcontrolautoluz button").click(function(){
        var mensaje = "";
        var datoEnvio;

        if($("#divcontrolautoluz button").val()=="true"){
            mensaje = "<?php echo $idZonaSensor;?>_AUTLZOFF";
        }
        else{
            mensaje = "<?php echo $idZonaSensor;?>_AUTLZON";
        }

        $.ajax({
            type:"GET",
            url:"http://"+ipServidor+":11000/",
            data:{comando: mensaje}
        });
            
        if($("#divcontrolautoluz button").val()=="true"){
            $('#divcontrolautoluz button').removeClass("btn-success");
            $('#divcontrolautoluz button').addClass("btn-danger");
            $('#divcontrolautoluz button').html("Activar Autoluz");
            $("#divcontrolautoluz button").val("false");
        }
        else{
            $('#divcontrolautoluz button').removeClass("btn-danger");
            $('#divcontrolautoluz button').addClass("btn-success");
            $('#divcontrolautoluz button').html("Desactivar Autoluz");
            $("#divcontrolautoluz button").val("true");
        }
    });
	
	$("#controluz").knob({
		'cursor':anchoCursor,
    	'width':anchoDial,
        change : function (value) {
            if($("#indicador-luz-estado").val()=="true"){
    			//$('#divbombilla').css('opacity',value/100);
    			var dato = value;//$('#controluz').val();
    			if((dato!==undefined & dato%5==0) | Math.abs(valorAnterior-dato)>5){ //Segunda alternativa: si el usuario fija un valor sin deslizar el mando y este no es múltiplo de 5.
    				valorAnterior=dato;
    				var datoEnvio=Math.pow(dato,2)/100; //Esto se hace para que el cambio de iluminación sea más gradual para el ojo humano
    				//var datoEnvio=dato;
    				$.ajax({
    					type:"GET",
    					url:"http://"+ipServidor+":11000/",
    					//url:"../enviarOrden.php",
    					data:{comando: "<?php echo $idZonaAcuadorLuz;?>_LZFR_"+Math.round(datoEnvio)}
    				});
    			}
            }
		},
        release : function (value) {
            //console.log(this.$.attr('value'));
            //console.log("release : " + value);
        },
        cancel : function () {
            //console.log("cancel : ", this);
        },
        draw : function () {
            // "tron" case
            if(this.$.data('skin') == 'tron') {

                var a = this.angle(this.cv) // Angle
                    , sa = this.startAngle // Previous start angle
                    , sat = this.startAngle // Start angle
                    , ea // Previous end angle
                    , eat = sat + a // End angle
                    , r = 1;

                this.g.lineWidth = this.lineWidth;

                this.o.cursor
                    && (sat = eat - 0.3)
                    && (eat = eat + 0.3);

                if (this.o.displayPrevious) {
                    ea = this.startAngle + this.angle(this.v);
                    this.o.cursor
                        && (sa = ea - 0.3)
                        && (ea = ea + 0.3);
                    this.g.beginPath();
                    this.g.strokeStyle = this.pColor;
                    this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sa, ea, false);
                    this.g.stroke();
                }

                this.g.beginPath();
                this.g.strokeStyle = r ? this.o.fgColor : this.fgColor ;
                this.g.arc(this.xy, this.xy, this.radius - this.lineWidth, sat, eat, false);
                this.g.stroke();

                this.g.lineWidth = 2;
                this.g.beginPath();
                this.g.strokeStyle = this.o.fgColor;
                this.g.arc( this.xy, this.xy, this.radius - this.lineWidth + 1 + this.lineWidth * 2 / 3, 0, 2 * Math.PI, false);
                this.g.stroke();

                return false;
            }
        }
	});
</script>
