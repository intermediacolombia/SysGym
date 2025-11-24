<div class="card-list-wrapper" id="comp-wrapper">

    <div class="card-list-empty" id="comp-empty" style="display:none;">
        No se pudo obtener la comparativa.
    </div>

    <div class="card-item" id="comp-card" style="display:none;">
        <div class="card-info">
            <div class="card-title" id="comp-title"></div>
            <div class="card-sub" id="comp-sub"></div>
        </div>
        <div>
            <span class="badge-pill" id="comp-badge"></span>
        </div>
    </div>

</div>

<style>
.badge-green  { background: #4caf50 !important; color: #fff !important; }
.badge-red    { background: #f44336 !important; color: #fff !important; }
.badge-yellow { background: #ff9800 !important; color: #fff !important; }
</style>

<script>
async function cargarComparativaAsistencias() {

    try {
        const r = await fetch("gets_home/get_asistencias_comparadas.php");
        const res = await r.json();

        const card  = document.getElementById("comp-card");
        const empty = document.getElementById("comp-empty");

        // Si no hay datos válidos
        if (!res || typeof res.today === "undefined") {
            empty.style.display = "block";
            card.style.display  = "none";
            return;
        }

        empty.style.display = "none";
        card.style.display  = "flex";

        // Datos
        const title = `${res.today} asistencias hoy`;
        const sub   = `La semana pasada fueron ${res.last_week}`;

        let badgeClass = "badge-yellow";
        let badgeText  = "";

        if (res.diff > 0) {
            badgeClass = "badge-green";
            badgeText  = `+${res.percent}%`;
        } else if (res.diff < 0) {
            badgeClass = "badge-red";
            badgeText  = `${res.percent}%`;
        } else {
            badgeClass = "badge-yellow";
            badgeText  = "0%";
        }

        document.getElementById("comp-title").innerText = title;
        document.getElementById("comp-sub").innerText   = sub;

        const badge = document.getElementById("comp-badge");
        badge.className = "badge-pill " + badgeClass;
        badge.innerText = badgeText;

    } catch (error) {
        console.error("Error comparativa", error);
        document.getElementById("comp-empty").style.display = "block";
        document.getElementById("comp-card").style.display  = "none";
    }
}

// Primera carga
cargarComparativaAsistencias();

// Actualizar cada 10 segundos
setInterval(cargarComparativaAsistencias, 10000);
</script>

