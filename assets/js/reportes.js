/**
 * reportes.js
 * Manejo de reportes con datos de ejemplo, exportación Excel y PDF
 */

// ── Librerías externas ──────────────────────────────────────────────────────
// SheetJS (Excel): https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js
// jsPDF:           https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
// jsPDF-AutoTable: https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js

// ── Datos de ejemplo ────────────────────────────────────────────────────────
const DATOS = {

    pacientes: {
        resumen: [
            { label: 'Total de pacientes',   valor: '156' },
            { label: 'Pacientes nuevos',      valor: '23'  },
            { label: 'Pacientes activos',     valor: '134' },
            { label: 'Pacientes inactivos',   valor: '22'  },
        ],
        columnas: ['Número de Paciente', 'Nombre', 'Total Citas', 'Última Cita', 'Total Pagado', 'Estatus'],
        filas: [
            ['P-001', 'Juana María Rojas Rojas',    '3', '10/11/2025', '$6,000.00', 'Activo'],
            ['P-002', 'Pedro Martínez Sánchez',     '4', '10/11/2025', '$8,500.00', 'Activo'],
            ['P-003', 'Laura Hernández Cruz',       '2', '05/11/2025', '$3,200.00', 'Activo'],
            ['P-004', 'Roberto García López',       '1', '01/11/2025', '$1,500.00', 'Inactivo'],
            ['P-005', 'María Fernández Torres',     '6', '08/11/2025', '$12,000.00','Activo'],
        ],
        badgeCol: 5,
    },

    citas: {
        resumen: [
            { label: 'Total de citas',        valor: '89'  },
            { label: 'Citas completadas',     valor: '67'  },
            { label: 'Citas canceladas',      valor: '8'   },
            { label: 'Citas pendientes',      valor: '14'  },
        ],
        columnas: ['ID Cita', 'Paciente', 'Especialista', 'Fecha', 'Hora', 'Motivo', 'Estatus'],
        filas: [
            ['C-001', 'Juana María Rojas',    'Dr. Roberto Hernández', '10/11/2025', '10:00', 'Control ortodoncia', 'Completada'],
            ['C-002', 'Pedro Martínez',       'Dra. Laura Morales',    '10/11/2025', '11:30', 'Extracción',         'Completada'],
            ['C-003', 'Laura Hernández',      'Dr. Roberto Hernández', '05/11/2025', '09:00', 'Valoración inicial', 'Completada'],
            ['C-004', 'Roberto García',       'Dra. Laura Morales',    '01/11/2025', '16:00', 'Dolor dental',       'Cancelada'],
            ['C-005', 'María Fernández',      'Dr. Roberto Hernández', '12/11/2025', '10:30', 'Control ortodoncia', 'Confirmada'],
        ],
        badgeCol: 6,
    },

    pagos: {
        resumen: [
            { label: 'Total recaudado',       valor: '$48,500.00' },
            { label: 'Pagos realizados',      valor: '67'         },
            { label: 'Pagos pendientes',      valor: '5'          },
            { label: 'Promedio por pago',     valor: '$724.00'    },
        ],
        columnas: ['No. Recibo', 'Paciente', 'Fecha', 'Monto Total', 'Tipo de Pago', 'Estatus'],
        filas: [
            ['REC-0001', 'Juana María Rojas',  '10/11/2025', '$2,000.00', 'Efectivo',       'Pagado'],
            ['REC-0002', 'Pedro Martínez',     '10/11/2025', '$2,500.00', 'Tarjeta débito', 'Pagado'],
            ['REC-0003', 'Laura Hernández',    '05/11/2025', '$1,600.00', 'Transferencia',  'Pagado'],
            ['REC-0004', 'Roberto García',     '01/11/2025', '$800.00',   'Efectivo',       'Pendiente'],
            ['REC-0005', 'María Fernández',    '08/11/2025', '$3,000.00', 'Tarjeta crédito','Pagado'],
        ],
        badgeCol: 5,
    },

    inventario: {
        resumen: [
            { label: 'Total productos',       valor: '42'   },
            { label: 'Productos disponibles', valor: '35'   },
            { label: 'Productos agotados',    valor: '4'    },
            { label: 'Próximos a caducar',    valor: '3'    },
        ],
        columnas: ['Código', 'Producto', 'Tipo', 'Lote', 'Cantidad', 'Caducidad', 'Estatus'],
        filas: [
            ['PROD-001', 'Anestesia Lidocaína 2%', 'Anestésico',  'LOT-2023-001', '95',  '01/06/2025', 'Disponible'],
            ['PROD-002', 'Hilo de sutura 3-0',     'Sutura',       'LOT-2023-002', '48',  '15/08/2025', 'Disponible'],
            ['PROD-003', 'Guantes desechables',    'Instrumental', 'LOT-2024-001', '200', '01/01/2026', 'Disponible'],
            ['PROD-004', 'Amoxicilina 500mg',      'Medicamento',  'LOT-2023-003', '0',   '30/11/2024', 'Agotado'],
            ['PROD-005', 'Resina compuesta A2',    'Material dental','LOT-2024-002','12', '15/03/2025', 'Disponible'],
        ],
        badgeCol: 6,
    },
};

