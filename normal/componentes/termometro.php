<?php
	$manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);
    $query = "SELECT valores.* FROM (SELECT a.id AS idvalor, a.valor AS valor, b.simbolounidad AS simbolounidad, a.idzona AS idzona FROM registrovars AS a LEFT JOIN variables AS b ON a.idvariable=b.id WHERE b.alias='temperatura') AS valores LEFT JOIN zonas as zonas ON valores.idzona=zonas.id WHERE zonas.idcontrolador=0x".$idZonaSensor." ORDER BY valores.idvalor DESC LIMIT 1";

    if($resultado = $manejadorBBDD->query($query)){
        $i = 0;
        while($linea = $resultado->fetch_object()){
            $temperatura = $linea->valor;
            $unidad = $linea->simbolounidad;
        }
    }
?>

<div id="termometro">
	<div class="relativizador">
		<div id="temp-deseada" data-role="none">
			<div class="demo">
			    <input id="controltemp" class="knob" data-max="50" data-min="0" data-displayPrevious=true value="9" data-role="none"/>
			    <div id="boton-encender-temp"></div>
                <input id="indicador-temp-estado" type="hidden" value="false"></input>
			</div>
			<!--form class="form-search" action="enviaTemperatura()">
				<div class="input-append">
					<input type="text" class="input-mini search-query">
					<button type="submit" class="btn btn-success btn-mini">Fijar</button>
				</div>
			</form>
			<h2 id="indictempdeseada">26&deg;C</h2-->
		</div>
		<div class="absolutizador" id="absolutizador-termometro">
			<div id="thermo1" class="thermometer">

				<div class="track">
				    <div class="goal">
				        <div class="amount"> 50 </div>
				    </div>
				    <div class="progress">
				        <div class="amount"> <?php echo $temperatura; ?> </div>
				    </div>
				</div>
			</div>
		</div>	
    </div>
</div>
<script>
    $( document ).ready(function(value){
		var valorInicialTempDeseada = $("#controltemp").val();

		if($("#indicador-temp-estado").val()=="true"){
			if(valorInicialTempDeseada < 21){
				$('#controltemp').trigger('configure',{"fgColor":"#0000FF","inputColor":"#0000FF"});
			}
			else if(valorInicialTempDeseada >= 21 && valorInicialTempDeseada <= 28){
				$('#controltemp').trigger('configure',{"fgColor":"#00FF00","inputColor":"#00FF00"});
			}
			else{
				$('#controltemp').trigger('configure',{"fgColor":"#FF0000","inputColor":"#FF0000"});
			}
		}
		else{
			$('#controltemp').trigger('configure',{"fgColor":"#CCCCCC","inputColor":"#CCCCCC"});
		}

		setInterval(function(){actualizarTemperatura();},60000);
	});

	$("#boton-encender-temp").click(function(){
        var mensaje = "";

        if($("#indicador-temp-estado").val()=="true"){
            mensaje = "<?php echo $idZonaSensor;?>_AUTTMPOFF";
        }
        else{
            mensaje = "<?php echo $idZonaSensor;?>_AUTTMPON";
        }

        $.ajax({
            type:"GET",
            url:"http://"+ipServidor+":11000/",
            data:{comando: mensaje}
        });
            
        if($("#indicador-temp-estado").val()=="true"){
            $('#controltemp').trigger('configure',{"fgColor":"#CCCCCC","inputColor":"#CCCCCC"});
            $("#indicador-temp-estado").val("false");
        }
        else{
        	valorTempDeseada = $("#controltemp").val();

            if(valorTempDeseada < 21){
				$('#controltemp').trigger('configure',{"fgColor":"#0000FF","inputColor":"#0000FF"});
			}
			else if(valorTempDeseada >= 21 && valorTempDeseada <= 28){
				$('#controltemp').trigger('configure',{"fgColor":"#00FF00","inputColor":"#00FF00"});
			}
			else{
				$('#controltemp').trigger('configure',{"fgColor":"#FF0000","inputColor":"#FF0000"});
			}
            $("#indicador-temp-estado").val("true");

            valorTempDeseada = $("#controltemp").val();

            $.ajax({
                type:"GET",
                url:"http://"+ipServidor+":11000/",
                data:{comando: "<?php echo $idZonaSensor;?>_TMPDS_"+parseInt(valorTempDeseada)}
            });
        }
    });
	
	$("#controltemp").knob({
		'cursor':anchoCursor,
		'width':anchoDial,
		change : function (value) {
		    //console.log("change : " + value);
		    if($("#indicador-temp-estado").val()=="true"){
			    if(value < 21){
			    	$('#controltemp').trigger('configure',{"fgColor":"#0000FF","inputColor":"#0000FF"});
				}
				else if(value >= 21 && value <= 28){
					$('#controltemp').trigger('configure',{"fgColor":"#00FF00","inputColor":"#00FF00"});
				}
				else{
					$('#controltemp').trigger('configure',{"fgColor":"#FF0000","inputColor":"#FF0000"});
				}
			}
		},
        release : function (value) {
            var mensaje;

            mensaje = "<?php echo $idZonaSensor;?>_TMPDS_"+parseInt(value);

            $.ajax({
                type:"GET",
                url:"http://"+ipServidor+":11000/",
                data:{comando: mensaje}
            });
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

	function actualizarTemperatura(){
		var datosPeticion = {
            tipoPeticion : "valorActual",
            zona : '<?php echo $idZonaSensor; ?>',
            variable : "temperatura"
        };

		var request = $.ajax({
            url: "peticionesAjax/dataBaseRequest.php",
            type: "POST",
            data: datosPeticion,
            dataType: "json",
            success: function(resultado){
            	jQuery.each(resultado,function(iteracion,dato){
	            	//alert(dato.valor);
	                $("#thermo1 .progress .amount").html(dato.valor);
	                //alert($("#thermo1 .progress .amount").val());
	                thermometer("thermo1");
            	});
            }
        });		
	}
	
</script>

