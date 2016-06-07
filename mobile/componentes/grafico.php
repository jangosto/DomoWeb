<script type="text/javascript" src="https://www.google.com/jsapi"></script>

<?php
    $variables = array();
    $zonas = array();

    $manejadorBBDD = new mysqli($BBDD_host, $BBDD_user, $BBDD_pass, $BBDD_name);
    $query = "SELECT * FROM variables";

    if($resultado = $manejadorBBDD->query($query)){
        $i = 0;
        while($linea = $resultado->fetch_object()){
            $variables[$i]["nombre"] = utf8_encode($linea->nombre);
            $variables[$i]["id"] = $linea->alias;
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
                    <option value="">--- Seleccione una Zona ---</option>
                    <?php foreach($zonas as $zona){ ?>
                        <option value="<?php echo $zona["id"]; ?>"><?php echo $zona["nombre"]; ?></option>
                    <?php } ?>
                </select>
                <div id="checkVarBox">
                    <?php foreach($variables as $variable){ ?>
                        <input type="checkbox" value="<?php echo $variable["id"]; ?>"/><?php echo $variable["nombre"]; ?>
                    <?php } ?>
                </div>
                <input id="fechaInicio" type="datetime-local"/>
                <input id="fechaFin" type="datetime-local"/>
                <input type="button" onclick="mostrarGrafica()"/>
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
        //variable : "temperatura"
    };

    //console.log(datos);

    google.load("visualization", "1", {packages:["corechart"]});
    //google.setOnLoadCallback(drawChart);
    google.setOnLoadCallback(function(){
        pedirDatos(datos);
    });

    function mostrarGrafica(){
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

        var datosPet = {
            tipoPeticion : "valores",
            fechas : {
                fechaIni : fechaIni,
                fechaFin : fechaFin
            },
            zona : "276B",
            variable : "intensidad luz"
            //variable : "temperatura"
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
                var datosGrafica = Array();
                var ejeX = Array();
                var anterior="";
                datosGrafica[0] = ['Fecha','Luz'];

                var i = 1;
                var j = 0;
                resultado.forEach(function(dato){
                    var aux = dato.fecha.split(" ");
                    var diaAux = aux[0].split("-");
                    var horaAux = aux[1].split(":");
                    //console.log(horaAux[0]+"/"+horaAux[1]);
                    if(horaAux[0]=="00" && anterior!=diaAux[2] && i><?php echo $inicioEjeX; ?>){
                        //alert(anterior+" / "+diaAux[2]);
                        ejeX[j] = new Date(diaAux[0],diaAux[1]-1,diaAux[2],horaAux[0],horaAux[1],horaAux[2]);
                        anterior = diaAux[2];
                        j++;
                    }

                    datosGrafica[i] = [new Date(diaAux[0],diaAux[1]-1,diaAux[2],horaAux[0],horaAux[1],horaAux[2]),parseFloat(dato.valor)];
                    i++;
                });
                //alert("hola");
                //console.log(resultado);
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
            title: 'Temperatura de la Vivienda',
            hAxis: {title: 'Fecha',  titleTextStyle: {color: '#333'}, ticks: hAxisTicks, format:'d-M-y'},
            animation: {duration: 1000, easing: 'inAndOut'},
            vAxis: {minValue: 0}
        };

        var chart = new google.visualization.AreaChart(document.getElementById('grafica'));
        chart.draw(data, options);
    }
</script>