// ── Mapa de badges ──────────────────────────────────────────────────────────
const BADGE_MAP = {
    'activo':      'badge-activo',
    'inactivo':    'badge-inactivo',
    'confirmada':  'badge-confirmada',
    'completada':  'badge-completada',
    'cancelada':   'badge-cancelada',
    'pendiente':   'badge-pendiente',
    'pagado':      'badge-pagado',
    'disponible':  'badge-disponible',
    'agotado':     'badge-agotado',
};

// ── Títulos legibles ────────────────────────────────────────────────────────
const TITULOS = {
    pacientes:  'Pacientes',
    citas:      'Citas',
    pagos:      'Pagos',
    inventario: 'Inventario',
};

// ── Estado actual del reporte ───────────────────────────────────────────────
let reporteActual = null;

// ── DOM ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const btnGenerar  = document.getElementById('btnGenerar');
    const btnExcel    = document.getElementById('btnExcel');
    const btnPdf      = document.getElementById('btnPdf');
    const selectPeriodo = document.getElementById('periodo');

    btnGenerar.addEventListener('click', generarReporte);
    btnExcel.addEventListener('click', exportarExcel);
    btnPdf.addEventListener('click', exportarPDF);

    // Controlar visibilidad del rango de fechas según período
    selectPeriodo.addEventListener('change', function () {
        const fechaInicio = document.getElementById('fechaInicio');
        const fechaFin    = document.getElementById('fechaFin');
        actualizarFechasPorPeriodo(this.value, fechaInicio, fechaFin);
    });

    // Cargar librerías necesarias
    cargarLibrerias();
});

// ── Actualizar fechas según período ────────────────────────────────────────
function actualizarFechasPorPeriodo(periodo, inputInicio, inputFin) {
    const hoy   = new Date();
    let inicio, fin;

    switch (periodo) {
        case 'este_mes':
            inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fin    = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            break;
        case 'mes_anterior':
            inicio = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
            fin    = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
            break;
        case 'este_anio':
            inicio = new Date(hoy.getFullYear(), 0, 1);
            fin    = new Date(hoy.getFullYear(), 11, 31);
            break;
        default:
            return; // personalizado: el usuario elige las fechas
    }

    inputInicio.value = formatDateInput(inicio);
    inputFin.value    = formatDateInput(fin);
}

