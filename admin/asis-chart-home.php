

    <!-- Mensaje vacío -->
    <div class="card-list-empty" id="asist-hoy-empty" style="display:none;">
        <i class="fas fa-check-circle text-success" style="font-size:22px;display:block;margin-bottom:6px;"></i>
        No hay asistencias registradas hoy.
    </div>

    <!-- Contenedor del gráfico (solo se muestra si hay datos) -->
    <div class="chart-card" id="asist-hoy-chart" style="display:none;">
        <canvas id="graficoAsistenciasHoy"></canvas>
    </div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let graficoAsistenciasHoy = null;

async function cargarGraficoAsistenciasHoy() {
    const res = await fetch("gets_home/get_asistencias_por_hora.php");
    const json = await res.json();
    const data = json.data || [];
    const emptyBox = document.getElementById("asist-hoy-empty");
    const chartBox = document.getElementById("asist-hoy-chart");
    
    if (!emptyBox || !chartBox) {
        console.error("Faltan los contenedores del gráfico.");
        return;
    }
    
    if (data.length === 0) {
        emptyBox.style.display = "block";
        chartBox.style.display = "none";
        return;
    }
    
    emptyBox.style.display = "none";
    chartBox.style.display = "block";
    
    const horas = data.map(r => r.hora + ":00");
    const totales = data.map(r => r.total);
    const canvas = document.getElementById("graficoAsistenciasHoy");
    
    if (!canvas) {
        console.error("Falta canvas graficoAsistenciasHoy");
        return;
    }
    
    const ctx = canvas.getContext("2d");
    
    // Detectar modo oscuro
    const dark = document.body.classList.contains("dark-mode");
    const gridColor = dark ? "rgba(255, 255, 255, 0.25)" : "rgba(0, 0, 0, 0.08)";
    const fontColor = dark ? "#ffffff" : "#222222";
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, "rgba(255, 205, 86, 0.5)");
    gradient.addColorStop(1, "rgba(0, 0, 0, 0)");
    
    // Destruir el gráfico si ya existe
    if (graficoAsistenciasHoy) {
        graficoAsistenciasHoy.destroy();
        graficoAsistenciasHoy = null;
    }
    
    graficoAsistenciasHoy = new Chart(ctx, {
        type: "line",
        data: {
            labels: horas,
            datasets: [{
                label: "Asistencias",
                data: totales,
                borderColor: "rgba(255, 205, 86, 1)",
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: "rgba(255, 205, 86, 1)",
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { display: false } 
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { 
                        color: fontColor,
                        precision: 0
                    }
                },
                x: {
                    grid: { color: gridColor },
                    ticks: { color: fontColor }
                }
            }
        }
    });
}

// Primera carga
cargarGraficoAsistenciasHoy();

// Recargar cuando cambie el tema
document.addEventListener("theme-changed", cargarGraficoAsistenciasHoy);
</script>

