<?php if (isset($_SESSION["user_permissions"])
          && in_array('Ver Asistencias', $_SESSION["user_permissions"])): ?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">

<style>
:root {
  --att-bg:      #0f1117;
  --att-surface: #1a1d27;
  --att-border:  #2a2d3a;
  --att-text:    #e8eaf0;
  --att-muted:   #6b7080;
  --att-green:   #22c55e;
  --att-yellow:  #f59e0b;
  --att-orange:  #f97316;
  --att-red:     #ef4444;
  --att-blue:    #3b82f6;
  --att-accent:  #6366f1;
}

#asistencias-wrapper {
  font-family: 'DM Sans', sans-serif;
  background: var(--att-bg);
  border-radius: 16px;
  padding: 28px;
  margin-top: 20px;
  color: var(--att-text);
}
#asistencias-wrapper h3 {
  font-family: 'Syne', sans-serif;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -.3px;
  color: #fff;
  margin: 0 0 20px;
}

/* Selector mes */
.att-month-selector {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 22px;
}
.att-month-selector label {
  font-size: .8rem;
  font-weight: 500;
  color: var(--att-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
}
.att-month-selector select {
  background: var(--att-surface);
  border: 1px solid var(--att-border);
  color: var(--att-text);
  border-radius: 8px;
  padding: 6px 12px;
  font-family: 'DM Sans', sans-serif;
  font-size: .875rem;
  cursor: pointer;
  outline: none;
  transition: border-color .2s;
}
.att-month-selector select:focus { border-color: var(--att-accent); }

/* Métricas */
.att-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.att-card {
  background: var(--att-surface);
  border: 1px solid var(--att-border);
  border-radius: 14px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  transition: transform .18s, border-color .18s;
}
.att-card:hover { transform: translateY(-2px); border-color: var(--att-accent); }
.att-card-label {
  font-size: .72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--att-muted);
}
.att-card-value {
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  line-height: 1;
  color: #fff;
}
.att-card-value.green  { color: var(--att-green);  }
.att-card-value.yellow { color: var(--att-yellow); }
.att-card-value.red    { color: var(--att-red);    }
.att-card-value.purple { color: #a5b4fc; }

/* Barra progreso */
.att-progress-wrap {
  background: var(--att-surface);
  border: 1px solid var(--att-border);
  border-radius: 14px;
  padding: 20px 22px;
  margin-bottom: 24px;
}
.att-progress-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 14px;
}
.att-progress-title {
  font-size: .8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--att-muted);
}
.att-badge {
  font-size: .78rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: .06em;
}
.badge-excelente { background: rgba(34,197,94,.15);  color: var(--att-green);  border: 1px solid rgba(34,197,94,.3); }
.badge-bueno     { background: rgba(59,130,246,.15); color: var(--att-blue);   border: 1px solid rgba(59,130,246,.3); }
.badge-regular   { background: rgba(245,158,11,.15); color: var(--att-yellow); border: 1px solid rgba(245,158,11,.3); }
.badge-malo      { background: rgba(239,68,68,.15);  color: var(--att-red);    border: 1px solid rgba(239,68,68,.3); }

.att-bar-track {
  height: 10px;
  background: var(--att-border);
  border-radius: 99px;
  overflow: hidden;
}
.att-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .7s cubic-bezier(.4,0,.2,1);
}
.att-bar-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: .7rem;
  color: var(--att-muted);
}

/* Heatmap semanal */
.att-week-grid {
  background: var(--att-surface);
  border: 1px solid var(--att-border);
  border-radius: 14px;
  padding: 20px 22px;
  margin-bottom: 24px;
}
.att-week-title {
  font-size: .8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--att-muted);
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
  gap: 6px;
}
.att-day-label {
  font-size: .68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--att-muted);
}
.att-day-dot {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Syne', sans-serif;
  font-size: .8rem;
  font-weight: 800;
  border: 1px solid var(--att-border);
  transition: transform .15s;
  cursor: default;
}
.att-day-dot:hover { transform: scale(1.1); }
.att-day-dot.level-0 { background: var(--att-border);         color: var(--att-muted); }
.att-day-dot.level-1 { background: rgba(239,68,68,.2);        color: var(--att-red);    border-color: rgba(239,68,68,.4); }
.att-day-dot.level-2 { background: rgba(245,158,11,.2);       color: var(--att-yellow); border-color: rgba(245,158,11,.4); }
.att-day-dot.level-3 { background: rgba(34,197,94,.2);        color: var(--att-green);  border-color: rgba(34,197,94,.4); }
.att-day-dot.level-4 { background: rgba(34,197,94,.5);        color: #fff;              border-color: rgba(34,197,94,.7); box-shadow: 0 0 10px rgba(34,197,94,.3); }
.att-day-count { font-size: .65rem; color: var(--att-muted); }

/* Tabla */
#asistencias-table.dataTable {
  border-collapse: separate !important;
  border-spacing: 0 6px !important;
  width: 100% !important;
}
#asistencias-table thead th {
  background: transparent !important;
  border: none !important;
  color: var(--att-muted);
  font-size: .72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .1em;
  padding: 4px 16px 12px !important;
}
#asistencias-table tbody tr td {
  background: var(--att-surface) !important;
  border: none !important;
  border-top: 1px solid var(--att-border) !important;
  border-bottom: 1px solid var(--att-border) !important;
  color: var(--att-text);
  padding: 12px 16px !important;
  font-size: .875rem;
  transition: background .15s;
}
#asistencias-table tbody tr td:first-child {
  border-left: 1px solid var(--att-border) !important;
  border-radius: 10px 0 0 10px !important;
}
#asistencias-table tbody tr td:last-child {
  border-right: 1px solid var(--att-border) !important;
  border-radius: 0 10px 10px 0 !important;
}
#asistencias-table tbody tr:hover td {
  background: #22253a !important;
  border-color: var(--att-accent) !important;
}
.day-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: .8rem;
}
.day-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--att-accent);
  flex-shrink: 0;
}
.hora-pill {
  display: inline-block;
  background: rgba(99,102,241,.12);
  border: 1px solid rgba(99,102,241,.25);
  color: #a5b4fc;
  border-radius: 20px;
  padding: 2px 10px;
  font-size: .8rem;
  font-weight: 500;
}

