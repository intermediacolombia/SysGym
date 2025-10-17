<?php
// index.php
$center = ['Hombros','Pecho','Abdomen','Cintura','Cadera'];
$lateral = ['Brazo','Antebrazo','Muñeca','Muslo medio','Pantorrilla'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Medidas Corporales</title>
  <?php include('../inc/header.php'); ?>
  <!-- Bootstrap CSS -->
  
  <style>
    .measurements-layout {
      display: flex;
      gap: 120px;
      padding: 90px;
      justify-content: center;
      align-items: flex-start;
      flex-wrap: wrap;
    }
    .wrapper {
      position: relative;
      width: 180px; /* Dejamos 300px como antes */
      flex-shrink: 0;
    }
    .wrapper img {
      width: 100%;
      display: block;
    }
    .label {
      position: absolute;
      width: 70px;
      padding: 4px 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: #fff;
      text-align: center;
      box-shadow: 0 1px 4px rgba(0,0,0,0.1);
      font-size: .85rem;
    }
    .label input {
      width: 100%;
      border: none;
      background: transparent;
      text-align: center;
      font-size: 10px;
      padding: 0;
    }
    /* Posiciones (ajústalas finamente) */
    .hombros    { top:15%; left:50%; transform:translateX(-50%); }
    .pecho      { top:22%; left:50%; transform:translateX(-50%); }
    .abdomen    { top:34%; left:50%; transform:translateX(-50%); }
    .cintura    { top:40%; left:50%; transform:translateX(-50%); }
    .cadera     { top:45%; left:50%; transform:translateX(-50%); }
    .izq-brazo       { top:25%; left:-55px; }
    .izq-antebrazo   { top:36%; left:-55px; }
    .izq-muneca      { top:43%; left:-55px; }
    .izq-muslo       { top:62%; left:-30px; }
    .izq-pantorrilla { top:78%; left:-30px; }
    .der-brazo       { top:25%; right:-55px; }
    .der-antebrazo   { top:36%; right:-55px; }
    .der-muneca      { top:43%; right:-55px; }
    .der-muslo       { top:62%; right:-30px; }
    .der-pantorrilla { top:78%; right:-30px; }

    .details-form .field {
      margin-bottom: 1rem;
    }
    .details-form label {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
	<h1>Nueva Valoración</h1>
	</div>
	</div>
  <?php include('../inc/menu.php'); ?>

  <div class="container mt-4">
   <form method="post" action="guardar_medidas.php" class="measurements-layout">

  <!-- 1) Silueta con campos superpuestos -->
  <div class="wrapper">
    <img src="/admin/images/male2.png" alt="Silueta corporal">

    <!-- Centro -->
    <div class="label hombros">
      <input type="number" name="medida[hombros]" placeholder="Hombros">
    </div>
    <div class="label pecho">
      <input type="number" name="medida[pecho]" placeholder="Pecho">
    </div>
    <div class="label abdomen">
      <input type="number" name="medida[abdomen]" placeholder="Abdomen">
    </div>
    <div class="label cintura">
      <input type="number" name="medida[cintura]" placeholder="Cintura">
    </div>
    <div class="label cadera">
      <input type="number" name="medida[cadera]" placeholder="Cadera">
    </div>

    <!-- Lateral izquierdo -->
    <div class="label izq-brazo">
      <input type="number" name="medida[izq_brazo]" placeholder="Brazo">
    </div>
    <div class="label izq-antebrazo">
      <input type="number" name="medida[izq_antebrazo]" placeholder="Antebrazo">
    </div>
    <div class="label izq-muneca">
      <input type="number" name="medida[izq_muneca]" placeholder="Muñeca">
    </div>
    <div class="label izq-muslo">
      <input type="number" name="medida[izq_muslo_medio]" placeholder="Muslo medio">
    </div>
    <div class="label izq-pantorrilla">
      <input type="number" name="medida[izq_pantorrilla]" placeholder="Pantorrilla">
    </div>

    <!-- Lateral derecho -->
    <div class="label der-brazo">
      <input type="number" name="medida[der_brazo]" placeholder="Brazo">
    </div>
    <div class="label der-antebrazo">
      <input type="number" name="medida[der_antebrazo]" placeholder="Antebrazo">
    </div>
    <div class="label der-muneca">
      <input type="number" name="medida[der_muneca]" placeholder="Muñeca">
    </div>
    <div class="label der-muslo">
      <input type="number" name="medida[der_muslo_medio]" placeholder="Muslo medio">
    </div>
    <div class="label der-pantorrilla">
      <input type="number" name="medida[der_pantorrilla]" placeholder="Pantorrilla">
    </div>
  </div>

  <!-- 2) Formulario de datos generales a la derecha -->
  <!-- Columna derecha: detalles del formulario con Bootstrap -->
<div class="col-md-4">
  <div class="mb-3">
    <label for="peso" class="form-label">Peso (Kg):</label>
    <input id="peso" name="peso" type="number" step="any" class="form-control" placeholder="55.7">
  </div>
  <div class="mb-3">
    <label for="estatura" class="form-label">Estatura (m):</label>
    <input id="estatura" name="estatura" type="number" step="any" class="form-control" placeholder="1.75">
  </div>
  <div class="mb-3">
    <label for="fecha" class="form-label">Fecha del registro:</label>
    <input id="fecha" name="fecha" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
  </div>
  <div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones:</label>
    <textarea id="observaciones" name="observaciones" class="form-control" rows="4" placeholder="Notas…"></textarea>
  </div>
  <div class="text-center mt-4">
    <button type="submit" class="btn btn-primary">Guardar Medidas</button>
  </div>
</div>


 

</form>
  </div>

  <?php include('../inc/menu-footer.php'); ?>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




