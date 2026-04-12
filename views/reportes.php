<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
// Verificar permiso específico al módulo
verificarPermiso('reportes');
 
$page_title = 'Reportes';
$page_css   = ['reportes.css'];
$page_js    = [];
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
<main class="main-content">
 
    <nav class="breadcrumb">
        <div class="breadcrumb-item">
            <span class="breadcrumb-current">Reportes</span>
        </div>
    </nav>
 
    <div class="page-header">
        <h1>Reportes</h1>
        <p class="page-description">Genera y exporta reportes del sistema</p>
    </div>
 
    <!-- Panel de filtros -->
    <div class="report-filters-card">
        <div class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Tipo de reporte</label>
                <div class="select-wrapper">
                    <select id="tipoReporte" class="filter-select">
                        <option value="pacientes">Pacientes</option>
                        <option value="citas">Citas</option>
                        <option value="pagos">Pagos</option>
                        <option value="inventario">Inventario</option>
                        <option value="facturas">Facturas</option>
                    </select>
                    <i class="ri-arrow-down-s-line select-icon"></i>
                </div>
            </div>
 
            <div class="filter-group">
                <label class="filter-label">Período</label>
                <div class="select-wrapper">
                    <select id="periodo" class="filter-select">
                        <option value="este_mes">Este mes</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="este_anio">Este año</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                    <i class="ri-arrow-down-s-line select-icon"></i>
                </div>
            </div>
 
            <div class="filter-group">
                <label class="filter-label">Rango de fechas</label>
                <div class="date-range">
                    <div class="date-input-wrapper">
                        <input type="date" id="fechaInicio" class="filter-date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <span class="date-separator">—</span>
                    <div class="date-input-wrapper">
                        <input type="date" id="fechaFin" class="filter-date" value="<?php echo date('Y-m-t'); ?>">
                    </div>
                </div>
            </div>
        </div>
 
        <div class="filters-actions">
            <button class="btn-generar" id="btnGenerar">
                <i class="ri-bar-chart-line"></i>
                Generar reporte
            </button>
            <button class="btn-exportar btn-excel" id="btnExcel">
                <i class="ri-file-excel-line"></i>
                Exportar Excel
            </button>
            <button class="btn-exportar btn-pdf" id="btnPdf">
                <i class="ri-file-pdf-2-line"></i>
                Exportar PDF
            </button>
        </div>
    </div>
 
    <!-- Resultado del reporte -->
    <div id="reporteResultado" class="reporte-resultado" style="display:none;">
        <div class="resumen-card" id="resumenCard">
            <h3 class="resumen-titulo" id="resumenTitulo"></h3>
            <div class="resumen-grid" id="resumenGrid"></div>
        </div>
        <div class="tabla-card">
            <div class="tabla-header">
                <span class="tabla-titulo" id="tablaTitulo"></span>
                <span class="tabla-count" id="tablaCount"></span>
            </div>
            <div class="table-responsive">
                <table class="data-table reporte-table" id="tablaReporte">
                    <thead id="tablaHead"></thead>
                    <tbody id="tablaBody"></tbody>
                </table>
            </div>
        </div>
    </div>
 
    <!-- Estado vacío inicial -->
    <div id="estadoVacio" class="estado-vacio">
        <div class="estado-vacio-icon">
            <i class="ri-file-chart-line"></i>
        </div>
        <h3>Selecciona un reporte</h3>
        <p>Elige el tipo de reporte, el período y presiona <strong>Generar reporte</strong></p>
    </div>
 
</main>
 
<script>
var API_URL = '<?php echo ajax_url('Api.php'); ?>';
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="<?php echo asset('js/reportes.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>