/* DataTables dark overrides */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate { color: var(--att-muted) !important; font-size: .8rem; }
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
  background: var(--att-surface) !important;
  border: 1px solid var(--att-border) !important;
  color: var(--att-text) !important;
  border-radius: 8px;
  padding: 4px 10px;
  outline: none;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
  background: var(--att-surface) !important;
  border: 1px solid var(--att-border) !important;
  color: var(--att-muted) !important;
  border-radius: 8px !important;
  margin: 0 2px !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--att-accent) !important;
  border-color: var(--att-accent) !important;
  color: #fff !important;
}
</style>

<div id="asistencias-wrapper">

  <h3><i class="fa fa-calendar-check-o" style="color:var(--att-accent);margin-right:8px"></i> Historial de Asistencias</h3>

  <!-- Selector mes -->
  <div class="att-month-selector">
    <label>Mes analizado</label>
    <select id="att-month-select"></select>
  </div>

  <!-- Métricas -->
  <div class="att-metrics">
    <div class="att-card">
      <span class="att-card-label">Asistencias</span>
      <span class="att-card-value" id="stat-total">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Días hábiles</span>
      <span class="att-card-value" id="stat-habiles">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Faltas</span>
      <span class="att-card-value red" id="stat-faltas">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Racha actual</span>
      <span class="att-card-value green" id="stat-racha">—</span>
    </div>
    <div class="att-card">
      <span class="att-card-label">Mejor racha</span>
      <span class="att-card-value purple" id="stat-mejor-racha">—</span>
    </div>
  </div>

  <!-- Barra porcentaje -->
  <div class="att-progress-wrap">
    <div class="att-progress-header">
      <span class="att-progress-title">Tasa de asistencia del mes</span>
      <span class="att-badge" id="att-badge">—</span>
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

  <!-- Heatmap por día -->
  <div class="att-week-grid">
    <div class="att-week-title">Frecuencia por día (mes seleccionado)</div>
    <div class="att-days-row" id="att-week-row"></div>
  </div>

  <!-- Tabla (todas las entradas, sin cambios) -->
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
  const DIAS = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
  let statsData = []; // datos deduplicados solo para estadísticas

  /* ── Helpers ── */
  function diasHabilesEnMes(year, month) {
    const d = new Date(year, month - 1, 1);
    let count = 0;
    while (d.getMonth() === month - 1) {
      const dow = d.getDay();
      if (dow >= 1 && dow <= 6) count++;
      d.setDate(d.getDate() + 1);
    }
    return count;
  }

  function calcRacha(sorted) {
    // sorted: fechas únicas ['2025-06-03','2025-06-02',...] DESC
    if (!sorted.length) return { actual: 0, mejor: 0 };
    const setFechas = new Set(sorted);

    // mejor racha global
    const asc = [...sorted].reverse();
    let mejor = 1, curr = 1;
    for (let i = 1; i < asc.length; i++) {
      const prev = new Date(asc[i-1]), cur = new Date(asc[i]);
      const diff = (cur - prev) / 86400000;
      // consecutivo si diff=1, o diff=2 saltando domingo (sab→lun)
      if (diff === 1 || (diff === 2 && new Date(asc[i-1]).getDay() === 6)) {
        curr++; mejor = Math.max(mejor, curr);
      } else { curr = 1; }
    }

    // racha actual: hacia atrás desde hoy
    let racha = 0;
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const d = new Date(hoy);
    let intentos = 0;
    while (intentos < 90) {
      const dow = d.getDay();
      if (dow >= 1 && dow <= 6) {
        const key = d.toISOString().slice(0,10);
        if (setFechas.has(key)) racha++;
        else break;
      }
      d.setDate(d.getDate() - 1);
      intentos++;
    }
    return { actual: racha, mejor };
  }

  function buildMonthOptions(data) {
    const meses = {};
    data.forEach(r => {
      const key = r.fecha.slice(0,7); // YYYY-MM
      meses[key] = true;
    });
    const sel = document.getElementById('att-month-select');
    sel.innerHTML = '';
    const keys = Object.keys(meses).sort().reverse();
    keys.forEach(k => {
      const [y, m] = k.split('-');
      const label = new Date(y, m-1, 1)
        .toLocaleDateString('es-CO', { month:'long', year:'numeric' });
      const opt = document.createElement('option');
      opt.value = k;
      opt.textContent = label.charAt(0).toUpperCase() + label.slice(1);
      sel.appendChild(opt);
    });
    return keys[0] || null;
  }

  function updateStats(monthKey) {
    if (!monthKey) return;
    const [y, m] = monthKey.split('-').map(Number);
    const filtered = statsData.filter(r => r.fecha.startsWith(monthKey));
    const total   = filtered.length;
    const habiles = diasHabilesEnMes(y, m);
    const faltas  = Math.max(0, habiles - total);
    const pct     = habiles ? Math.round((total / habiles) * 100) : 0;

    // rachas con todas las fechas únicas históricas
    const allFechas = [...new Set(statsData.map(r => r.fecha.slice(0,10)))].sort().reverse();
    const { actual, mejor } = calcRacha(allFechas);

    document.getElementById('stat-total').textContent        = total;
    document.getElementById('stat-habiles').textContent      = habiles;
    document.getElementById('stat-faltas').textContent       = faltas;
    document.getElementById('stat-racha').textContent        = actual + ' días';
    document.getElementById('stat-mejor-racha').textContent  = mejor + ' días';

    // barra y badge
    const bar   = document.getElementById('att-bar');
    const badge = document.getElementById('att-badge');
    bar.style.width = pct + '%';

    let color;
    if (pct >= 85)      { badge.className='att-badge badge-excelente'; badge.textContent=`${pct}% · Excelente`; color='#22c55e'; }
    else if (pct >= 66) { badge.className='att-badge badge-bueno';     badge.textContent=`${pct}% · Bueno`;     color='#3b82f6'; }
    else if (pct >= 40) { badge.className='att-badge badge-regular';   badge.textContent=`${pct}% · Regular`;   color='#f59e0b'; }
    else                { badge.className='att-badge badge-malo';      badge.textContent=`${pct}% · Malo`;      color='#ef4444'; }
    bar.style.background = `linear-gradient(90deg, ${color}88, ${color})`;

    // heatmap
    const dayCount = { 'Lunes':0,'Martes':0,'Miércoles':0,'Jueves':0,'Viernes':0,'Sábado':0 };
    filtered.forEach(r => {
      const cap = r.dia_semana.charAt(0).toUpperCase() + r.dia_semana.slice(1);
      if (dayCount[cap] !== undefined) dayCount[cap]++;
    });
    const maxDay = Math.max(...Object.values(dayCount), 1);
    document.getElementById('att-week-row').innerHTML = DIAS.map(d => {
      const c = dayCount[d] || 0;
      const ratio = c / maxDay;
      const lvl = c === 0 ? 0 : ratio < .33 ? 1 : ratio < .66 ? 2 : ratio < 1 ? 3 : 4;
      return `<div class="att-day-cell">
        <span class="att-day-label">${d.slice(0,3)}</span>
        <div class="att-day-dot level-${lvl}" title="${d}: ${c} asistencias">${c}</div>
        <span class="att-day-count">${c} vez${c!==1?'es':''}</span>
      </div>`;
    }).join('');
  }

  /* ── Stats: llamada separada con datos deduplicados ── */
  $.get('get_asistencias_stats.php', { cliente_id: '<?= $id ?>' }, function(json) {
    statsData = json.data || [];
    const defaultMonth = buildMonthOptions(statsData);
    updateStats(defaultMonth);

    document.getElementById('att-month-select').addEventListener('change', function(){
      updateStats(this.value);
    });
  });

  /* ── DataTable: intacto, muestra TODAS las entradas ── */
  if (!$.fn.DataTable.isDataTable('#asistencias-table')) {
    $('#asistencias-table').DataTable({
      ajax: {
        url : 'get_asistencias.php',
        type: 'GET',
        data: { cliente_id: '<?= $id ?>' },
        dataSrc: 'data'
      },
      columns: [
        {
          data: 'dia_semana',
          render: d => `<span class="day-chip"><span class="day-dot"></span>${d.charAt(0).toUpperCase()+d.slice(1)}</span>`
        },
        { data: 'fecha' },
        {
          data: 'hora',
          render: function(data, type) {
            if (type === 'display') {
              const [h,m] = data.split(':');
              let hh = parseInt(h,10), suf = ' am';
              if (hh === 0) hh = 12;
              else if (hh >= 12){ suf=' pm'; if (hh>12) hh-=12; }
              return `<span class="hora-pill">${hh}:${m}${suf}</span>`;
            }
            return data;
          }
        }
      ],
      order: [[1,'desc'],[2,'desc']],
      language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });
  }

})();
</script>

<?php endif; ?>