function formatDateInput(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// ── Generar reporte ─────────────────────────────────────────────────────────
function generarReporte() {
    const tipo      = document.getElementById('tipoReporte').value;
    const fechaIni  = document.getElementById('fechaInicio').value;
    const fechaFin  = document.getElementById('fechaFin').value;
    const btn       = document.getElementById('btnGenerar');

    // Loading
    btn.classList.add('loading');
    btn.querySelector('i').className = 'ri-loader-4-line';

    // Simular delay de carga
    setTimeout(function () {
        btn.classList.remove('loading');
        btn.querySelector('i').className = 'ri-bar-chart-2-line';

        reporteActual = { tipo, fechaIni, fechaFin, data: DATOS[tipo] };
        renderReporte(tipo, fechaIni, fechaFin);
    }, 600);
}

// ── Renderizar reporte ──────────────────────────────────────────────────────
function renderReporte(tipo, fechaIni, fechaFin) {
    const data = DATOS[tipo];
    const titulo = TITULOS[tipo];

    // Ocultar estado vacío, mostrar resultado
    document.getElementById('estadoVacio').style.display = 'none';
    const resultado = document.getElementById('reporteResultado');
    resultado.style.display = 'flex';

    // Resumen
    document.getElementById('resumenTitulo').textContent =
        `Resumen general de ${titulo}`;

    const grid = document.getElementById('resumenGrid');
    grid.innerHTML = data.resumen.map(item =>
        `<div class="resumen-item">${item.label}: <strong>${item.valor}</strong></div>`
    ).join('');

    // Título tabla
    document.getElementById('tablaTitulo').textContent =
        `Detalle de ${titulo}`;
    document.getElementById('tablaCount').textContent =
        `${data.filas.length} registros — ${formatDateDisplay(fechaIni)} al ${formatDateDisplay(fechaFin)}`;

    // Cabeceras
    const thead = document.getElementById('tablaHead');
    thead.innerHTML = `<tr>${data.columnas.map(c => `<th>${c}</th>`).join('')}</tr>`;

    // Filas
    const tbody = document.getElementById('tablaBody');
    tbody.innerHTML = data.filas.map(fila =>
        `<tr>${fila.map((celda, i) => {
            if (i === data.badgeCol) {
                const cls = BADGE_MAP[celda.toLowerCase()] || '';
                return `<td><span class="badge ${cls}">${celda}</span></td>`;
            }
            return `<td>${celda}</td>`;
        }).join('')}</tr>`
    ).join('');
}

// ── Exportar Excel ──────────────────────────────────────────────────────────
function exportarExcel() {
    if (!reporteActual) {
        alert('Primero genera un reporte para exportar.');
        return;
    }

    if (typeof XLSX === 'undefined') {
        alert('Cargando librería Excel, intenta en un momento...');
        return;
    }

    const { tipo, fechaIni, fechaFin, data } = reporteActual;
    const titulo = TITULOS[tipo];
    const wb = XLSX.utils.book_new();

    // Hoja resumen
    const resumenData = [
        [`Reporte de ${titulo}`],
        [`Período: ${formatDateDisplay(fechaIni)} — ${formatDateDisplay(fechaFin)}`],
        [`Generado: ${new Date().toLocaleString('es-MX')}`],
        [],
        ['RESUMEN'],
        ...data.resumen.map(item => [item.label, item.valor]),
    ];

    const wsResumen = XLSX.utils.aoa_to_sheet(resumenData);
    XLSX.utils.book_append_sheet(wb, wsResumen, 'Resumen');

    // Hoja detalle
    const detalleData = [
        data.columnas,
        ...data.filas,
    ];

    const wsDetalle = XLSX.utils.aoa_to_sheet(detalleData);

    // Ancho de columnas automático
    const colWidths = data.columnas.map((col, i) => {
        const maxLen = Math.max(
            col.length,
            ...data.filas.map(f => String(f[i] || '').length)
        );
        return { wch: maxLen + 4 };
    });
    wsDetalle['!cols'] = colWidths;

    XLSX.utils.book_append_sheet(wb, wsDetalle, `Detalle ${titulo}`);

    const fileName = `reporte_${tipo}_${fechaIni}_${fechaFin}.xlsx`;
    XLSX.writeFile(wb, fileName);
}

// ── Exportar PDF ────────────────────────────────────────────────────────────
function exportarPDF() {
    if (!reporteActual) {
        alert('Primero genera un reporte para exportar.');
        return;
    }

    if (typeof window.jspdf === 'undefined') {
        alert('Cargando librería PDF, intenta en un momento...');
        return;
    }

    const { jsPDF } = window.jspdf;
    const { tipo, fechaIni, fechaFin, data } = reporteActual;
    const titulo = TITULOS[tipo];

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // Colores del sistema
    const COLOR_PRIMARIO = [32, 168, 158];
    const COLOR_TEXTO    = [26, 26, 26];
    const COLOR_GRIS     = [108, 117, 125];

    // Encabezado
    doc.setFillColor(...COLOR_PRIMARIO);
    doc.rect(0, 0, 297, 22, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text(`Sistema Maxilofacial Texcoco — Reporte de ${titulo}`, 14, 14);

    // Subtítulo
    doc.setFillColor(248, 249, 250);
    doc.rect(0, 22, 297, 12, 'F');
    doc.setTextColor(...COLOR_GRIS);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text(`Período: ${formatDateDisplay(fechaIni)} — ${formatDateDisplay(fechaFin)}`, 14, 30);
    doc.text(`Generado: ${new Date().toLocaleString('es-MX')}`, 200, 30);

    // Resumen
    let y = 42;
    doc.setTextColor(...COLOR_TEXTO);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Resumen general', 14, y);
    y += 6;

    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    const itemsPorFila = 2;
    data.resumen.forEach((item, i) => {
        const col = i % itemsPorFila;
        const row = Math.floor(i / itemsPorFila);
        const x   = 14 + col * 130;
        const yy  = y + row * 6;
        doc.setTextColor(...COLOR_GRIS);
        doc.text(`${item.label}:`, x, yy);
        doc.setTextColor(...COLOR_TEXTO);
        doc.setFont('helvetica', 'bold');
        doc.text(item.valor, x + 55, yy);
        doc.setFont('helvetica', 'normal');
    });

    y += Math.ceil(data.resumen.length / itemsPorFila) * 6 + 8;

    // Tabla
    doc.autoTable({
        head: [data.columnas],
        body: data.filas,
        startY: y,
        theme: 'grid',
        headStyles: {
            fillColor: COLOR_PRIMARIO,
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8,
        },
        bodyStyles: {
            fontSize: 8,
            textColor: COLOR_TEXTO,
        },
        alternateRowStyles: {
            fillColor: [248, 252, 252],
        },
        styles: {
            cellPadding: 3,
            lineColor: [240, 240, 240],
        },
        margin: { left: 14, right: 14 },
    });

    // Pie de página
    const totalPaginas = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPaginas; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(...COLOR_GRIS);
        doc.text(
            `Página ${i} de ${totalPaginas}`,
            doc.internal.pageSize.getWidth() / 2,
            doc.internal.pageSize.getHeight() - 8,
            { align: 'center' }
        );
    }

    const fileName = `reporte_${tipo}_${fechaIni}_${fechaFin}.pdf`;
    doc.save(fileName);
}

// ── Cargar librerías dinámicamente ──────────────────────────────────────────
function cargarLibrerias() {
    // SheetJS para Excel
    const scriptXLSX = document.createElement('script');
    scriptXLSX.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
    document.head.appendChild(scriptXLSX);

    // jsPDF
    const scriptPDF = document.createElement('script');
    scriptPDF.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    document.head.appendChild(scriptPDF);

    // jsPDF-AutoTable (depende de jsPDF)
    scriptPDF.onload = function () {
        const scriptAutoTable = document.createElement('script');
        scriptAutoTable.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js';
        document.head.appendChild(scriptAutoTable);
    };
}