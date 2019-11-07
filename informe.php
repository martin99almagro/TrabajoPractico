<?php
if(isset($_POST['total'])):

  $meses = [1 => 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  $dias = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
  $auxVars = array(
      'diaMasVotos' => ['cant' => 0],
      'diaMenosVotos' => ['cant' => ''],
      'horaMasVotos' => ['cant' => 0],
      'mejorDiaMes' => ['nps' => 0],
      'peorDiaMes' => ['nps' => ''],
      'horaMasVotos' => ['cant' => 0],
      'mejorTurno' => ['nps' => 0],
      'peorTurno' => ['nps' => ''],
      'mejorTurnoLunVie' => ['nps' => 0],
      'peorTurnoSabDom' => ['nps' => ''],
    );

  include("../php/abrir_conexion.php");
	$desde= $_POST{'desde'};
	$hasta= $_POST{'hasta'};

  $diaDesde = date('d',strtotime($desde));
  $diaHasta = date('d',strtotime($hasta));
  $mesDesde = $meses[date('n',strtotime($desde))];
  $mesHasta = $meses[date('n',strtotime($hasta))];
  $anioDesde = date('Y',strtotime($desde));
  $anioHasta = date('Y',strtotime($hasta));

	$turno= implode("','",$_POST{'turno'});
	$estacion= implode("','",$_POST{'estacion'});
  $cliente = $_POST['cliente'][0];

  $sql = "SELECT DISTINCT(history) FROM t_login WHERE client LIKE '".$cliente."'";

  $client =  mysqli_fetch_array(mysqli_query($conexion,$sql));

  $tabla = str_replace('Index_','',$client['history']);
  $tabla = str_replace('.php','',$tabla);
  mysqli_free_result();

  $sql = "SELECT turno, HOUR(turno_inicio) as hora_inicio, HOUR(turno_fin) as hora_fin FROM t_horarios WHERE cliente LIKE '".$cliente."' AND punto_de_venta IN ('".$estacion."') AND turno IN ('".$turno."')";
  $turnosQ =  mysqli_query($conexion,$sql);

  $turnos = array();
  while ( $row = $turnosQ->fetch_assoc() ) {
    $turnos[] = $row;
  }
  mysqli_free_result();

  /* GENERAL TOTAL */
  $totalSql = "SELECT ROUND((((Sum(h_1.v1))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS t_v1, ROUND((((Sum(h_1.v2))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS t_v2, ROUND((((Sum(h_1.v3))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS t_v3, ROUND((((Sum(h_1.v4))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS t_v4, Sum(h_1.v1 + h_1.v2 + h_1.v3 + h_1.v4) AS Total FROM ".$tabla." h_1 WHERE ((dia_turno) BETWEEN '$desde' AND '$hasta' AND c='0' AND (turno) IN ('".$turno."') AND (punto_de_venta) IN ('".$estacion."'))";
  $totalQuery = mysqli_query($conexion,$totalSql);
  $totalResult = mysqli_fetch_array($totalQuery);
  mysqli_free_result();

  /* NPS */
  $npsGeneral = $Math.round($totalResult['t_v1'] - $totalResult['t_v3'] - $totalResult['t_v4']);


  /* TURNOS */
  $turnosSql = "SELECT CONCAT(dia_turno, ' - ', turno) AS fecha_turno, CONCAT(dia_turno, ' - ',If(turno='Manana',' 1 - Mañana',If(turno='Tarde',' 2 - Tarde',If(turno='Noche',' 3 - Noche','Error')))) AS orden, Sum(h_1.v1) AS t_v1, Sum(h_1.v2) AS t_v2, Sum(h_1.v3) AS t_v3, Sum(h_1.v4) AS t_v4, ROUND((((Sum(h_1.v1))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS Excelencia FROM ".$tabla." h_1 WHERE ((dia_turno) BETWEEN '$desde' AND '$hasta' AND c='0' AND (turno) IN ('".$turno."') AND (punto_de_venta) IN ('".$estacion."')) GROUP BY CONCAT(dia_turno, ' - ',If(turno='Manana','Mañana',If(turno='Tarde','Tarde',If(turno='Noche','Noche','Error')))) ORDER BY orden ASC";
  $turnosQuery = mysqli_query($conexion,$turnosSql);

  $turnosData = array();
  while ( $row = $turnosQuery->fetch_assoc() ) {

    $aux = explode(' - ',$row['fecha_turno']);

    $row['fecha_turno'] = date('d/m',strtotime($aux[0])).' - '.mb_strtoupper(substr($aux[1],0,1));

    $row['Excelencia'] =  $Math.round($row['Excelencia']) ;

    $turnosData[] = $row;
  }

  /* DIARIO */

  $diarioSql = "SELECT dia_real, dia_turno, if(HOUR(hora)<10, CONCAT(dia_real, ' - 0', HOUR(hora)),CONCAT(dia_real, ' - ', HOUR(hora))) AS fecha_hora, Sum(h_1.v1) AS t_v1, Sum(h_1.v2) AS t_v2, Sum(h_1.v3) AS t_v3, Sum(h_1.v4) AS t_v4, ROUND((((Sum(h_1.v1))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS Excelencia FROM ".$tabla." h_1 WHERE ((dia_turno) BETWEEN '$desde' AND '$hasta' AND c='0' AND (turno) IN ('".$turno."') AND (punto_de_venta) IN ('".$estacion."')) GROUP BY if(HOUR(hora)<10, CONCAT(dia_real, ' - 0', HOUR(hora)),CONCAT(dia_real, ' - ', HOUR(hora)));";
  $diarioQuery = mysqli_query($conexion, $diarioSql);

  $diarioData = array();

  $diarioTotalData = array();

  $horasArray = array();
  $horasAux = array();

  $totalesHs = array();

  $promedioDia = array();
  $promedioHora = array();

  $promedioDiaTurno = array();

  $npsMinMax = ['min' => '', 'max' => 0];

  while ( $row = $diarioQuery->fetch_assoc() ) {

    $dia = date('N', strtotime($row['dia_turno']));
    $hora = intval(substr($row['fecha_hora'], -2));

    if($hora < 6){
      $horasAux[$hora] = $hora;
    }else{
      $horasArray[$hora] = $hora;
    }

    $diarioData[$dia][$hora] = $row;
    $diarioData[$dia][$hora]['total'] = intval($row['t_v1'] + $row['t_v2'] + $row['t_v3'] + $row['t_v4']);


    if($diarioData[$dia][$hora]['total'] > 0){
      $resta = intval($row['t_v1']) - intval($row['t_v3']) - intval($row['t_v4']);
      $nps = ($resta <= 0) ? 0 : $Math.round(($resta / $diarioData[$dia][$hora]['total'])*100);

      if($nps > $npsMinMax['max']){
        $npsMinMax['max'] = $nps;
      }

      if($npsMinMax['min'] == '' || $nps < $npsMinMax['min']){
        $npsMinMax['min'] = $nps;
      }

    }else{
      $resta = 0;
      $nps = '';
    }

    if(empty($diarioTotalData[$dia][$hora])){
      $diarioTotalData[$dia][$hora]['total'] = intval($row['t_v1'] + $row['t_v2'] + $row['t_v3'] + $row['t_v4']);
      $diarioTotalData[$dia][$hora]['nps'] = ($diarioTotalData[$dia][$hora]['total'] > 0) ? $nps : 0;
      $diarioTotalData[$dia][$hora]['divisor'] = ($diarioTotalData[$dia][$hora]['total'] > 0) ? 1 : 0;
    }else{
      $diarioTotalData[$dia][$hora]['total'] += intval($row['t_v1'] + $row['t_v2'] + $row['t_v3'] + $row['t_v4']);
      $diarioTotalData[$dia][$hora]['nps'] += ($diarioTotalData[$dia][$hora]['total'] > 0) ? $nps : 0;
      $diarioTotalData[$dia][$hora]['divisor'] += ($diarioTotalData[$dia][$hora]['total'] > 0) ? 1 : 0;
    }

    if(empty($promedioDia[$dia])){

        $promedioDia[$dia] = array(
          'total' => $diarioData[$dia][$hora]['total'],
          'nps' => ($nps == '') ? 0 : $nps,
          'divisor' => ($nps == '') ? 0 : 1,
        );

    }else{

      $promedioDia[$dia]['total'] += $diarioData[$dia][$hora]['total'];
      $promedioDia[$dia]['nps'] += ($nps == '') ? 0 : $nps;
      $promedioDia[$dia]['divisor'] += ($nps == '') ? 0 : 1;

    }

    if(empty($promedioHora[$hora])){

        $promedioHora[$hora] = array(
          'total' => $diarioData[$dia][$hora]['total'],
          'nps' => ($nps == '') ? 0 : $nps,
          'divisor' => ($nps == '') ? 0 : 1,
        );

    }else{

      $promedioHora[$hora]['total'] += $diarioData[$dia][$hora]['total'];
      $promedioHora[$hora]['nps'] += ($nps == '') ? 0 : $nps;
      $promedioHora[$hora]['divisor'] += ($nps == '') ? 0 : 1;

    }

    if($dia < 6){
      if(empty($promedioHora[$hora]['lunVie'])){
        $promedioHora[$hora]['lunVie'] = ($nps == '') ? 0 : $nps;
        $promedioHora[$hora]['lunVieDiv'] = ($nps == '') ? 0 : 1;
      }else{
        $promedioHora[$hora]['lunVie'] += ($nps == '') ? 0 : $nps;
        $promedioHora[$hora]['lunVieDiv'] += ($nps == '') ? 0 : 1;
      }
    }else{
      if(empty($promedioHora[$hora]['sabDom'])){
        $promedioHora[$hora]['sabDom'] = ($nps == '') ? 0 : $nps;
        $promedioHora[$hora]['sabDomDiv'] = ($nps == '') ? 0 : 1;
      }else{
        $promedioHora[$hora]['sabDom'] += ($nps == '') ? 0 : $nps;
        $promedioHora[$hora]['sabDomDiv'] += ($nps == '') ? 0 : 1;
      }
    }

    if(!empty($totalesHs[$hora])){
      $totalesHs[$hora]['total'] += $diarioData[$dia][$hora]['total'];
      $totalesHs[$hora]['resta'] += $resta;
      $totalesHs[$hora]['t_v1'] += intval($row['t_v1']);
      $totalesHs[$hora]['t_v2'] += intval($row['t_v2']);
      $totalesHs[$hora]['t_v3'] += intval($row['t_v3']);
      $totalesHs[$hora]['t_v4'] += intval($row['t_v4']);
    }else{
      $totalesHs[$hora] = array(
          'total' => $diarioData[$dia][$hora]['total'],
          'resta' => $resta,
          't_v1' => intval($row['t_v1']),
          't_v2' => intval($row['t_v2']),
          't_v3' => intval($row['t_v3']),
          't_v4' => intval($row['t_v4']),
        );
    }


    $diarioData[$dia][$hora]['nps'] = $nps;

    if(empty($nps)){
      $diarioData[$dia][$hora]['class'] = '';

      if($diarioData[$dia][$hora]['total'] > 0){
        if((intval($row['t_v1']) + intval($row['t_v3']) + intval($row['t_v4']) ) == 0){

          $diarioData[$dia][$hora]['class'] = 'x90';

        }elseif((intval($row['t_v1']) - intval($row['t_v3']) - intval($row['t_v4']) ) <= 0){

          $diarioData[$dia][$hora]['class'] = 'x0';

        }
      }
    }else{

      $diarioData[$dia][$hora]['class'] = 'x100';
      if($nps < 100){
        $diarioData[$dia][$hora]['class'] = 'x90';
      }
      if($nps < 90){
        $diarioData[$dia][$hora]['class'] = 'x80';
      }
      if($nps < 80){
        $diarioData[$dia][$hora]['class'] = 'x70';
      }
      if($nps < 70){
        $diarioData[$dia][$hora]['class'] = 'x60';
      }
      if($nps < 60){
        $diarioData[$dia][$hora]['class'] = 'x50';
      }
      if($nps < 50){
        $diarioData[$dia][$hora]['class'] = 'x40';
      }
      if($nps < 40){
        $diarioData[$dia][$hora]['class'] = 'x30';
      }
      if($nps < 30){
        $diarioData[$dia][$hora]['class'] = 'x20';
      }
      if($nps < 20){
        $diarioData[$dia][$hora]['class'] = 'x10';
      }
      if($nps < 10){
        $diarioData[$dia][$hora]['class'] = 'x0';
      }

    }

    foreach($turnos as $t){

      if($t['turno'] == 'Manana'){
        $t['turno'] = 'Mañana';
      }

      if($hora >= $t['hora_inicio'] && $hora <= $t['hora_fin'] && $diarioData[$dia][$hora]['total'] > 0){

        if(empty($promedioDiaTurno[$dia])){

            $promedioDiaTurno[$dia] = [$t['turno'] => ['nps' => $nps, 'cant' => 1]];

        }else{

            $promedioDiaTurno[$dia][$t['turno']]['nps'] += $nps;
            $promedioDiaTurno[$dia][$t['turno']][cant]++;

        }

      }

    }

  }

  ksort($diarioData);
  ksort($diarioTotalData);
  ksort($horasArray);
  ksort($horasAux);

  /* CONCLUSIONES*/

  $totalesTurnos = array();

   /* MEJOR DIA TURNO */
   $mejor_dia = ['dia' => '', 'turno' => '', 'nps' => 0, 'total' => 0];
   foreach($turnosData as $k => $dt){

     $total = intval($dt['t_v1']) + intval($dt['t_v2']) + intval($dt['t_v3']) + intval($dt['t_v4']);
     $resta = intval($dt['t_v1']) - intval($dt['t_v3']) - intval($dt['t_v4']);
     $nps = ($resta <= 0) ? 0 : $Math.round(($resta / $total)*100) ;

     $aux = explode(' - ', $dt['orden']);
     $aux[0] = strtotime($aux[0]);

     $dia = date('d', $aux[0]).' de '.$meses[date('n',$aux[0])];

     if(date('N',$aux[0]) < 6){
       $sem = 'lunVie';
     }else{
       $sem = 'sabDom';
     }
     if(!empty($totalesTurnos[$aux[2]])){

       $totalesTurnos[$aux[2]][$sem]['total'] += $total;
       $totalesTurnos[$aux[2]][$sem]['t_v1'] += intval($dt['t_v1']);
       $totalesTurnos[$aux[2]][$sem]['t_v2'] += intval($dt['t_v2']);
       $totalesTurnos[$aux[2]][$sem]['t_v3'] += intval($dt['t_v3']);
       $totalesTurnos[$aux[2]][$sem]['t_v4'] += intval($dt['t_v4']);
     }else{
       $totalesTurnos[$aux[2]][$sem]['total'] = $total;
       $totalesTurnos[$aux[2]][$sem]['t_v1'] = intval($dt['t_v1']);
       $totalesTurnos[$aux[2]][$sem]['t_v2'] = intval($dt['t_v2']);
       $totalesTurnos[$aux[2]][$sem]['t_v3'] = intval($dt['t_v3']);
       $totalesTurnos[$aux[2]][$sem]['t_v4'] = intval($dt['t_v4']);
     }

     if($nps > $mejor_dia['nps'] || ($nps == $mejor_dia['nps'] && $total > $mejor_dia['total'])){
       $mejor_dia = ['dia' => $dia, 'turno' => $aux[2], 'nps' => $nps, 'total' => $total];
     }

   }
   $promediosTurnos = array();
   $mejorTurno = array();

   $horasPorTurno = array();

   $turnosUnicos = array();


   foreach($turnos as $t){

     if($t['turno'] == 'Manana'){
       $t['turno'] = 'Mañana';
     }
     $turnosUnicos[$t['turno']] = $t['turno'];

     if(empty($mejorTurno[$t['turno']])){
       $mejorTurno[$t['turno']]['mejor'] = ['nps' => 0, 'total' => 0];
       $mejorTurno[$t['turno']]['peor'] = ['nps' => 100, 'total' => 0];
     }
     for($hh = $t['hora_inicio']; $hh <=  $t['hora_fin']; $hh++){

      $nps = ($totalesHs[$hh]['resta'] <= 0) ? 0 : $Math.round(($totalesHs[$hh]['resta'] / $totalesHs[$hh]['total'])*100) ;

      if(empty($horasPorTurno[$t['turno']])) {
        $horasPorTurno[$t['turno']] = ['horas' => ($nps > 0 ? 1 : 0), 'nps' => $nps];
      }else{
        $horasPorTurno[$t['turno']]['nps'] += $nps;
        if($nps > 0){
          $horasPorTurno[$t['turno']]['horas']++;
        }
      }

      if($nps > $mejorTurno[$t['turno']]['mejor']['nps'] || ($nps == $mejorTurno[$t['turno']]['mejor']['nps'] && $totalesHs[$hh]['total'] > $mejorTurno[$t['turno']]['mejor']['total'])){

        $mejorTurno[$t['turno']]['mejor']['nps'] = $nps;
        $mejorTurno[$t['turno']]['mejor']['total'] = $totalesHs[$hh]['total'];
        $mejorTurno[$t['turno']]['mejor']['hora'] = $hh;

      }

      if($nps < $mejorTurno[$t['turno']]['peor']['nps'] || ($nps == $mejorTurno[$t['turno']]['peor']['nps'] && $totalesHs[$hh]['total'] >= $mejorTurno[$t['turno']]['peor']['total'])){

        $mejorTurno[$t['turno']]['peor']['nps'] = $nps;
        $mejorTurno[$t['turno']]['peor']['total'] = $totalesHs[$hh]['total'];
        $mejorTurno[$t['turno']]['peor']['hora'] = $hh;

      }

     }

   }

   $totTur = array();
$totalHoras = count($horasAux)+count($horasArray);


  $mejorPeorDiaMes = array(
      'mejor' => ['nps' => 0, 'total' => 0],
      'peor' => ['nps' => '', 'total' => 0],
    );

  foreach($promedioDia as $diaP => $dataP){

    $npsDia = $Math.round($dataP['nps'] / $dataP['divisor']);

    if($mejorPeorDiaMes['mejor']['nps'] < $npsDia || ( $mejorPeorDiaMes['mejor']['nps'] == $npsDia &&  $mejorPeorDiaMes['mejor']['total'] < $dataP['total'] )){
      $mejorPeorDiaMes['mejor']['nps'] = $npsDia;
      $mejorPeorDiaMes['mejor']['dia'] = $dias[$diaP];
    }

    if($mejorPeorDiaMes['peor']['nps'] == '' || $mejorPeorDiaMes['peor']['nps'] > $npsDia || ( $mejorPeorDiaMes['peor']['nps'] == $npsDia &&  $mejorPeorDiaMes['peor']['total'] < $dataP['total'] )){
      $mejorPeorDiaMes['peor']['nps'] = $npsDia;
      $mejorPeorDiaMes['peor']['dia'] = $dias[$diaP];
    }

  }


  $auxVars['mejorDiaMes'] = $mejorPeorDiaMes['mejor']['dia'];
  $auxVars['peorDiaMes'] = $mejorPeorDiaMes['peor']['dia'];


  /*  OPERARIOS */

  $operariosSql = "SELECT t_turnos.operario AS Operario, Sum(h_1.v4) AS Malo, Sum(h_1.v3) AS Regular, Sum(h_1.v2) AS Bueno, Sum(h_1.v1) AS Excelente, Sum(h_1.v1 + h_1.v2 + h_1.v3 + h_1.v4) AS Total, ROUND((((Sum(h_1.v1))/((Sum(h_1.v1))+(Sum(h_1.v2))+(Sum(h_1.v3))+(Sum(h_1.v4))))*100),2) AS Excelencia FROM ".$tabla." h_1 LEFT JOIN t_turnos ON (h_1.punto_de_venta = t_turnos.punto_de_venta) AND (h_1.dia_turno = t_turnos.dia_turno) AND (h_1.turno = t_turnos.turno) AND (h_1.turno = t_turnos.turno) WHERE ((h_1.dia_turno) BETWEEN '$desde' AND '$hasta' AND c='0' AND (h_1.turno) IN ('".$turno."') AND (h_1.punto_de_venta) IN ('".$estacion."'))  GROUP BY t_turnos.operario ORDER BY Total DESC;";
  $operariosQuery = mysqli_query($conexion, $operariosSql);

 // Print out rows
  $dataOp = array();
  while ( $row = $operariosQuery->fetch_assoc() ) {
  $dataOp[] = $row;
  }

?>
<!doctype html>
<html>
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="css/informes.css">

  <script src="../js/fontawesome-all.min.js"></script>
  <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
  <script src="https://www.amcharts.com/lib/3/serial.js"></script>
  <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
  <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
  <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <style>
    .pc_100, .x100{
      background-color: #4FC3B3;
    }
    .pc_0, .x0{background-color: #C22821}
    <?php

    for($p = 99; $p > 0; $p--):
      if($p < 100){
        $color = '#67C190';
      }
      if($p < 90){
        $color = '#E9C563';
      }
      if($p < 80){
        $color = '#E8B264';
      }
      if($p < 70){
        $color = '#E5A865';
      }
      if($p < 60){
        $color = '#E0A368';
      }
      if($p < 50){
        $color = '#DB8916';
      }
      if($p < 40){
        $color = '#D86619';
      }
      if($p < 30){
        $color = '#D6411A';
      }
      if($p < 20){
        $color = '#C22821';
      }
      if($p < 10){
        $color = '#C22821';
      }
      echo '.pc_'.$p.', .x'.$p.'{ background-color: '.$color.'}';
    endfor;
    ?>

  </style>
</head>
<body>
	<header id="header">
    <div class="container">
      <div class="row">
        <div class="col-6 font-weight-bold">
          <input class="nombre_cliente" type="text" value="<?php echo $cliente ?>">
        </div>
        <div class="col-6 header-info">
          <input class="nombre_puesto" type="text" value="<?php echo mb_strtoupper($estacion) ?>">
          <?php if($mesDesde != $mesHasta): ?>
            <h3><?php echo $diaDesde.' de '.$mesDesde.' a '.$diaHasta.' de '.$mesHasta.' '.$anioHasta ?></h3>
          <?php else: ?>
            <h3><?php echo $diaDesde.' a '.$diaHasta. ' de '.$mesDesde.' '.$anioDesde ?></h3>
          <?php endif; ?>
        </div>
  </header>
  <div class="container recuadrosContent">
    <div class="row">
      <div class="col-6">
          <div class="recuadro totalGeneral">
            <header><span>TOTAL GENERAL</span></header>
            <ul>
              <li>
                <img src="img/excelente.png" alt="Excelente">
                <p><?php echo $Math.round($totalResult['t_v1']) ?>%</p>
              </li><li>
                <img src="img/bueno.png" alt="Bueno">
                <p><?php echo $Math.round($totalResult['t_v2']) ?>%</p>
              </li><li>
                <img src="img/regular.png" alt="Regular">
                <p><?php echo $Math.round($totalResult['t_v3']) ?>%</p>
              </li><li>
                <img src="img/mal.png" alt="Malo">
                <p><?php echo $Math.round($totalResult['t_v4']) ?>%</p>
              </li>
            </ul>
            <p>Total de votos: <?php echo $totalResult['Total'] ?></p>
            <p>NPS: <?php echo $npsGeneral ?></p>
          </div>
      </div>
        <div class="col-6">
            <div class="recuadro porTurnos">
              <div class="no-print" style="position:absolute; top: -50px; right: 0">
                <label for="margen">Margen</label>
                <select id="margen" style="display:inline-block">
                  <option>5</option>
                  <option>10</option>
                  <option>15</option>
                  <option>20</option>
                  <option>25</option>
                  <option selected>30</option>
                  <option>35</option>
                  <option>40</option>
                  <option>45</option>
                  <option>50</option>
                  <option>55</option>
                </select>
                <select id="recuadro2" style="display:inline-block">
                  <option value="info1">Por Turno Gráfico</option>
                  <option value="info2">Por Turno Tabla</option>
                  <option value="info3">Por Operario</option>
                </select>
              </div>
              <div id="info1" class="infoMes">
                <header><span>RESULTADOS POR TURNO</span></header>
                <div style="width: 430px; height: 100%">
                  <div id="chartdiv" style="width: 100%; height: 335px;"></div>
                </div>
              </div>
              <div id="info2" class="infoMes" style="display:none">
                <header><span>RESULTADOS POR TURNO</span></header>
                  <table class="tablaPorTurnos" border="2px" style=" border: #949799 2px solid;">
                    <thead>
                      <tr style="background: #949799; color: #FFF; ">
                        <td style="width: 18%;"><b>DIAS</b></td>
                        <td style="width: 18%;"><b>TURNO</b></td>
                        <td style="width: 8%;"><b>Excelente</b></td>
                        <td style="width: 8%;"><b>Bueno</b></td>
                        <td style="width: 8%;"><b>Regular</b></td>
                        <td style="width: 8%;"><b>Malo</b></td>
                        <td style="width: 8%;">TOTAL</td>
                        <td style="width: 8%;"><b>NPS</b></td>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $colDias = TRUE;
                       foreach($totalesTurnos as $tt => $s):
                         $restaS = $s['lunVie']['t_v1'] - $s['lunVie']['t_v3'] - $s['lunVie']['t_v4'];
                         $npsS = ($restaS > 0) ? $Math.round(($restaS / $s['lunVie']['total'])*100) : 0;
                         ?>
                        <tr>
                           <?php if($colDias):  $colDias = FALSE ?>
                            <td rowspan="<?php echo count($turnosUnicos) ?>">LUNES A VIERNES</td>
                          <?php endif;?>
                            <td><?php echo $tt ?></td>
                            <td><?php echo $s['lunVie']['t_v1'] ?></td>
                            <td><?php echo $s['lunVie']['t_v2'] ?></td>
                            <td><?php echo $s['lunVie']['t_v3'] ?></td>
                            <td><?php echo $s['lunVie']['t_v4'] ?></td>
                            <td><?php echo $s['lunVie']['total'] ?></td>
                            <td class="pc_<?php echo $npsS ?>"><?php echo $npsS ?>%</td>
                        </tr>
                      <?php endforeach;
                      $colDias = TRUE;
                     foreach($totalesTurnos as $tt => $s):
                       $restaS = $s['sabDom']['t_v1'] - $s['sabDom']['t_v3'] - $s['sabDom']['t_v4'];
                       $npsS = ($restaS > 0) ? $Math.round(($restaS / $s['sabDom']['total'])*100) : 0;
                       ?>
                      <tr>
                         <?php if($colDias):  $colDias = FALSE ?>
                          <td rowspan="<?php echo count($turnosUnicos) ?>">SÁBADO Y DOMINGO</td>
                        <?php endif;?>
                          <td><?php echo $tt ?></td>
                          <td><?php echo $s['sabDom']['t_v1'] ?></td>
                          <td><?php echo $s['sabDom']['t_v2'] ?></td>
                          <td><?php echo $s['sabDom']['t_v3'] ?></td>
                          <td><?php echo $s['sabDom']['t_v4'] ?></td>
                          <td><?php echo $s['sabDom']['total'] ?></td>
                          <td class="pc_<?php echo $npsS ?>"><?php echo $npsS ?>%</td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
              </div>
              <div id="info3" class="infoMes" style="display:none">
                <header><span>RESULTADOS POR OPERARIOS</span></header>
                <table class="tablaPorOperarios" border="2px" style=" border: #949799 2px solid;">
                  <thead>
                    <tr style="background: #949799; color: #FFF; ">
                      <td style="width: 24%;"><b>OPERARIO</b></td>
                      <td style="width: 10%;"><b>Excelente</b></td>
                      <td style="width: 10%;"><b>Bueno</b></td>
                      <td style="width: 10%;"><b>Regular</b></td>
                      <td style="width: 10%;"><b>Malo</b></td>
                      <td style="width: 10%;">TOTAL</td>
                      <td style="width: 10%;"><b>NPS</b></td>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($dataOp as $do):
                       $restaS = intval($do['Excelente']) - intval($do['Regular']) - intval($do['Malo']);
                       $npsS = ($restaS > 0) ? $Math.round(($restaS / intval($do['Total']))*100) : 0;
                       ?>
                      <tr>
                          <td><?php echo $do['Operario'] ?></td>
                          <td><?php echo $do['Excelente'] ?></td>
                          <td><?php echo $do['Bueno'] ?></td>
                          <td><?php echo $do['Regular'] ?></td>
                          <td><?php echo $do['Malo'] ?></td>
                          <td><?php echo $do['Total'] ?></td>
                          <td class="pc_<?php echo $npsS ?>"><?php echo $npsS; ?>%</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
        </div>
    </div>
    <div class="row">
      <div class="col-6">
          <div class="recuadro recuadroSmall porDiaHora">
            <header><span>DISTRIBUCIÓN POR DÍA Y HORA</span></header>
            <table class="full">
              <thead>
                  <tr>
                    <td></td>
                    <?php foreach($horasArray as $h => $hora): ?>
                      <td><?php echo $h ?></td>
                    <?php endforeach; ?>
                    <?php foreach($horasAux as $h => $hora): ?>
                      <td><?php echo $h ?></td>
                    <?php endforeach; ?>
                  </tr>
              </thead>
              <tbody>
                <?php foreach($diarioTotalData as $d => $hora): ?>
                  <tr>
                    <td><?php echo $dias[$d] ?></td>
                    <?php foreach($horasArray as $h => $hs):
                        $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
                      ?>
                      <td class="pc_<?php echo $npsD ?>"></td>
                    <?php endforeach; ?>
                    <?php foreach($horasAux as $h => $hs):
                      $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
                      ?>
                      <td class="pc_<?php echo $npsD ?>"></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
      </div>
        <div class="col-6">
            <div class="recuadro recuadroSmall conclusiones <?php echo count($mejorTurno) == 2 ? 'lix3' : '' ?>">
              <header><span>CONCLUSIONES</span></header>
              <ul>
                <li class="conclusionesOpt mejor" id="infoC1">
                  <h1>MEJOR TURNO</h1>
                  <p><i class="fas fa-angle-down fa-2x"></i></p>
                  <p>Turno <?php echo $mejor_dia['turno'] ?><br><?php echo $mejor_dia['dia'] ?><p>
                </li><li class="conclusionesOpt" id="infoC2" style="display: none">
                  <h1>Día de la semana</h1>
                  <p><i class="fas fa-angle-down fa-2x"></i></p>
                  <p class="mejorHora">MEJOR DÍA<br><?php echo $mejorPeorDiaMes['mejor']['dia'] ?><p>
                  <p class="peorHora">PEOR DÍA<br><?php echo $mejorPeorDiaMes['peor']['dia'] ?><p>
                </li><?php
                  foreach($mejorTurno as $tur => $td):
                ?><li>
                  <h1>Turno <?php echo $tur ?></h1>
                  <p><i class="fas fa-angle-down fa-2x"></i></p>
                  <p class="mejorHora">MEJOR HORA<br><?php echo $td['mejor']['hora'] ?> HS<p>
                  <p class="peorHora">PEOR HORA<br><?php echo $td['peor']['hora'] ?> HS<p>
                </li><?php endforeach; ?>
                </ul>
                <p class="footer">El NPS del período fue <?php echo $npsGeneral ?>.<br>Promedio del Rubro de Estaciones de Servicio fue de <input class="promedioTotal" type="text" />.</p>
                <div class="no-print" style="position:absolute; bottom: -30px; right: 0">
                  <select id="recuadro4">
                    <option value="infoC1">Mejor Turno</option>
                    <option value="infoC2">Dia de la semana</option>
                  </select>
                </div>
            </div>
        </div>
    </div>
  </div>
  <footer><div class="container"><p class="text-right"><br><img src="../css/images/Portada-emojis.png" style="max-width: 100px;" alt="smileWeb" /></p></div></footer>
  <div style="width: 80%; margin: 30px auto;" class="no-print">
    <table style="width: 100%">
      <thead>
          <tr>
            <td style="background: #CCC; text-align: center;">Día / Hora</td>
            <?php foreach($horasArray as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <td style="background: #666; color: #FFF; text-align: center; font-weight: bold;">Totales</td>
          </tr>
      </thead>
      <tbody>
        <?php
        foreach($diarioTotalData as $d => $hora): ?>
          <tr style="text-align: center;">
            <td><?php echo $dias[$d] ?></td>
            <?php
              $totalGralDia = 0;
             foreach($horasArray as $h => $hs):

                 $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
               ?>
              <td class="pc_<?php echo $npsD ?>"><?php echo $hora[$h]['total']; $totalGralDia +=  $hora[$h]['total'];

              if($hora[$h]['total'] > $auxVars['horaMasVotos']['cant']){

                $auxVars['horaMasVotos']['cant'] = $hora[$h]['total'];
                $auxVars['horaMasVotos']['hora'] = $h;

              }

              ?></td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs):
              $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
              ?>
              <td class="pc_<?php echo $npsD ?>"><?php  echo $hora[$h]['total']; $totalGralDia +=  $hora[$h]['total'];
              if($hora[$h]['total'] > $auxVars['horaMasVotos']['cant']){

                $auxVars['horaMasVotos']['cant'] = $hora[$h]['total'];
                $auxVars['horaMasVotos']['hora'] = $h;

              }
              ?></td>
            <?php endforeach; ?>
            <td style="background: #666; color: #FFF; text-align: center; font-weight: bold;"><?php echo $totalGralDia;

              if($totalGralDia > $auxVars['diaMasVotos']['cant']){

                $auxVars['diaMasVotos']['cant'] = $totalGralDia;
                $auxVars['diaMasVotos']['dia'] = $dias[$d];

              }


              if($auxVars['diaMenosVotos']['cant'] == '' || $totalGralDia < $auxVars['diaMenosVotos']['cant']){

                $auxVars['diaMenosVotos']['cant'] = $totalGralDia;
                $auxVars['diaMenosVotos']['dia'] = $dias[$d];

              }

             ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfooter>
        <tr style="background: #666; color: #FFF; text-align: center; font-weight: bold;" >
          <td>Totales</td>
          <?php foreach($horasArray as $h => $hs): ?>
              <td><?php echo $totalesHs[$h]['total'] ?></td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs): ?>
              <td><?php echo $totalesHs[$h]['total'] ?></td>
            <?php endforeach; ?>
            <td><?php echo $totalResult['Total'] ?></td>
        </tr>
      </tfooter>
    </table>
    <br><br><br>
    <table style="width: 100%">
      <thead>
          <tr>
            <td style="background: #CCC; text-align: center;">Día / Hora</td>
            <?php foreach($horasArray as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <td style="background: #666; color: #FFF; text-align: center; font-weight: bold;">Promedios Días</td>
            <td> - </tb>
            <?php foreach($turnosUnicos as $t):
                $totTur[$t]['nps'] = 0;
                $toTur[$t]['dias'] = 0;
              ?>
              <td><?php echo $t ?></td>
            <?php endforeach; ?>
          </tr>
      </thead>
      <tbody>
        <?php
        $divisor = array();
        foreach($diarioTotalData as $d => $hora):
          $sum = 0;
          $div = 0;
           ?>
          <tr style="text-align: center;">
            <td><?php echo $dias[$d] ?></td>
            <?php
              $totalGralDia = 0;

             foreach($horasArray as $h => $hs):
               if($hora[$hs]['divisor'] > 0){
                  $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
                  $sum += $npsD;
                  $div++;
                 } else{
                     $npsD = 0;
                }
                ?>
             <?php if($hora[$h]['total'] > 0){

                 if(!empty($divisor['dia'][$d])){
                   $divisor['dia'][$d]++;
                 }else{
                   $divisor['dia'][$d] = 1;
                 }

                if(!empty($divisor[$h])){
                  $divisor[$h]++;
                }else{
                  $divisor[$h] = 1;
                }


             }  ?>
              <td class="pc_<?php echo $hora[$hs]['divisor'] > 0 ? $npsD : '' ?>"><?php echo $npsD  ?>%</td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs):
              if($hora[$hs]['divisor'] > 0){
                 $npsD = $Math.round($hora[$hs]['nps'] / $hora[$hs]['divisor']);
                 $sum += $npsD;
                 $div++;
              } else{
                  $npsD = 0;
             }
               ?>
              <?php if($hora[$h]['total']>0){

                  if(!empty($divisor['dia'][$d])){
                    $divisor['dia'][$d]++;
                  }else{
                    $divisor['dia'][$d] = 1;
                  }
                 if(!empty($divisor[$h])){
                   $divisor[$h]++;
                 }else{
                   $divisor[$h] = 1;
                 }


              }  ?>
              <td class="pc_<?php echo $hora[$hs]['divisor'] > 0 ? $npsD : '' ?>"><?php echo $npsD?>%</td>
            <?php endforeach; ?>
            <td style="font-weight: bold;"  class="pc_<?php echo  $div > 0 ? $Math.round($sum / $div) : 0 ?>"><?php echo  $div > 0 ? $Math.round($sum / $div) : 0 ?>%</td>
            <td>&nbsp;</tb>
            <?php foreach($turnosUnicos as $t):
              $pt = !empty($promedioDiaTurno[$d][$t]['nps']) ? $Math.round($promedioDiaTurno[$d][$t]['nps'] / $promedioDiaTurno[$d][$t]['cant'] ) : 0;
              $totTur[$t]['nps'] += $pt;
              $totTur[$t]['dias'] += ($pt > 0 ? 1 : 0);
              ?>
              <td class="pc_<?php echo $pt ?>"><?php echo $pt ?>%</td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfooter>
        <tr style="background: #666; text-align: center; font-weight: bold;" >
          <td>Promedios Hs</td>
          <?php foreach($horasArray as $h => $hs): ?>
              <td class="pc_<?php echo !empty($promedioHora[$h]['divisor']) ? $Math.round($promedioHora[$h]['nps'] / $promedioHora[$h]['divisor']) : 0 ?>"><?php echo !empty($promedioHora[$h]['divisor']) ? $Math.round($promedioHora[$h]['nps'] / $promedioHora[$h]['divisor']) : 0 ?>%</td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs): ?>
              <td class="pc_<?php echo !empty($promedioHora[$h]['divisor']) ? $Math.round($promedioHora[$h]['nps'] / $promedioHora[$h]['divisor']) : 0 ?>"><?php echo !empty($promedioHora[$h]['divisor']) ? $Math.round($promedioHora[$h]['nps'] / $promedioHora[$h]['divisor']) : 0 ?>%</td>
            <?php endforeach; ?>
            <td></td>
            <td style="background: #FFF;">&nbsp;</tb>
            <?php foreach($turnosUnicos as $t):
                $pt = $Math.round($totTur[$t]['nps'] / $totTur[$t]['dias'] );
              ?>
              <td class="pc_<?php echo $pt ?>"><?php echo $pt ?>%</td>
            <?php endforeach; ?>
        </tr>
      </tfooter>
    </table>
    <br><br><br>
    <table style="width: 100%">
      <thead>
          <tr>
            <td style="background: #FFF; text-align: center;"></td>
            <?php foreach($horasArray as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hora): ?>
              <td style="background: #CCC; text-align: center;"><?php echo $h ?></td>
            <?php endforeach; ?>
            <td style="background: #666; color: #FFF; text-align: center; font-weight: bold;">Promedios Días</td>
          </tr>
      </thead>
      <tbody>
          <tr style="text-align: center;">
            <td>Lunes a Viernes</td>
            <?php $totLunVie = 0 ?>
            <?php foreach($horasArray as $h => $hs):
                $pc = $Math.round($promedioHora[$h]['lunVie'] / $promedioHora[$h]['lunVieDiv']);
              ?>
              <td class="pc_<?php echo $pc ?>"><?php echo $pc; $totLunVie += ($promedioHora[$h]['lunVie'] /  $promedioHora[$h]['lunVieDiv']) ?>%</td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs):
              $pc = $Math.round($promedioHora[$h]['lunVie'] / $promedioHora[$h]['lunVieDiv']);
              ?>
              <td class="pc_<?php echo $pc ?>"><?php echo $pc; $totLunVie += ($promedioHora[$h]['lunVie'] / $promedioHora[$h]['lunVieDiv']) ?>%</td>
            <?php endforeach; ?>
          <td style="font-weight: bold;" class="pc_<?php echo $Math.round($totLunVie / $totalHoras) ?>"><?php echo $Math.round($totLunVie / $totalHoras); ?>%</td>
          </tr>
          <tr style="text-align: center;">
            <td>Sábado y Domingo</td>
            <?php $totSabDom = 0 ?>
            <?php foreach($horasArray as $h => $hs):
              $pc = $Math.round($promedioHora[$h]['sabDom'] / $promedioHora[$h]['sabDomDiv']);
              ?>
              <td class="pc_<?php echo $pc ?>"><?php echo $pc; $totSabDom += ($promedioHora[$h]['sabDom'] / $promedioHora[$h]['sabDomDiv']) ?>%</td>
            <?php endforeach; ?>
            <?php foreach($horasAux as $h => $hs):
              $pc = $Math.round($promedioHora[$h]['sabDom'] / $promedioHora[$h]['sabDomDiv']);
              ?>
              <td class="pc_<?php echo $pc ?>"><?php echo $pc; $totSabDom += ($promedioHora[$h]['sabDom'] / $promedioHora[$h]['sabDomDiv']) ?>%</td>
            <?php endforeach; ?>
            <td style="font-weight: bold;" class="pc_<?php echo $Math.round($totSabDom / $totalHoras) ?>"><?php echo $Math.round($totSabDom / $totalHoras); ?>%</td>
          </tr>
      </tbody>
    </table>
  </div><div class="no-print"><?php foreach($auxVars as $a => $b):

    echo '<p><b>'.$a.'</b></p><p>';
    if(is_array($b)): var_dump($b); else: echo $b; endif;
    echo '</p></br>';

  endforeach; ?></div>
