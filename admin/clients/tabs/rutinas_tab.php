<?php if (isset($_SESSION["user_permissions"]) && in_array('Ver Rutinas', $_SESSION["user_permissions"])): ?>
<style>
/* ═══════════════════════════════════════
   RUTINA SEMANAL — tab del cliente
═══════════════════════════════════════ */
.rs-wrap {
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
}
.rs-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 6px;
}
.rs-topbar h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.rs-subtitle {
  font-size: 0.8rem;
  color: #94a3b8;
  margin-bottom: 22px;
}

/* ── Save button ── */
.rs-btn-save {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 22px;
  border-radius: 12px;
  border: none;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  background: linear-gradient(135deg, var(--system-color-primary), var(--system-color-primary-dark, #15803d));
  color: #fff;
  box-shadow: 0 4px 14px color-mix(in srgb, var(--system-color-primary) 30%, transparent);
  transition: opacity .2s, transform .15s;
}
.rs-btn-save:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.rs-btn-save:disabled { opacity: .6; cursor: not-allowed; }

/* ── Week grid ── */
.rs-week-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 14px;
}
@media (max-width: 1199px) { .rs-week-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 639px)  { .rs-week-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 380px)  { .rs-week-grid { grid-template-columns: 1fr; } }

/* ── Day card ── */
.rs-day-card {
  border-radius: 18px;
  box-shadow: 0 2px 10px rgba(0,0,0,.07);
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  border: 2px solid transparent;
  background: #fff;
}
.rs-day-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 28px rgba(0,0,0,.12);
}
.rs-day-card.has-rutina {
  border-color: var(--system-color-primary);
  box-shadow: 0 4px 20px color-mix(in srgb, var(--system-color-primary) 18%, transparent);
}

/* ── Card header (gradients per day) ── */
.rs-day-hdr {
  padding: 18px 12px 14px;
  text-align: center;
  color: #fff;
  position: relative;
}
.rs-day-hdr.d-lunes     { background: linear-gradient(145deg,#60a5fa,#2563eb); }
.rs-day-hdr.d-martes    { background: linear-gradient(145deg,#4ade80,#16a34a); }
.rs-day-hdr.d-miercoles { background: linear-gradient(145deg,#c084fc,#7c3aed); }
.rs-day-hdr.d-jueves    { background: linear-gradient(145deg,#fb923c,#c2410c); }
.rs-day-hdr.d-viernes   { background: linear-gradient(145deg,#f87171,#b91c1c); }
.rs-day-hdr.d-sabado    { background: linear-gradient(145deg,#38bdf8,#0369a1); }
.rs-day-hdr.d-domingo   { background: linear-gradient(145deg,#f472b6,#be185d); }

.rs-day-dot {
  position: absolute;
  top: 9px;
  right: 9px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(255,255,255,.35);
  transition: background .25s, box-shadow .25s;
}
.rs-day-card.has-rutina .rs-day-dot {
  background: #fff;
  box-shadow: 0 0 0 3px rgba(255,255,255,.35);
}
.rs-day-abbrev {
  font-size: 2.2rem;
  font-weight: 900;
  letter-spacing: -2px;
  line-height: 1;
  text-shadow: 0 2px 6px rgba(0,0,0,.18);
}
.rs-day-full {
  font-size: .62rem;
  font-weight: 600;
  opacity: .85;
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-top: 3px;
}

/* ── Card body ── */
.rs-day-body { padding: 14px 12px 13px; }

.rs-day-select {
  width: 100%;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 7px 8px;
  font-size: .8rem;
  color: #374151;
  background: #f8fafc;
  cursor: pointer;
  outline: none;
  transition: border-color .2s, background .2s;
}
.rs-day-select:focus {
  border-color: var(--system-color-primary);
  background: #fff;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--system-color-primary) 14%, transparent);
}

/* ── Rest tag ── */
.rs-rest {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  margin-top: 10px;
  font-size: .74rem;
  color: #94a3b8;
  letter-spacing: .05em;
}

/* ── Toggle exercises ── */
.rs-ej-toggle {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .75rem;
  font-weight: 600;
  color: var(--system-color-primary);
  background: none;
  border: none;
  padding: 0;
  margin-top: 8px;
  cursor: pointer;
  transition: opacity .2s;
}
.rs-ej-toggle:hover { opacity: .7; }

/* ── Exercise list ── */
.rs-ej-list {
  margin-top: 8px;
  border-top: 1px solid #f1f5f9;
  padding-top: 7px;
}
.rs-ej-item {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  padding: 4px 0;
  font-size: .77rem;
  color: #475569;
  border-bottom: 1px dashed #e2e8f0;
}
.rs-ej-item:last-child { border-bottom: none; }
.rs-ej-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--system-color-primary);
  color: #fff;
  font-size: .63rem;
  font-weight: 800;
  flex-shrink: 0;
  margin-top: 1px;
}
.rs-ej-info { flex: 1; }
.rs-ej-name { font-weight: 600; color: #1e293b; line-height: 1.3; }
.rs-ej-meta { font-size: .69rem; color: #94a3b8; }

/* ── Spinner ── */
.rs-spinner-wrap {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}
</style>

<div class="rs-wrap" id="rsWrap" data-cliente-id="<?= (int)$id ?>">

  <div class="rs-topbar">
    <h3>
      <i class="material-icons" style="vertical-align:middle;font-size:22px">fitness_center</i>
      Rutina Semanal
    </h3>
    <button class="rs-btn-save" id="rsBtnGuardar">
      <i class="fa fa-save"></i> Guardar Semana
    </button>
  </div>
  <p class="rs-subtitle">Asigna una rutina a cada día. Los días sin rutina se marcan como descanso.</p>

  <div class="rs-spinner-wrap" id="rsLoading">
    <div class="spinner-border" style="color:var(--system-color-primary)" role="status"></div>
  </div>

  <div class="rs-week-grid" id="rsWeekGrid" style="display:none"></div>
</div>
<?php endif; ?>
