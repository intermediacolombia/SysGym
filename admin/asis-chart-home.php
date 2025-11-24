

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

/* ============================================================
   CARGAR GRÁFICO DE ASISTENCIAS HOY
   ============================================================ */
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

    // --- SIN ASISTENCIAS ---
    if (data.length === 0) {
        emptyBox.style.display = "block";
        chartBox.style.display = "none";
        return;
    }

    // --- HAY DATA ---
    emptyBox.style.display = "none";
    chartBox.style.display = "block";

    const horas = data.map(r => r.hora + ":00");
    const totales = data.map(r => r.total);

    const canvas = document.getElementById("graficoAsistenciasHoy");
    if (!canvas) {
        console.error("Falta el canvas graficoAsistenciasHoy");
        return;
    }

    const ctx = canvas.getContext("2d");

    /* ============================================================
       DETECTAR TEMA (claro / oscuro)
       ============================================================ */
    const dark = document.body.classList.contains("dark-mode");

    const lineColor  = dark ? "rgba(255, 99, 132, 1)"  : "rgba(255, 80, 80, 1)";
    const fillColor  = dark ? "rgba(255, 99, 132, 0.20)" : "rgba(255, 80, 80, 0.20)";
    const gridColor  = dark ? "rgba(255,255,255,0.20)" : "rgba(0,0,0,0.06)";
    const fontColor  = dark ? "#ffffff" : "#222222";

    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, fillColor);
    gradient.addColorStop(1, "rgba(0,0,0,0)");

    /* ============================================================
       DESTRUIR EL GRÁFICO PREVIO SI EXISTE
       ============================================================ */
    if (graficoAsistenciasHoy) {
        graficoAsistenciasHoy.destroy();
        graficoAsistenciasHoy = null;
    }

    /* ============================================================
       CREAR NUEVO GRÁFICO
       ============================================================ */
    graficoAsistenciasHoy = new Chart(ctx, {
        type: "line",
        data: {
            labels: horas,
            datasets: [{
                label: "Asistencias",
                data: totales,
                borderColor: lineColor,
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: lineColor,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: { color: fontColor },
                    grid: { color: gridColor }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: fontColor },
                    grid: { color: gridColor }
                }
            }
        }
    });
}

/* ============================================================
   PRIMERA CARGA
   ============================================================ */
cargarGraficoAsistenciasHoy();

/* ============================================================
   RECARGAR AL CAMBIAR TEMA
   ============================================================ */
document.addEventListener("theme-changed", () => {
    cargarGraficoAsistenciasHoy();
});


</script>