</body>
<script>



AmCharts.addInitHandler(function(chart) {

  //method to handle removing/adding columns when the marker is toggled
  function handleCustomMarkerToggle(legendEvent) {
      var dataProvider = legendEvent.chart.dataProvider;
      var itemIndex; //store the location of the removed item

      //Set a custom flag so that the dataUpdated event doesn't fire infinitely, in case you have
      //a dataUpdated event of your own
      legendEvent.chart.toggleLegend = true;
      // The following toggles the markers on and off.
      // The only way to "hide" a column and reserved space on the axis is to remove it
      // completely from the dataProvider. You'll want to use the hidden flag as a means
      // to store/retrieve the object as needed and then sort it back to its original location
      // on the chart using the dataIdx property in the init handler
      if (undefined !== legendEvent.dataItem.hidden && legendEvent.dataItem.hidden) {
        legendEvent.dataItem.hidden = false;
        dataProvider.push(legendEvent.dataItem.storedObj);
        legendEvent.dataItem.storedObj = undefined;
        //re-sort the array by dataIdx so it comes back in the right order.
        dataProvider.sort(function(lhs, rhs) {
          return lhs.dataIdx - rhs.dataIdx;
        });
      } else {
        // toggle the marker off
        legendEvent.dataItem.hidden = true;
        //get the index of the data item from the data provider, using the
        //dataIdx property.
        for (var i = 0; i < dataProvider.length; ++i) {
          if (dataProvider[i].dataIdx === legendEvent.dataItem.dataIdx) {
            itemIndex = i;
            break;
          }
        }
        //store the object into the dataItem
        legendEvent.dataItem.storedObj = dataProvider[itemIndex];
        //remove it
        dataProvider.splice(itemIndex, 1);
      }
      legendEvent.chart.validateData(); //redraw the chart
  }

  //check if legend is enabled and custom generateFromData property
  //is set before running
  if (!chart.legend || !chart.legend.enabled || !chart.legend.generateFromData) {
    return;
  }

  var categoryField = chart.categoryField;
  var colorField = chart.graphs[0].lineColorField || chart.graphs[0].fillColorsField || chart.graphs[0].colorField;
  var legendData =  chart.dataProvider.map(function(data, idx) {
    var markerData = {
      "title": data[categoryField] + ": " + data[chart.graphs[0].valueField],
      "color": data[colorField],
      "dataIdx": idx //store a copy of the index of where this appears in the dataProvider array for ease of removal/re-insertion
    };
    if (!markerData.color) {
      markerData.color = chart.graphs[0].lineColor;
    }
    data.dataIdx = idx; //also store it in the dataProvider object itself
    return markerData;
  });

  chart.legend.data = legendData;




  //make the markers toggleable
  chart.legend.switchable = true;
  chart.legend.addListener("clickMarker", handleCustomMarkerToggle);

}, ["serial"]);

