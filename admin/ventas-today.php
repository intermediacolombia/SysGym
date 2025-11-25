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
	
body.dark-mode .unique {
	
	background: #192229!important;
	
	}
	
	
	
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let graficoVentas = null; // instancia global

async function cargarGraficoVentas() {
    try {
        const res = await fetch("gets_home/get_ventas_comparadas.php");
        const data = await res.json();

        const empty = document.getElementById("ventas-empty");
        const card  = document.getElementById("ventas-chart-card");

        if (!data || data.hoy === undefined) {
            empty.style.display = "block";
            card.style.display  = "none";
            return;
        }

        empty.style.display = "none";
        card.style.display  = "block";

        const ctx = document.getElementById("graficoVentas").getContext("2d");

        // Detectar modo oscuro
        const dark  = document.body.classList.contains("dark-mode");
        const colorHoy  = dark ? "rgba(0, 200, 255, 0.9)" : "rgba(0,122,255,0.9)";
        const colorAyer = dark ? "rgba(255,115,0,0.9)" : "rgba(255,159,64,0.9)";
        const fontC     = dark ? "#fff" : "#222";
        const gridC     = dark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)";

        // DESTRUIR GRÁFICO ANTERIOR
        if (graficoVentas) {
            graficoVentas.destroy();
            graficoVentas = null;
        }

        // CREAR NUEVO
        graficoVentas = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Ayer", "Hoy"],
                datasets: [{
                    data: [data.ayer, data.hoy],
                    backgroundColor: [colorAyer, colorHoy],
                    borderWidth: 0,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => "$" + ctx.raw.toLocaleString("es-CO")
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

// PRIMERA CARGA
cargarGraficoVentas();

// Modo claro/oscuro: recargar completamente
document.addEventListener("theme-changed", cargarGraficoVentas);

// ACTUALIZAR CADA 30 SEGUNDOS
setInterval(cargarGraficoVentas, 60000);
</script>

