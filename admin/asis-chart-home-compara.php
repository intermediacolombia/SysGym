<div class="card-list-wrapper" id="comp-wrapper">

    <div class="card-list-empty" id="comp-empty" style="display:none;">
        No se pudo obtener la comparativa.
    </div>

    <div class="card-item" id="comp-card" style="display:none;">
        <div class="card-info">
            <div class="card-title" id="comp-title">
                <!-- Aquí va el texto dinámico -->
            </div>
            <div class="card-sub" id="comp-sub">
                <!-- Aquí va la comparación -->
            </div>
        </div>
        <div>
            <span class="badge-pill" id="comp-badge"></span>
        </div>
    </div>

</div>

<style>
/* Badge colores modo claro/oscuro se ajustan solo */
.badge-green {
    background: #4caf50 !important;
    color: #fff !important;
}
.badge-red {
    background: #f44336 !important;
    color: #fff !important;
}
.badge-yellow {
    background: #ff9800 !important;
    color: #fff !important;
}
</style>
<script>
fetch("gets_home/get_asistencias_comparadas.php")
    .then(r => r.json())
    .then(res => {

        const card = document.getElementById("comp-card");
        const empty = document.getElementById("comp-empty");

        if (!res || res.today === undefined) {
            empty.style.display = "block";
            return;
        }

        empty.style.display = "none";
        card.style.display = "flex";

        let title = `${res.today} asistencias hoy`;
        let sub = `La semana pasada fueron ${res.last_week}`;

        let badgeClass = "badge-yellow";
        let badgeText = "";

        if (res.diff > 0) {
            badgeClass = "badge-green";
            badgeText = `+${res.percent}%`;
        } else if (res.diff < 0) {
            badgeClass = "badge-red";
            badgeText = `${res.percent}%`; 
        } else {
            badgeClass = "badge-yellow";
            badgeText = "0%";
        }

        document.getElementById("comp-title").innerText = title;
        document.getElementById("comp-sub").innerText = sub;

        const badge = document.getElementById("comp-badge");
        badge.className = "badge-pill " + badgeClass;
        badge.innerText = badgeText;

    })
    .catch(err => {
        console.error("Error comparativa", err);
        document.getElementById("comp-empty").style.display = "block";
        document.getElementById("comp-card").style.display = "none";
    });
</script>
