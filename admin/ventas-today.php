<div class="card-list-empty" id="ventas-empty" style="display:none;">
        No hay información disponible.
    </div>

    <div class="chart-card" id="ventas-chart-card" style="display:none;">
        <canvas id="graficoVentas"></canvas>
    </div>



<style>
.chart-card {
    padding: 22px;
    border-radius: 18px;
    background: var(--system-bg-secondary);
    width: 100%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

body:not(.dark-mode) .chart-card {
    background: #ffffff;
}

body.dark-mode .chart-card {
    background: #212E36!important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function cargarGraficoVentas() {
    try {
        const res = await fetch("gets_home/get_ventas_comparadas.php");
        const data = await res.json();

        if (!data || data.hoy === undefined) {
            document.getElementById("ventas-empty").style.display = "block";
            return;
        }

        document.getElementById("ventas-empty").style.display = "none";
        document.getElementById("ventas-chart-card").style.display = "block";

        const ctx = document.getElementById("graficoVentas").getContext("2d");

        // Modo oscuro / claro
        const dark = document.body.classList.contains("dark-mode");

        const colorHoy   = dark ? "rgba(0, 200, 255, 0.9)" : "rgba(0, 122, 255, 0.9)";
        const colorAyer  = dark ? "rgba(255, 115, 0, 0.9)" : "rgba(255, 159, 64, 0.9)";
        const borderC    = dark ? "#fff" : "#222";
        const gridC      = dark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)";
        const fontC      = dark ? "#fff" : "#222";

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Ayer", "Hoy"],
                datasets: [{
                    data: [data.ayer, data.hoy],
                    backgroundColor: [colorAyer, colorHoy],
                    borderColor: borderC,
                    borderWidth: 1,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => 
                                "$" + ctx.raw.toLocaleString("es-CO")
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridC },
                        ticks: { color: fontC }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: fontC }
                    }
                }
            }
        });

    } catch (e) {
        console.error("Error gráfico ventas", e);
        document.getElementById("ventas-empty").style.display = "block";
    }
}

cargarGraficoVentas();

// Si tu sistema cambia de claro/oscuro en runtime:
document.addEventListener("theme-changed", cargarGraficoVentas);
</script>
