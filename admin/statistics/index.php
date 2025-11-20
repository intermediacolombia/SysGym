<?php 
require_once __DIR__ . '/../login/session.php'; 
?>
<?php 
$permisopage = 'Ver Estadisticas';
include('../login/restriction.php'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Estadísticas de Clientes y Planes</title>
  <?php include('../inc/header.php'); ?>
  <!-- Cargamos Chart.js desde un CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Asegúrate de incluir jQuery; si ya lo tienes en header.php, puedes omitirlo -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    .chart-container { width: 100%; margin: auto; }
  </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1 class="mb-4">Estadisticas</h1>
  </div>
</div>
  <?php include('../inc/menu.php'); ?>

 <div class="container mt-4">
    <!-- Filtro en tiempo real con switches -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <!-- Para switches usamos "form-check form-switch" -->
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterNuevos" value="nuevos" checked>
              <label class="form-check-label" for="filterNuevos">Nuevos Clientes Mensuales</label>
            </div>
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterPlanes" value="planes" checked>
              <label class="form-check-label" for="filterPlanes">Estadísticas de Planes</label>
            </div>
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterActivos" value="activos" checked>
              <label class="form-check-label" for="filterActivos">Clientes Activos vs Inactivos</label>
            </div>
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterGenero" value="genero" checked>
              <label class="form-check-label" for="filterGenero">Estadísticas de Género</label>
            </div>
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterEdad" value="edad" checked>
              <label class="form-check-label" for="filterEdad">Distribución por Edad</label>
            </div>
            <div class="form-check form-switch form-check-inline">
              <input class="form-check-input filter-checkbox" type="checkbox" id="filterCumpleanos" value="cumpleanos" checked>
              <label class="form-check-label" for="filterCumpleanos">Cumpleaños por Mes</label>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row" id="statisticsContainer">
      
      <!-- Tarjeta para Nuevos Clientes Mensuales -->
      <div class="col-md-6 mb-4 filterCard" data-filter="nuevos">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class="fa fa-line-chart"></i> Nuevos Clientes Mensuales</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Esta estadística muestra la cantidad de nuevos clientes registrados cada mes, permitiendo visualizar la tendencia de crecimiento y detectar posibles estacionalidades en la demanda.
            </p>
            <?php include('nuevos.php'); ?>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta para Estadísticas de Planes -->
      <div class="col-md-6 mb-4 filterCard" data-filter="planes">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class='fas fa-running'></i> Estadísticas de Planes</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Aquí se muestra la cantidad de usuarios inscritos en cada uno de los planes disponibles, lo que ayuda a evaluar la popularidad y rentabilidad de cada opción.
            </p>
            <?php include('planes.php'); ?>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta para Clientes Activos vs Inactivos -->
      <div class="col-md-6 mb-4 filterCard" data-filter="activos">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class='fas fa-user-check'></i> Clientes Activos vs Inactivos</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Esta estadística muestra la proporción de clientes activos frente a inactivos, utilizando el campo "estado" de la base de datos, que contiene los valores "activo" e "inactivo".
            </p>
            <?php include('activo.php'); ?>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta para Estadísticas de Género de Clientes -->
      <div class="col-md-6 mb-4 filterCard" data-filter="genero">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class='fas fa-restroom'></i> Estadísticas de Género de Clientes</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Se muestra la distribución de clientes por género (masculino, femenino y otros), información útil para analizar la segmentación del mercado y adaptar estrategias de marketing.
            </p>
            <?php include('genero.php'); ?>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta para Distribución por Edad -->
      <div class="col-md-6 mb-4 filterCard" data-filter="edad">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class='fas fa-child'></i> Distribución por Edad</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Esta estadística muestra la distribución de clientes en diferentes rangos de edad, calculada a partir de la fecha de nacimiento, lo que permite conocer mejor el perfil demográfico.
            </p>
            <?php include('edad.php'); ?>
          </div>
        </div>
      </div>
      
      <!-- Tarjeta para Cumpleaños por Mes -->
      <div class="col-md-6 mb-4 filterCard" data-filter="cumpleanos">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="card-title"><i class="fas fa-birthday-cake"></i> Cumpleaños por Mes</h5>
          </div>
          <div class="card-body">
            <p class="card-text">
              Esta estadística muestra la cantidad de clientes que cumplen años en cada mes, permitiendo identificar en qué periodos se concentra la mayoría de los cumpleaños.
            </p>
            <?php include('cumpleanos.php'); ?>
          </div>
        </div>
      </div>
      
    </div>
  </div>
  
  <?php include('../inc/menu-footer.php'); ?>

  <script>
    // Utilizamos jQuery para aplicar un efecto de deslizado al filtrar
    $('.filter-checkbox').on('change', function(){
       $('.filterCard').each(function(){
           var filter = $(this).data('filter');
           var checkbox = $('.filter-checkbox[value="'+filter+'"]');
           if(checkbox.is(':checked')){
               $(this).slideDown();
           } else {
               $(this).slideUp();
           }
       });
    });
  </script>
</body>
</html>



