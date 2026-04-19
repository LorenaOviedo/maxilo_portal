/**
 * Funcionalidad de refresco automático del dashboard.
 *
 * Refresca los datos del dashboard cada 60 segundos mediante AJAX.
 */

const AGENDA_COLORES = ['#b8e6e3', '#a8e0a3', '#ffe0a3', '#d4b8e6', '#b8cee6'];

// ── Refresco automático ───────────────────────────────────────────

async function refrescarDashboard() {
    try {
        const res  = await fetch(`${API_URL}?modulo=dashboard&accion=resumen`);
        const json = await res.json();

        if (!json.success) return;

        // Métricas numéricas
        setText('citas-hoy-total',        json.citas_hoy);
        setText('citas-atendidas-hoy',    json.citas_atendidas_hoy);
        setText('citas-atendidas-ayer',   json.citas_atendidas_ayer);
        setText('facturas-pendientes',    json.facturas_pendientes);

        // Desglose por motivo de consulta
        const motivoEl = document.getElementById('citas-hoy-motivo');
        if (motivoEl) {
            motivoEl.innerHTML = json.citas_hoy_motivo.length === 0
                ? '<div style="color:#999;">Sin citas registradas</div>'
                : json.citas_hoy_motivo
                    .map(f => `<div style="margin-bottom:8px;">
                                   <strong>${f.total}</strong> ${escHtml(f.motivo)}
                               </div>`)
                    .join('');
        }

        // Agenda
        const agendaEl = document.getElementById('agenda-lista');
        if (agendaEl) {
            agendaEl.innerHTML = json.agenda.length === 0
                ? `<li class="agenda-item" style="background-color:#f5f5f5; color:#999;">
                       <span class="agenda-item-name">Sin citas pendientes</span>
                   </li>`
                : json.agenda
                    .map((c, i) => `
                        <li class="agenda-item"
                            style="background-color:${AGENDA_COLORES[i % AGENDA_COLORES.length]};">
                            <span class="agenda-item-name">
                                ${escHtml(c.paciente)}
                                <small style="display:block;font-size:11px;opacity:.75;">
                                    ${escHtml(c.motivo)}
                                </small>
                            </span>
                            <span class="agenda-item-time">${escHtml(c.hora)}</span>
                        </li>`)
                    .join('');
        }

    } catch (err) {
        console.warn('Dashboard: error al refrescar', err);
    }
}

// ── Helpers ───────────────────────────────────────────────────────

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

// ── Iniciar refresco cada 60 s ────────────────────────────────────
setInterval(refrescarDashboard, 60_000);