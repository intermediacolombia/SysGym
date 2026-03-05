<?php if (isset($_SESSION["user_permissions"])
          && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>

<style>
#att-wrap {
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
  margin-top: 24px;
  color: #3a4155;
}
.att-month-selector {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
}
.att-month-selector label {
  font-size: .75rem;
  font-weight: 600;
  color: #5a6478;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin: 0;
}
.att-month-selector select {
  border: 1.5px solid #e5e7ef;
  border-radius: 10px;
  padding: 6px 12px;
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
  font-size: .85rem;
  color: #3a4155;
  outline: none;
  cursor: pointer;
  transition: border-color .2s;
  background: #fff;
}
.att-month-selector select:focus {
  border-color: var(--system-color-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--system-color-primary) 15%, transparent);
}
.att-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.att-card {
  background: #fff;
  border: 1.5px solid #e5e7ef;
  border-radius: 14px;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: transform .18s, box-shadow .18s, border-color .18s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.att-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
  border-color: var(--system-color-primary);
}
.att-card-label {
  font-size: .7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: #9aa3b5;
}
.att-card-value {
  font-size: 1.7rem;
  font-weight: 700;
  line-height: 1;
  color: #1a2235;
}
.att-card-value.c-green  { color: #16a34a; }
.att-card-value.c-red    { color: #dc2626; }
.att-card-value.c-purple { color: var(--system-color-primary); }
.att-card-value.c-blue   { color: #2563eb; }
.att-progress-wrap {
  background: #fff;
  border: 1.5px solid #e5e7ef;
  border-radius: 14px;
  padding: 18px 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.att-progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.att-progress-title {
  font-size: .75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #9aa3b5;
}
.att-badge-stat {
  font-size: .75rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: .05em;
}
.ab-excelente { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.ab-bueno     { background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
.ab-regular   { background: #fef9c3; color: #ca8a04; border: 1px solid #fde68a; }
.ab-malo      { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
.att-bar-track {
  height: 8px;
  background: #f0f2f7;
  border-radius: 99px;
  overflow: hidden;
}
.att-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .7s cubic-bezier(.4,0,.2,1);
  background: var(--system-color-primary);
}
.att-bar-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: .68rem;
  color: #c0c7d4;
}
.att-week-grid {
  background: #fff;
  border: 1.5px solid #e5e7ef;
  border-radius: 14px;
  padding: 18px 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.att-week-title {
  font-size: .75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #9aa3b5;
  margin-bottom: 14px;
}
.att-days-row {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 8px;
}
.att-day-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}
.att-day-label {
  font-size: .65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #9aa3b5;
}
.att-day-dot {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .82rem;
  font-weight: 700;
  border: 1.5px solid #e5e7ef;
  transition: transform .15s;
  cursor: default;
}
.att-day-dot:hover { transform: scale(1.08); }
.att-day-dot.lv0 { background: #f8faff; color: #c0c7d4; }
.att-day-dot.lv1 { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
.att-day-dot.lv2 { background: #fef9c3; color: #ca8a04; border-color: #fde68a; }
.att-day-dot.lv3 { background: #dcfce7; color: #16a34a; border-color: #bbf7d0; }
.att-day-dot.lv4 { background: #16a34a; color: #fff; border-color: #15803d; box-shadow: 0 2px 8px rgba(22,163,74,.3); }
.att-day-count { font-size: .62rem; color: #9aa3b5; }
#asistencias-table.dataTable {
  border-collapse: separate !important;
  border-spacing: 0 5px !important;
  width: 100% !important;
}
#asistencias-table thead th {
  background-color: var(--system-color-primary) !important;
  color: #fff !important;
  font-size: .75rem !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  letter-spacing: .08em !important;
  padding: 10px 16px !important;
  border: none !important;
  white-space: nowrap;
}
#asistencias-table thead th:first-child { border-radius: 8px 0 0 8px !important; }
#asistencias-table thead th:last-child  { border-radius: 0 8px 8px 0 !important; }
#asistencias-table tbody tr td {
  background: #fff !important;
  border: none !important;
  border-top: 1.5px solid #f0f2f7 !important;
  border-bottom: 1.5px solid #f0f2f7 !important;
  color: #3a4155 !important;
  padding: 11px 16px !important;
  font-size: .875rem !important;
  vertical-align: middle !important;
  transition: background .12s !important;
}
#asistencias-table tbody tr td:first-child {
  border-left: 1.5px solid #f0f2f7 !important;
  border-radius: 10px 0 0 10px !important;
}
#asistencias-table tbody tr td:last-child {
  border-right: 1.5px solid #f0f2f7 !important;
  border-radius: 0 10px 10px 0 !important;
}
#asistencias-table tbody tr:hover td {
  background: color-mix(in srgb, var(--system-color-primary) 6%, #fff) !important;
  border-color: color-mix(in srgb, var(--system-color-primary) 20%, #f0f2f7) !important;
  color: var(--system-color-primary) !important;
}
.day-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: .82rem;
}
.day-dot-chip {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--system-color-primary);
  flex-shrink: 0;
}
.hora-pill {
  display: inline-block;
  background: color-mix(in srgb, var(--system-color-primary) 10%, #fff);
  border: 1px solid color-mix(in srgb, var(--system-color-primary) 25%, #fff);
  color: var(--system-color-primary);
  border-radius: 20px;
  padding: 2px 10px;
  font-size: .8rem;
  font-weight: 600;
}
#att-wrap .dataTables_wrapper .dataTables_length,
#att-wrap .dataTables_wrapper .dataTables_filter,
#att-wrap .dataTables_wrapper .dataTables_info,
#att-wrap .dataTables_wrapper .dataTables_paginate {
  color: #5a6478 !important;
  font-size: .8rem;
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
}
#att-wrap .dataTables_wrapper .dataTables_filter input,
#att-wrap .dataTables_wrapper .dataTables_length select {
  border: 1.5px solid #e5e7ef !important;
  border-radius: 10px !important;
  padding: 4px 10px !important;
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
  font-size: .82rem !important;
  color: #3a4155 !important;
  outline: none;
}
#att-wrap .dataTables_wrapper .dataTables_paginate .paginate_button {
  border-radius: 8px !important;
  border: 1.5px solid #e5e7ef !important;
  color: #5a6478 !important;
  margin: 0 2px !important;
  font-size: .8rem !important;
}
#att-wrap .dataTables_wrapper .dataTables_paginate .paginate_button.current,
#att-wrap .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--system-color-primary) !important;
  border-color: var(--system-color-primary) !important;
  color: #fff !important;
}
</style>

<div id="att-wrap">

  <h3 class="mb-3" style="font-weight:700;font-size:1.1rem;color:#1a2235;">
    <i class="fa fa-calendar-check-o" style="color:var(--system-color-primary);margin-right:8px"></i>
    Historial de Asistencias
  </h3>

  <div class="att-month-selector">
    <label>Mes analizado</label>
    <select id="att-month-select"></select>
  </div>

  <div class="att-metrics">
    <div class="att-card">
      <span class="att-card-label">Asistencias</span>
      <span class="att-card-value c-purple" id="stat-total">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Días hábiles</span>
      <span class="att-card-value" id="stat-habiles">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Faltas</span>
      <span class="att-card-value c-red" id="stat-faltas">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Racha actual</span>
      <span class="att-card-value c-green" id="stat-racha">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Mejor racha</span>
      <span class="att-card-value c-blue" id="stat-mejor-racha">—</span>
    </div>
  </div>

  <div class="att-progress-wrap">
    <div class="att-progress-header">
      <span class="att-progress-title">Tasa de asistencia del mes</span>
      <span class="att-badge-stat" id="att-badge">—</span>
    </div>
    <div class="att-bar-track">
      <div class="att-bar-fill" id="att-bar" style="width:0%"></div>
    </div>
    <div class="att-bar-labels">
      <span>Malo &lt;40%</span>
      <span>Regular 40–65%</span>
      <span>Bueno 66–84%</span>
      <span>Excelente ≥85%</span>
    </div>
  </div>

  <div class="att-week-grid">
    <div class="att-week-title">Frecuencia por día (mes seleccionado)</div>
    <div class="att-days-row" id="att-week-row"></div>
  </div>

  <table id="asistencias-table" class="table">
    <thead>
      <tr>
        <th>Día</th>
        <th>Fecha</th>
        <th>Hora</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

</div>

<script>
(function () {

  var ATT_CLIENTE_ID = <?= intval($id) ?>;

  function norm(str) {
    return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  var DIAS      = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
  var DIAS_NORM = DIAS.map(norm);
  var statsData = [];
  var statsLoaded = false;
  var tableLoaded = false;

  /* ── Días hábiles en el mes (lun–sab) ── */
  function diasHabilesEnMes(year, month) {
    var d = new Date(year, month - 1, 1);
    var count = 0;
    while (d.getMonth() === month - 1) {
      var dow = d.getDay();
      if (dow >= 1 && dow <= 6) count++;
      d.setDate(d.getDate() + 1);
    }
    return count;
  }

  /* ── Racha ── */
  function calcRacha(sortedDesc) {
    if (!sortedDesc.length) return { actual: 0, mejor: 0 };
    var setF = {};
    sortedDesc.forEach(function(f){ setF[f] = true; });
    var asc  = sortedDesc.slice().reverse();
    var mejor = 1, curr = 1;
    for (var i = 1; i < asc.length; i++) {
      var prev = new Date(asc[i-1]), cur = new Date(asc[i]);
      var diff = (cur - prev) / 86400000;
      if (diff === 1 || (diff === 2 && new Date(asc[i-1]).getDay() === 6)) {
        curr++; if (curr > mejor) mejor = curr;
      } else curr = 1;
    }
    var racha = 0;
    var d = new Date(); d.setHours(0,0,0,0);
    for (var j = 0; j < 90; j++) {
      var dow = d.getDay();
      if (dow >= 1 && dow <= 6) {
        var key = d.toISOString().slice(0,10);
        if (setF[key]) racha++;
        else break;
      }
      d.setDate(d.getDate() - 1);
    }
    return { actual: racha, mejor: mejor };
  }

  /* ── Selector de meses ── */
  function buildMonthOptions(data) {
    var meses = {};
    data.forEach(function(r){ meses[r.fecha.slice(0,7)] = true; });
    var sel  = document.getElementById('att-month-select');
    sel.innerHTML = '';
    var keys = Object.keys(meses).sort().reverse();
    keys.forEach(function(k) {
      var parts = k.split('-');
      var lbl = new Date(parts[0], parts[1]-1, 1)
        .toLocaleDateString('es-CO', { month:'long', year:'numeric' });
      var opt = document.createElement('option');
      opt.value = k;
      opt.textContent = lbl.charAt(0).toUpperCase() + lbl.slice(1);
      sel.appendChild(opt);
    });
    return keys[0] || null;
  }

  /* ── Actualizar estadísticas ── */
  function updateStats(monthKey) {
    if (!monthKey) return;
    var parts    = monthKey.split('-');
    var y        = parseInt(parts[0]);
    var m        = parseInt(parts[1]);
    var filtered = statsData.filter(function(r){ return r.fecha.indexOf(monthKey) === 0; });
    var total    = filtered.length;
    var habiles  = diasHabilesEnMes(y, m);
    var faltas   = Math.max(0, habiles - total);
    var pct      = habiles ? Math.round((total / habiles) * 100) : 0;

    var uniqFechas = [];
    var seen = {};
    statsData.forEach(function(r){
      var f = r.fecha.slice(0,10);
      if (!seen[f]) { seen[f] = true; uniqFechas.push(f); }
    });
    uniqFechas.sort().reverse();
    var rachas = calcRacha(uniqFechas);

    document.getElementById('stat-total').textContent       = total;
    document.getElementById('stat-habiles').textContent     = habiles;
    document.getElementById('stat-faltas').textContent      = faltas;
    document.getElementById('stat-racha').textContent       = rachas.actual + ' días';
    document.getElementById('stat-mejor-racha').textContent = rachas.mejor  + ' días';

    var bar   = document.getElementById('att-bar');
    var badge = document.getElementById('att-badge');
    bar.style.width = pct + '%';

    if (pct >= 85) {
      badge.className = 'att-badge-stat ab-excelente';
      badge.textContent = pct + '% · Excelente';
      bar.style.background = '#16a34a';
    } else if (pct >= 66) {
      badge.className = 'att-badge-stat ab-bueno';
      badge.textContent = pct + '% · Bueno';
      bar.style.background = '#2563eb';
    } else if (pct >= 40) {
      badge.className = 'att-badge-stat ab-regular';
      badge.textContent = pct + '% · Regular';
      bar.style.background = '#ca8a04';
    } else {
      badge.className = 'att-badge-stat ab-malo';
      badge.textContent = pct + '% · Malo';
      bar.style.background = '#dc2626';
    }

    /* Heatmap */
    var dayCount = {};
    DIAS_NORM.forEach(function(dn){ dayCount[dn] = 0; });
    filtered.forEach(function(r){
      var n = norm(r.dia_semana);
      if (typeof dayCount[n] !== 'undefined') dayCount[n]++;
    });
    var maxDay = 1;
    DIAS_NORM.forEach(function(dn){ if (dayCount[dn] > maxDay) maxDay = dayCount[dn]; });

    var html = '';
    DIAS.forEach(function(d, i){
      var c   = dayCount[DIAS_NORM[i]] || 0;
      var rat = c / maxDay;
      var lv  = c === 0 ? 0 : rat < .33 ? 1 : rat < .66 ? 2 : rat < 1 ? 3 : 4;
      html += '<div class="att-day-cell">'
            + '<span class="att-day-label">' + d.slice(0,3) + '</span>'
            + '<div class="att-day-dot lv' + lv + '" title="' + d + ': ' + c + ' asistencias">' + c + '</div>'
            + '<span class="att-day-count">' + c + ' vez' + (c !== 1 ? 'es' : '') + '</span>'
            + '</div>';
    });
    document.getElementById('att-week-row').innerHTML = html;
  }

  /* ── Cargar stats ── */
  function cargarStats() {
    if (statsLoaded) return;
    statsLoaded = true;
    $.get('get_att_stats.php', { cliente_id: ATT_CLIENTE_ID }, function(json) {
      statsData = (json && json.data) ? json.data : [];
      var def = buildMonthOptions(statsData);
      updateStats(def);
      document.getElementById('att-month-select').addEventListener('change', function(){
        updateStats(this.value);
      });
    }).fail(function(){
      console.warn('get_att_stats.php no respondió correctamente');
    });
  }

  /* ── Cargar tabla DataTable ── */
  function cargarTabla() {
    if (tableLoaded) return;
    tableLoaded = true;
    if (!$.fn.DataTable.isDataTable('#asistencias-table')) {
      $('#asistencias-table').DataTable({
        ajax: {
          url : 'get_asistencias.php',
          type: 'GET',
          data: { cliente_id: ATT_CLIENTE_ID },
          dataSrc: 'data'
        },
        columns: [
          {
            data: 'dia_semana',
            render: function(d) {
              return '<span class="day-chip"><span class="day-dot-chip"></span>'
                   + d.charAt(0).toUpperCase() + d.slice(1) + '</span>';
            }
          },
          { data: 'fecha' },
          {
            data: 'hora',
            render: function(data, type) {
              if (type === 'display') {
                var parts = data.split(':');
                var hh = parseInt(parts[0], 10), suf = ' am';
                if (hh === 0) hh = 12;
                else if (hh >= 12) { suf = ' pm'; if (hh > 12) hh -= 12; }
                return '<span class="hora-pill">' + hh + ':' + parts[1] + suf + '</span>';
              }
              return data;
            }
          }
        ],
        order: [[1,'desc'],[2,'desc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
      });
    }
  }

  /* ── Disparar al activar el tab ── */
  var tabBtn = document.getElementById('asistencias-tab');
  if (tabBtn) {
    tabBtn.addEventListener('shown.bs.tab', function() {
      cargarStats();
      cargarTabla();
    });
    /* Si ya está activo al cargar la página */
    if (tabBtn.classList.contains('active')) {
      cargarStats();
      cargarTabla();
    }
  } else {
    /* Fallback: si el tab no existe, cargar directo */
    cargarStats();
    cargarTabla();
  }

})();
</script>

<?php endif; ?>