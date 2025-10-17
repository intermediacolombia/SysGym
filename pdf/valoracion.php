<?php
/** view_valoracion_print.php
 *  Vista de la valoración (solo lectura) lista para imprimir / PDF.
 *  GET id = id de la valoración
 * ------------------------------------------------------------------ */

require_once __DIR__ . '/../inc/config.php';

/* -------- conexión y datos -------- */
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,$dbpass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) { die("Error BD: ".$e->getMessage()); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('ID de valoración inválido');

$q = $pdo->prepare("SELECT * FROM valoraciones WHERE id=:id AND borrado=0");
$q->execute([':id'=>$id]);
$val = $q->fetch(PDO::FETCH_ASSOC) ?: die('Valoración no encontrada');

$q = $pdo->prepare("SELECT nombres,apellidos,genero,fecha_nacimiento
                    FROM clientes WHERE id=:cid");
$q->execute([':cid'=>$val['cliente_id']]);
$cli = $q->fetch(PDO::FETCH_ASSOC) ?: ['nombres'=>'','apellidos'=>'','genero'=>'masculino'];

$edad='';
if(!empty($cli['fecha_nacimiento'])){
    $edad=(new DateTime($cli['fecha_nacimiento']))->diff(new DateTime())->y;
}
$gender = strtolower($cli['genero'])==='femenino' ? 'femenino':'masculino';

/* -------- clasificadores -------- */
function clas_imc($v){ if($v==='')return null; return $v<18.5?'bajo':($v<25?'perfecto':($v<30?'medio':'alto')); }


//function clas_peso($w,$h){ return ($w&&$h)?clas_imc($w/($h*$h)):null; }

function clas_peso($peso,$estatura){
    // Asegura números flotantes y evita división por 0
    $peso     = (float)str_replace(',','.',$peso);
    $estatura = (float)str_replace(',','.',$estatura);

    if($peso<=0 || $estatura<=0) return null;          // evita error
    return clas_imc( $peso / ($estatura * $estatura) );
}


function clas_grasa($v,$g){
    if($v==='')return null;
    return $g==='femenino'
        ? ($v<16?'bajo':($v<24?'perfecto':($v<31?'medio':'alto')))
        : ($v<8?'bajo':($v<20?'perfecto':($v<25?'medio':'alto')));
}
function clas_musculo($v,$g){
    if($v==='')return null;
    return $g==='femenino'
        ? ($v<23?'bajo':($v<30?'perfecto':($v<36?'medio':'alto')))
        : ($v<33?'bajo':($v<39?'perfecto':($v<46?'medio':'alto')));
}
function clas_visceral($v){ if($v==='')return null; return $v<=1?'bajo':($v<=9?'perfecto':($v<=15?'medio':'alto')); }
function clas_edad_corporal($v,$r){
    if($v==='')return null; $d=$v-$r;
    return $d<-3?'bajo':($d<=3?'perfecto':($d<=10?'medio':'alto'));
}

/* -------- filas métricas -------- */
$metricas=[
 ['Fecha',$val['fecha'],null],
['Edad Real',$edad,null],
['Estatura (m)',number_format($val['estatura'],2),null],
 ['Peso (Kg)',number_format($val['peso'],2),clas_peso($val['peso'],$val['estatura'])], 
 ['IMC',number_format($val['imc'],2),clas_imc($val['imc'])],
 ['% Grasa',number_format($val['porcentaje_grasa_corporal'],2),clas_grasa($val['porcentaje_grasa_corporal'],$gender)],
 ['% Músculo',number_format($val['porcentaje_musculo_esqueletico'],2),clas_musculo($val['porcentaje_musculo_esqueletico'],$gender)],
 ['Metabolismo Basal (kcal)',$val['metabolismo_basal'],null],
 ['Edad Corporal',$val['edad_corporal'],clas_edad_corporal($val['edad_corporal'],$edad)],
 ['Grasa Visceral',$val['nivel_grasa_visceral'],clas_visceral($val['nivel_grasa_visceral'])]
 
];

/* -------- filas medidas corporales -------- */
$labels=[
 'hombros'=>'Hombros','pecho'=>'Pecho','abdomen'=>'Abdomen','cintura'=>'Cintura','cadera'=>'Cadera',
 'izq_brazo'=>'Brazo Izq.','der_brazo'=>'Brazo Der.','izq_antebrazo'=>'Antebrazo Izq.','der_antebrazo'=>'Antebrazo Der.',
 'izq_muneca'=>'Muñeca Izq.','der_muneca'=>'Muñeca Der.',
 'izq_muslo_medio'=>'Muslo Medio Izq.','der_muslo_medio'=>'Muslo Medio Der.',
 'izq_pantorrilla'=>'Pantorrilla Izq.','der_pantorrilla'=>'Pantorrilla Der.'
];
$medidas=[];
foreach($labels as $c=>$et){
    if($val[$c]!=='') $medidas[]=[$et,number_format($val[$c],2)];
}

/* -------- iconos FontAwesome -------- */
$ico = [
  'bajo'      => '<i class="fa-solid fa-arrow-down"></i>',
  'perfecto'  => '<i class="fa-solid fa-arrow-right"></i>',
  'medio'     => '<i class="fa-solid fa-arrow-up"></i>',
  // flecha ascendente “espejada” (punta sigue arriba)
  'alto'      => '<i class="fa-solid fa-arrow-up fa-flip-horizontal"></i>'
];

?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Valoración – <?=htmlspecialchars("{$cli['nombres']} {$cli['apellidos']}")?></title>

<!-- Font Awesome -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-sV8RQrZFv61FtHVzNneV6S+K8T6fj5shQVz0cGBWNqnEeFTArwI4Z8nU9tnb2A6k1BvF6rn4YBlfb81NJj5KDw=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
body{font-family:"DejaVu Sans", Arial, Helvetica, sans-serif; 
	margin:20px;}
h1{text-align:center;
	margin-bottom:40px;}

table{
	width:100%;
	border-collapse:collapse;
	font-size:.9rem;
	margin-top:32px
	}
th{background:#E21F0C;
	color:#fff;
	padding:6px 4px
	}
td{
	border:1px solid #ccc;
	padding:6px 4px
	}
th:first-child,td:first-child{
	width:55%
	}
th:last-child,td:last-child{
	text-align:center;
	width:18%
	}

.bajo      {background:#e1f5fe;
	color:#036f8b;
	}
.perfecto  {background:#e6f4ea;
	color:#1a7f30;
	}
.medio     {
	background:#fff9e6;
	color:#b38900;
	}
.alto      {
	background:#fdecea;
	color:#a71d2a;
	}

td .fa{
	font-size:1rem;
	}

	.arrow { 
		font-weight:bold; 
		font-size:0.9rem; 
		display:inline-block;
	}
	.logo {
      text-align: center;
      margin-bottom: 20px;
    }
	
</style>
</head><body>
  <!-- Mostrar el logo usando el data URI generado -->
  <div class="logo">
    <img src="<?php echo $url; ?>/pdf/images/logo-activ.png" width="150" alt="Logo ACTIVGYM">
  </div>


<!-- Párrafo de introducción con las flechas -->
<p class="intro">
  Esta valoración ofrece una instantánea del estado físico de
  <strong><?= htmlspecialchars("{$cli['nombres']} {$cli['apellidos']}") ?></strong>
  en la fecha indicada.
  Los indicadores (IMC, % grasa, % músculo, edad y grasa visceral) se interpretan así:
  <span class="arrow bajo">&#x2193;</span> bajo ·
  <span class="arrow perfecto">&#x2192;</span> óptimo ·
  <span class="arrow medio">&#x2191;</span> moderado ·
  <span class="arrow alto">&#x2191;</span> elevado.
  Utilízalos para ajustar entrenamiento, nutrición y hábitos saludables.
</p>





<!-- ======= Medidas corporales ======= -->
<?php if($medidas): ?>
<table>
 <thead>
   <tr><th colspan="2" style="text-align:center">MEDIDAS CORPORALES&nbsp;(CM)</th></tr>
   <tr><th>Zona</th><th>Medida</th></tr>
 </thead><tbody>
 <?php foreach($medidas as [$z,$v]) echo "<tr><td>$z</td><td>$v</td></tr>"; ?>
 </tbody>
</table>
<?php endif; ?>
	
	<!-- ======= Métricas ======= -->
<table>
	<tr><th colspan="3" style="text-align:center">METRICAS</th></tr>
 <thead><tr><th>Dato</th><th>Medida</th><th></th></tr></thead><tbody>
<?php
foreach($metricas as [$d,$m,$n]){
    if($m==='') continue;
    $cls=$n?:'';
    $fle=$n? $ico[$n] : '';
    echo "<tr class=\"$cls\"><td>$d</td><td>$m</td><td>$fle</td></tr>";
}
if(!empty($val['observaciones'])){
  echo '<tr><td>Observaciones</td><td colspan="2">'
      .nl2br(htmlspecialchars($val['observaciones'])).'</td></tr>';
}
?>
 </tbody>
</table>
	

</body></html>