var chart = AmCharts.makeChart("chartdiv", {

    "type": "serial",
    "theme": "light",
    "dataProvider": <?php echo json_encode( $turnosData ) ?>,
    "valueAxes": [{
    "id": "v1",
    "unit": "%",
    "position": "right",
    "title": "GDP growth rate",
  }, {
    "id": "v2",
    "stackType": "100%",
    "unit": "$",
    "unitPosition": "left",
    "position": "right",
    "title": "Votos"
  }],
    "startDuration": 1,
    "graphs": [{
        "balloonText": "[[title]], [[category]]<br><span style='font-size:14px;'><b>[[value]]</b> ([[percents]]%)</span>",
        "fillAlphas": 0.9,
        "fontSize": 11,
        "labelText": "[[value]]",
        "lineAlpha": 0,
        "title": "Malo",
        "type": "column",
        "valueField": "t_v4",
        "lineColor": "none",
        "fillColors": "#C22821",
        "valueAxis": "v2",

    }, {
        "balloonText": "[[title]], [[category]]<br><span style='font-size:14px;'><b>[[value]]</b> ([[percents]]%)</span>",
        "fillAlphas": 0.9,
        "fontSize": 11,
        "labelText": "[[value]]",
        "lineAlpha": 0,
        "title": "Regular",
        "type": "column",
        "valueField": "t_v3",
        "lineColor": "none",
        "fillColors": "#DB8916",
        "valueAxis": "v2",

    }, {
        "balloonText": "[[title]], [[category]]<br><span style='font-size:14px;'><b>[[value]]</b> ([[percents]]%)</span>",
        "fillAlphas": 0.9,
        "fontSize": 11,
        "labelText": "[[value]]",
        "lineAlpha": 0,
        "title": "Bueno",
        "type": "column",
        "valueField": "t_v2",
        "lineColor": "none",
        "fillColors": "#E9C563",
        "valueAxis": "v2"
    }, {
      "balloonText": "[[title]], [[category]]<br><span style='font-size:14px;'><b>[[value]]</b> ([[percents]]%)</span>",
        "fillAlphas": 0.9,
        "fontSize": 11,
        "labelText": "[[value]]",
        "lineAlpha": 0,
        "title": "Excelente",
        "type": "column",
        "valueField": "t_v1",
        "lineColor": "none",
        "fillColors": "#4FC3B3",
        "valueAxis": "v2"
    }, {
        "lineAlpha": 0.9,
        "title": "% Excelencia",
        "fontSize": 9,
        "type": "smoothedLine",
        "labelText": "[[value]]%",
        "lineThickness": 3,
        "bullet": "round",
        "bulletBorderColor": "#0101DF",
        "bulletBorderThickness": 2,
        "bulletBorderAlpha": 1,
        "valueField": "Excelencia",
        "valueAxis": "v1",
        "color": "#0101DF",
        "fillColors": "#0101DF"
  }],
    "marginTop": 20,
    "marginRight": 0,
    "marginLeft": 0,
    "marginBottom": 140,
    "autoMargins": false,
    "categoryField": "fecha_turno",
    "categoryAxis": {
        "gridPosition": "start",
        "axisAlpha": 0,
        "gridAlpha": 0,
        "labelRotation": 90,
        "minHorizontalGap": 0,

    },

    "balloon":{

    },

    "export": {
      "enabled": false
     }

});

$(document).ready(function(){
  $(document).on('change','#recuadro2', function(){
    var info = $(this).val();
    $(".infoMes").hide();
    $("#"+info).delay(300).show();

  });
  $(document).on('change','#recuadro4', function(){
    var info = $(this).val();
    $(".conclusionesOpt").hide();
    $("#"+info).delay(300).show();

  });

  $(document).on('change','#margen', function(){
    var margen = $(this).val()+'px';
    $(".infoMes table").css('margin-top',margen);

  });

});
</script>
</html>
<?php
endif;
 ?>
