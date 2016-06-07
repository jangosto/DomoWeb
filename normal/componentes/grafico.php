<script type="text/javascript" src="https://www.google.com/jsapi"></script>

<?php
    $variables = array();
    $zonas = array();

    $manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);
    $query = "SELECT * FROM variables";

    if($resultado = $manejadorBBDD->query($query)){
        $i = 0;
        while($linea = $resultado->fetch_object()){
            if($linea->id == 1 || $linea->id == 2){
                $variables[$i]["nombre"] = utf8_encode($linea->nombre);
                $variables[$i]["id"] = $linea->alias;
            }
            $i++;
        }
    }

    $query = "SELECT * FROM zonas";

    if($resultado = $manejadorBBDD->query($query)){
        $i = 0;
        while($linea = $resultado->fetch_object()){
            $zonas[$i]["nombre"] = utf8_encode($linea->nombre);
            $zonas[$i]["id"] = $linea->idcontrolador;
            $i++;
        }
    }
?>

<div id="divgrafica">
    <div class="relativizador">
        <div id="formulario">
            <form id="selector">
                <select id="zona">
                    <option value="">--- Zona ---</option>
                    <?php foreach($zonas as $zona){ ?>
                        <option value="<?php echo $zona["id"]; ?>"><?php echo $zona["nombre"]; ?></option>
                    <?php } ?>
                </select>
                <div id="divFechaIni">
                    <label for="fechaInicio">Fecha Inicial</label>
                    <input id="fechaInicio" type="datetime-local"/>
                </div>
                <div id="divFechaFin">
                    <label for="fechaFin">Fecha Final</label>
                    <input id="fechaFin" type="datetime-local"/>
                </div>
                <div id="checkVarBox">
                    <label id="labelVariables" for="checkVarBox">Variables Visibles: </label>
                    <?php foreach($variables as $variable){ ?>
                        <input type="radio" class="selector-check" name='datoVariable' value="<?php echo $variable["id"]; ?>"/><?php echo $variable["nombre"]; ?>
                    <?php } ?>
                </div>
                <input type="button" id="botonActualizarGraf" value="Visualizar Datos" onclick="mostrarGrafica()"/>
            </form>
        </div>
        <div id="grafica"></div>
    </div>
</div>

<script>
    var meses = ["01","02","03","04","05","06","07","08","09","10","11","12"];
    var fechaAhora = new Date();
    if(fechaAhora.getDate()<10){
        var dia = "0"+fechaAhora.getDate();
    }
    else{
        var dia = fechaAhora.getDate();
    }
    var datos = {
        tipoPeticion : "valores",
        fechas : {
            fechaIni : fechaAhora.getFullYear()+"-"+meses[fechaAhora.getMonth()]+"-"+dia+" 00:00:00",
            fechaFin : ""
        },
        zona : "276B",
        variable : "intensidad luz"
                    //"temperatura"
    };

    //console.log(datos);

    google.load("visualization", "1", {packages:["corechart"]});
    //google.setOnLoadCallback(drawChart);
    google.setOnLoadCallback(function(){
        pedirDatos(datos);
    });

    function mostrarGrafica(){
        var idZona = $('#zona').val();

        if(idZona != ""){
            idZona = Number(idZona).toString(16).toUpperCase();
        }

        var valor = $('#fechaInicio').val();
        if(valor != ""){
            var fechaIni = valor.replace("T"," ")+":00";
        }
        else{
            var fechaIni = "";
        }
        valor = $('#fechaFin').val();
        if(valor != ""){
            var fechaFin = valor.replace("T"," ")+":00";
        }
        else{
            var fechaFin = "";
        }

        var variables = new Array();
        var contadorArrayVariables = 0;
        $('#checkVarBox .selector-check').each(function(index){
            if($(this).is(':checked')){
                variable = $(this).val();
                contadorArrayVariables++;
            }
        });

        var datosPet = {
            tipoPeticion : "valores",
            fechas : {
                fechaIni : fechaIni,
                fechaFin : fechaFin
            },
            zona : idZona,
            variable : variable
        };
        pedirDatos(datosPet);
    }

    function pedirDatos(datosPeticion){
        //var jsonDatos = JSON.stringify(datosPeticion);

        //console.log(jsonDatos);

        var request = $.ajax({
            url: "peticionesAjax/dataBaseRequest.php",
            type: "POST",
            data: datosPeticion,
            dataType: "json",
            success: function(resultado){
                //console.log(resultado);
                var datosGrafica = Array();
                var ejeX = Array();
                var anterior="0";
                datosGrafica[0] = ['Fecha',resultado[1]['nomVariable']];

                var i = 1;
                var j = 0;
                jQuery.each(resultado,function(iteracion,dato){
                    var aux = dato.fecha.split(" ");
                    var diaAux = aux[0].split("-");
                    var horaAux = aux[1].split(":");
                    //console.log(horaAux[0]+"/"+horaAux[1]);
                    if(horaAux[0]=="00" && anterior!=diaAux[2]){
                        //alert(anterior+" / "+diaAux[2]);
                        ejeX[j] = new Date(diaAux[0],diaAux[1]-1,diaAux[2],horaAux[0],horaAux[1],horaAux[2]);
                        anterior = diaAux[2];
                        j++;
                    }

                    datosGrafica[i] = [new Date(diaAux[0],diaAux[1]-1,diaAux[2],horaAux[0],horaAux[1],horaAux[2]),parseFloat(dato.valor)];
                    i++;
                });
                //alert("hola");
                //console.log(datosGrafica);
                drawChart(datosGrafica,ejeX);
            }
        });
    }

    function drawChart(arrayDatos,hAxisTicks) {
        //console.log(hAxisTicks);
        //console.log(arrayDatos);
        var data = google.visualization.arrayToDataTable(arrayDatos);
        /*[
            ['Year', 'Sales', 'Expenses'],
            ['2013',  1000,      400],
            ['2014',  1170,      460],
            ['2015',  660,       1120],
            ['2016',  1030,      540]
        ]*/

        var options = {
            title: arrayDatos[0][1]+' de la Vivienda',
            hAxis: {title: 'Fecha',  titleTextStyle: {color: '#333'}, ticks: hAxisTicks, format:'d-M-y'},
            animation: {duration: 1000, easing: 'inAndOut'},
            vAxis: {minValue: 0},
            height: 300
        };

        var chart = new google.visualization.AreaChart(document.getElementById('grafica'));
        chart.draw(data, options);
    }
</script>