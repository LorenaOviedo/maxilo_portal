/**
 * reportes.js
 * Controlador del módulo de reportes.
 * Depende de: API_URL, SheetJS (XLSX)
 */

const reporteController = {
 
    _datos: null,   // último reporte generado
 
    // ─────────────────────────────────────────────────────────────────────
    // GENERAR
    // ─────────────────────────────────────────────────────────────────────
 
    async generar() {
        const tipo   = document.getElementById('tipoReporte')?.value;
        const desde  = document.getElementById('fechaInicio')?.value;
        const hasta  = document.getElementById('fechaFin')?.value;
 
        if (!tipo || !desde || !hasta) {
            this._notify('Completa todos los filtros', 'warning'); return;
        }
        if (desde > hasta) {
            this._notify('La fecha de inicio no puede ser mayor a la fecha fin', 'warning'); return;
        }
 
        this._setLoading(true);
 
        try {
            const extra  = this._getExtraParams();
        console.log('Generando reporte:', { tipo, desde, hasta, ...extra });
        const params = new URLSearchParams({
                modulo: 'reportes',
                accion: 'generar_reporte',
                tipo, desde, hasta,
                ...extra,
            });
            const r     = await fetch(`${API_URL}?${params}`);
            const texto = await r.text();
            console.log('Respuesta reporte (status', r.status, '):', texto.substring(0, 300));
            let data;
            try { data = JSON.parse(texto); }
            catch(e) {
                this._notify('Error del servidor — ver consola para detalles', 'error');
                this._setLoading(false); return;
            }
 
            if (!data.success) {
                this._notify(data.message || 'Error al generar', 'error');
                this._setLoading(false); return;
            }
 
            this._datos = data;
            this._renderReporte(data);
            this._setLoading(false);
 
        } catch (err) {
            console.error('reporteController.generar:', err);
            this._notify('Error de conexión', 'error');
            this._setLoading(false);
        }
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // RENDERIZAR
    // ─────────────────────────────────────────────────────────────────────
 
    _renderReporte(data) {
        // Mostrar contenedor
        document.getElementById('estadoVacio').style.display    = 'none';
        document.getElementById('reporteResultado').style.display = '';
 
        // Título
        const desde = this._fmtFecha(document.getElementById('fechaInicio').value);
        const hasta = this._fmtFecha(document.getElementById('fechaFin').value);
        document.getElementById('resumenTitulo').textContent =
            `${data.titulo} · ${desde} — ${hasta}`;
        document.getElementById('tablaTitulo').textContent = data.titulo;
        document.getElementById('tablaCount').textContent  =
            `${data.filas?.length ?? 0} registro(s)`;
 
        // Tarjetas de resumen
        const grid = document.getElementById('resumenGrid');
        grid.innerHTML = (data.resumen ?? []).map(item => `
            <div class="resumen-item">
                <div class="resumen-valor">${item.valor}</div>
                <div class="resumen-label">${item.label}</div>
            </div>`).join('');
 
        // Tabla — encabezados
        const thead = document.getElementById('tablaHead');
        thead.innerHTML = '<tr>' +
            (data.columnas ?? []).map(c => `<th>${c}</th>`).join('') +
            '</tr>';
 
        // Tabla — filas
        const tbody = document.getElementById('tablaBody');
        if (!data.filas?.length) {
            tbody.innerHTML = `<tr><td colspan="${data.columnas?.length ?? 1}"
                style="text-align:center;padding:24px;color:#adb5bd;">
                Sin datos para el período seleccionado</td></tr>`;
            return;
        }
 
        tbody.innerHTML = data.filas.map(fila => `
            <tr>${fila.map(cel => `<td>${cel ?? '—'}</td>`).join('')}</tr>`
        ).join('');
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // EXPORTAR EXCEL (SheetJS)
    // ─────────────────────────────────────────────────────────────────────
 
    exportarExcel() {
        if (!this._datos?.filas?.length) {
            this._notify('Genera un reporte primero', 'warning'); return;
        }
 
        if (typeof XLSX === 'undefined') {
            this._notify('SheetJS no está disponible', 'error'); return;
        }
 
        const desde = document.getElementById('fechaInicio').value;
        const hasta = document.getElementById('fechaFin').value;
 
        // Preparar datos: encabezados + filas
        const wsData = [
            this._datos.columnas,
            ...this._datos.filas,
        ];
 
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(wsData);
 
        // Ancho de columnas automático
        const colWidths = this._datos.columnas.map((_, ci) => ({
            wch: Math.max(
                this._datos.columnas[ci].length,
                ...this._datos.filas.map(r => String(r[ci] ?? '').length)
            ) + 2
        }));
        ws['!cols'] = colWidths;
 
        XLSX.utils.book_append_sheet(wb, ws, this._datos.titulo.substring(0, 31));
 
        const nombre = `${this._datos.tipo}_${desde}_${hasta}.xlsx`;
        XLSX.writeFile(wb, nombre);
 
        this._notify('Excel exportado correctamente', 'success');
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // EXPORTAR PDF (window.print)
    // ─────────────────────────────────────────────────────────────────────
 
    exportarPDF() {
        if (!this._datos?.filas?.length) {
            this._notify('Genera un reporte primero', 'warning'); return;
        }
 
        const desde = this._fmtFecha(document.getElementById('fechaInicio').value);
        const hasta = this._fmtFecha(document.getElementById('fechaFin').value);
        const data  = this._datos;
        const anio  = new Date().getFullYear();
        const ahora = new Date();
        const hoy   = this._fmtFecha(this._toISO(ahora));
 
        const filasHTML = data.filas.map(fila =>
            `<tr>${fila.map(cel => `<td>${cel ?? '&#x2014;'}</td>`).join('')}</tr>`
        ).join('');
 
        const resumenHTML = (data.resumen ?? []).map(item => `
            <div class="res-item">
                <div class="res-valor">${item.valor}</div>
                <div class="res-label">${item.label}</div>
            </div>`).join('');
 
        const ventana = window.open('', '_blank', 'width=1100,height=800');
        ventana.document.write(`<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>${data.titulo}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html {
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
            padding: 12mm 14mm;
        }
 
        /* ── Encabezado ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #192D8C;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header-clinica-nombre {
            font-size: 18px; font-weight: 900;
            color: #192D8C; text-transform: uppercase;
        }
        .header-clinica-sub {
            font-size: 9px; color: #555;
            text-transform: uppercase; margin-top: 2px;
        }
        .header-meta {
            text-align: right; font-size: 10px; color: #555; line-height: 1.6;
        }
 
        /* ── Título ── */
        .titulo {
            font-size: 14px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .periodo {
            font-size: 11px; color: #555; margin-bottom: 16px;
        }
 
        /* ── Resumen ── */
        .resumen {
            display: flex; flex-wrap: wrap; gap: 10px;
            margin-bottom: 20px;
        }
        .res-item {
            background: #f0f4ff;
            border-left: 3px solid #192D8C;
            border-radius: 4px;
            padding: 8px 14px;
            min-width: 120px;
        }
        .res-valor { font-size: 18px; font-weight: 700; color: #192D8C; }
        .res-label { font-size: 9px; color: #555; text-transform: uppercase;
                     letter-spacing: .5px; margin-top: 2px; }
 
        /* ── Tabla ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        thead th {
            background: #192D8C;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        tbody tr:nth-child(even) { background: #f8f9ff; }
        tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e9ecef;
        }
 
        /* ── Pie ── */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #adb5bd;
        }
 
        @media print {
            body { padding: 8mm 10mm; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
 
    <div class="header">
        <div>
            <div class="header-clinica-nombre">Maxilofacial Texcoco</div>
            <div class="header-clinica-sub">Ortodoncia &#xB7; Cirugía Maxilofacial &#xB7; Patología Oral</div>
        </div>
        <div class="header-meta">
            <strong>Fecha de generación:</strong> ${hoy}<br>
            Dr. Alfonso Ayala Gómez
        </div>
    </div>
 
    <div class="titulo">${data.titulo}</div>
    <div class="periodo">Período: ${desde} &#x2014; ${hasta}</div>
 
    <div class="resumen">${resumenHTML}</div>
 
    <table>
        <thead>
            <tr>${data.columnas.map(c => `<th>${c}</th>`).join('')}</tr>
        </thead>
        <tbody>${filasHTML}</tbody>
    </table>
 
    <div class="footer">
        <span>Sistema Maxilofacial Texcoco &#x2014; ${anio}</span>
        <span>Total registros: ${data.filas.length}</span>
    </div>
 
<script>
    window.onload = function() { window.print(); window.close(); };
<\/script>
</body>
</html>`);
        ventana.document.close();
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // PERÍODO AUTOMÁTICO
    // ─────────────────────────────────────────────────────────────────────
 
    _aplicarPeriodo(valor) {
        if (valor === 'personalizado') return; // no tocar las fechas
        const hoy = new Date();
        const y   = hoy.getFullYear();
        const m   = hoy.getMonth();
        let desde, hasta;
        switch (valor) {
            case 'este_mes':
                desde = new Date(y, m, 1);
                hasta = new Date(y, m + 1, 0);
                break;
            case 'mes_anterior':
                desde = new Date(y, m - 1, 1);
                hasta = new Date(y, m, 0);
                break;
            case 'este_anio':
                desde = new Date(y, 0, 1);
                hasta = new Date(y, 11, 31);
                break;
            default:
                return;
        }
        document.getElementById('fechaInicio').value = this._toISO(desde);
        document.getElementById('fechaFin').value    = this._toISO(hasta);
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // FILTROS CONTEXTUALES
    // ─────────────────────────────────────────────────────────────────────
 
    _filtrosConfig: {
        citas:    ['filtroPaciente', 'filtroEspecialista', 'filtroEstatusCita'],
        pagos:    ['filtroMetodoPago', 'filtroEstatusPago'],
        facturas: ['filtroPacienteFactura', 'filtroEstatusFactura'],
    },
 
    inicializarFiltros() {
        if (!window.CATALOGOS_RPT) return;
 
        // Especialistas
        this._poblarSelect('rptEspecialista', CATALOGOS_RPT.especialistas,
            'id_especialista', 'nombre_completo', 'Todos');
 
        // Estatus cita
        this._poblarSelect('rptEstatusCita', CATALOGOS_RPT.estatusCita,
            'id_estatus_cita', 'estatus_cita', 'Todos');
 
        // Métodos de pago
        this._poblarSelect('rptMetodoPago', CATALOGOS_RPT.metodosPago,
            'id_metodo_pago', 'metodo_pago', 'Todos');
 
        // Estatus factura
        this._poblarSelect('rptEstatusFactura', CATALOGOS_RPT.estatusFactura,
            'id_estatus_factura', 'estatus_factura', 'Todos');
 
        // Buscadores de paciente
        this._bindPacienteSearch('rptPacienteInput',    'rptPacienteValue',    'rptPacienteDropdown');
        this._bindPacienteSearch('rptPacienteFactInput','rptPacienteFactValue','rptPacienteFactDropdown');
    },
 
    _poblarSelect(id, items, valKey, labelKey, placeholder = 'Todos') {
        const sel = document.getElementById(id);
        if (!sel || !items) return;
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
            const opt       = document.createElement('option');
            opt.value       = item[valKey];
            opt.textContent = item[labelKey];
            sel.appendChild(opt);
        });
    },
 
    _bindPacienteSearch(inputId, hiddenId, dropdownId) {
        const input    = document.getElementById(inputId);
        const hidden   = document.getElementById(hiddenId);
        const dropdown = document.getElementById(dropdownId);
        if (!input || !hidden || !dropdown) return;
 
        const pacientes = window.CATALOGOS_RPT?.pacientes ?? [];
 
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            hidden.value = ''; // sin selección = Todos
            if (q.length < 1) { dropdown.style.display = 'none'; return; }
 
            const resultados = pacientes.filter(p =>
                p.nombre_completo.toLowerCase().includes(q)
            ).slice(0, 8);
 
            if (!resultados.length) {
                dropdown.innerHTML = '<div class="pac-drop-item pac-drop-empty">Sin resultados</div>';
                dropdown.style.display = 'block';
                return;
            }
 
            const todosItem = `<div class="pac-drop-item" data-id="" data-nombre=""
                style="padding:10px 14px; font-size:13px; cursor:pointer;
                       border-bottom:1px solid #f1f3f5; color:#6c757d; font-style:italic;"
                onmouseover="this.style.background='#f0faf9'"
                onmouseout="this.style.background=''">
                Todos los pacientes
            </div>`;
 
            dropdown.innerHTML = todosItem + resultados.map(p => `
                <div class="pac-drop-item" data-id="${p.numero_paciente}"
                    data-nombre="${p.nombre_completo.replace(/"/g,'&quot;')}"
                    style="padding:10px 14px; font-size:13px; cursor:pointer;
                           border-bottom:1px solid #f1f3f5;"
                    onmouseover="this.style.background='#f0faf9'"
                    onmouseout="this.style.background=''">
                    ${p.nombre_completo}
                </div>`).join('');
 
            dropdown.querySelectorAll('.pac-drop-item').forEach(item => {
                item.addEventListener('mousedown', () => {
                    if (!item.dataset.id) {
                        // Seleccionó "Todos" — mostrar texto pero no filtrar
                        input.value  = 'Todos los pacientes';
                        hidden.value = '';
                    } else {
                        input.value  = item.dataset.nombre;
                        hidden.value = item.dataset.id;
                    }
                    dropdown.style.display = 'none';
                });
            });
            dropdown.style.display = 'block';
        });
 
        input.addEventListener('blur', () =>
            setTimeout(() => { dropdown.style.display = 'none'; }, 200)
        );
    },
 
    _mostrarFiltrosExtra(tipo) {
        // Ocultar todos primero
        const todos = ['filtroPaciente','filtroEspecialista','filtroEstatusCita',
                       'filtroMetodoPago','filtroEstatusPago',
                       'filtroPacienteFactura','filtroEstatusFactura'];
        todos.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
 
        // Limpiar valores
        ['rptPacienteInput','rptPacienteFactInput'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['rptPacienteValue','rptPacienteFactValue','rptEspecialista',
         'rptEstatusCita','rptMetodoPago','rptEstatusPago','rptEstatusFactura']
            .forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
 
        const filtros  = this._filtrosConfig[tipo] ?? [];
        const extraDiv = document.getElementById('filtrosExtra');
 
        if (filtros.length) {
            filtros.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = '';
            });
            if (extraDiv) extraDiv.style.display = '';
        } else {
            if (extraDiv) extraDiv.style.display = 'none';
        }
    },
 
    _getExtraParams() {
        const params = {};
        const pac    = document.getElementById('rptPacienteValue')?.value;
        const pacF   = document.getElementById('rptPacienteFactValue')?.value;
        const esp    = document.getElementById('rptEspecialista')?.value;
        const estC   = document.getElementById('rptEstatusCita')?.value;
        const met    = document.getElementById('rptMetodoPago')?.value;
        const estP   = document.getElementById('rptEstatusPago')?.value;
        const estF   = document.getElementById('rptEstatusFactura')?.value;
 
        if (pac)  params.numero_paciente   = pac;
        if (pacF) params.numero_paciente   = pacF;
        if (esp)  params.id_especialista   = esp;
        if (estC) params.id_estatus_cita   = estC;
        if (met)  params.id_metodo_pago    = met;
        if (estP) params.estatus           = estP;
        if (estF) params.id_estatus_factura= estF;
 
        return params;
    },
 
    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
 
    _toISO(date) {
        // Usar fecha local para evitar desfase de zona horaria
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    },
 
    _fmtFecha(str) {
        if (!str) return '—';
        const [y, m, d] = str.split('-');
        return `${d}/${m}/${y}`;
    },
 
    _setLoading(show) {
        const btn = document.getElementById('btnGenerar');
        if (!btn) return;
        if (show) {
            btn.disabled     = true;
            btn.innerHTML    = '<i class="ri-loader-4-line"></i> Generando...';
        } else {
            btn.disabled     = false;
            btn.innerHTML    = '<i class="ri-bar-chart-line"></i> Generar reporte';
        }
    },
 
    _notify(msg, tipo = 'info') {
        // Usar CatalogTable si está disponible, si no alert
        if (window.CatalogTable?.showNotification) {
            CatalogTable.showNotification(msg, tipo);
        } else {
            alert(msg);
        }
    },
};
 
// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS
// ─────────────────────────────────────────────────────────────────────────────
 
document.addEventListener('DOMContentLoaded', () => {
 
    document.getElementById('btnGenerar')
        ?.addEventListener('click', () => reporteController.generar());
 
    document.getElementById('btnExcel')
        ?.addEventListener('click', () => reporteController.exportarExcel());
 
    document.getElementById('btnPdf')
        ?.addEventListener('click', () => reporteController.exportarPDF());
 
    document.getElementById('periodo')
        ?.addEventListener('change', e =>
            reporteController._aplicarPeriodo(e.target.value)
        );
 
    // Al cambiar fechas manualmente → poner selector en "personalizado"
    document.getElementById('fechaInicio')
        ?.addEventListener('change', () => {
            const sel = document.getElementById('periodo');
            if (sel) sel.value = 'personalizado';
        });
 
    document.getElementById('fechaFin')
        ?.addEventListener('change', () => {
            const sel = document.getElementById('periodo');
            if (sel) sel.value = 'personalizado';
        });
 
    // Al cambiar tipo de reporte → mostrar filtros contextuales
    document.getElementById('tipoReporte')
        ?.addEventListener('change', e =>
            reporteController._mostrarFiltrosExtra(e.target.value)
        );
 
    // Inicializar catálogos de filtros
    reporteController.inicializarFiltros();
 
    // Aplicar período inicial
    reporteController._aplicarPeriodo(
        document.getElementById('periodo')?.value ?? 'este_mes'
    );
});