

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
let graficoAsistenciasHoy = null; // instancia global

async function cargarGraficoAsistenciasHoy() {

    const res = await fetch("gets_home/get_asistencias_por_hora.php");
    const json = await res.json();

    const data = json.data || [];

    const emptyBox = document.getElementById("asist-hoy-empty");
    const chartBox = document.getElementById("asist-hoy-chart");

    if (data.length === 0) {
        emptyBox.style.display = "block";
        chartBox.style.display = "none";
        return;
    }

    emptyBox.style.display = "none";
    chartBox.style.display = "block";

    const horas = data.map(r => r.hora + ":00");
    const totales = data.map(r => r.total);

    const ctx = document.getElementById("graficoAsistenciasHoy").getContext("2d");

    // Detectar modo oscuro
    const dark = document.body.classList.contains("dark-mode");

    const lineColor = dark ? "rgba(255, 99, 132, 1)" : "rgba(255, 80, 80, 1)";
    const fillColor = dark ? "rgba(255, 99, 132, 0.20)" : "rgba(255, 80, 80, 0.20)";
    const gridColor = dark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)";
    const fontColor = dark ? "#fff" : "#222";

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, fillColor);
    gradient.addColorStop(1, "rgba(0,0,0,0)");

    // Si ya existe → solo actualiza colores y datos
    if (graficoAsistenciasHoy) {

        graficoAsistenciasHoy.data.labels = horas;
        graficoAsistenciasHoy.data.datasets[0].data = totales;

        graficoAsistenciasHoy.data.datasets[0].borderColor = lineColor;
        graficoAsistenciasHoy.data.datasets[0].backgroundColor = gradient;
        graficoAsistenciasHoy.data.datasets[0].pointBackgroundColor = lineColor;

        graficoAsistenciasHoy.options.scales.x.ticks.color = fontColor;
        graficoAsistenciasHoy.options.scales.y.ticks.color = fontColor;
        graficoAsistenciasHoy.options.scales.x.grid.color = gridColor;
        graficoAsistenciasHoy.options.scales.y.grid.color = gridColor;

        graficoAsistenciasHoy.update();
        return;
    }

    // Crear el gráfico por primera vez
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
                pointRadius: 5,
                pointBackgroundColor: lineColor,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: fontColor }
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

// Recargar SOLO colores al cambiar tema
document.addEventListener("theme-changed", () => {
    cargarGraficoAsistenciasHoy();
});

</script>

