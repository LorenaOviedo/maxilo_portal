<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Pago.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Pagos';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina']     ?? 1));
$buscar       = trim($_GET['buscar']               ?? '');
$estatus      = trim($_GET['estatus']              ?? '');
$fechaDesde   = trim($_GET['fecha_desde']          ?? '');
$fechaHasta   = trim($_GET['fecha_hasta']          ?? '');
 
$db    = getDB();
$model = new Pago($db);
 
$filtros = [];
if ($buscar !== '')     $filtros['buscar']      = $buscar;
if ($estatus !== '')    $filtros['estatus']      = $estatus;
if ($fechaDesde !== '') $filtros['fecha_desde']  = $fechaDesde;
if ($fechaHasta !== '') $filtros['fecha_hasta']  = $fechaHasta;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$pagos        = $model->getAll($filtros, $paginaActual, $porPagina);
$resumen      = $model->getResumen();
$catalogos    = $model->getCatalogos();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <main class="main-content">
 
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Pagos</span>
                </div>
            </nav>
 
            <div class="page-header">
                <h1>Pagos</h1>
                <p class="page-description">Registro y control de pagos de citas</p>
            </div>
 
            <!-- ── Tarjetas de resumen ─────────────────────────────────── -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
                gap:14px; margin-bottom:24px;">
 
                <div style="background:#fff;border-radius:10px;padding:16px 20px;
                    box-shadow:0 1px 6px rgba(0,0,0,.07);border-left:4px solid #20a89e;">
                    <div style="font-size:26px;font-weight:700;color:#20a89e;">
                        <?php echo (int)($resumen['total_pagos'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Total pagos</div>
                </div>
 
                <div style="background:#fff;border-radius:10px;padding:16px 20px;
                    box-shadow:0 1px 6px rgba(0,0,0,.07);border-left:4px solid #28a745;">
                    <div style="font-size:26px;font-weight:700;color:#28a745;">
                        $<?php echo number_format((float)($resumen['total_recaudado'] ?? 0), 2); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Total recaudado</div>
                </div>
 
                <div style="background:#fff;border-radius:10px;padding:16px 20px;
                    box-shadow:0 1px 6px rgba(0,0,0,.07);border-left:4px solid #17a2b8;">
                    <div style="font-size:26px;font-weight:700;color:#17a2b8;">
                        $<?php echo number_format((float)($resumen['pagos_mes'] ?? 0), 2); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Pagos este mes</div>
                </div>
 
                <?php if ((int)($resumen['pendientes'] ?? 0) > 0): ?>
                <div style="background:#fff;border-radius:10px;padding:16px 20px;
                    box-shadow:0 1px 6px rgba(0,0,0,.07);border-left:4px solid #ffc107;">
                    <div style="font-size:26px;font-weight:700;color:#e65100;">
                        <?php echo (int)($resumen['pendientes'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Pendientes</div>
                </div>
                <?php endif; ?>
 
            </div>
 
            <!-- ── Búsqueda y nuevo pago ──────────────────────────────── -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input type="text" class="search-input" id="searchInput"
                        placeholder="Buscar por recibo, paciente..."
                        value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i> Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="pagoController.abrir()">
                    <i class="ri-add-line"></i> Registrar pago
                </button>
            </div>
 
            <!-- ── Filtros ─────────────────────────────────────────────── -->
            <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;
                margin-bottom:16px;align-items:flex-end;">
                <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>">
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">
                        Estatus
                    </label>
                    <select name="estatus" class="form-select" style="min-width:140px;">
                        <option value="">Todos</option>
                        <option value="Pagado"    <?php echo $estatus === 'Pagado'    ? 'selected' : ''; ?>>Pagado</option>
                        <option value="Pendiente" <?php echo $estatus === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    </select>
                </div>
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">Desde</label>
                    <input type="date" name="fecha_desde" class="form-input"
                        value="<?php echo htmlspecialchars($fechaDesde); ?>"
                        style="min-width:140px;">
                </div>
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-input"
                        value="<?php echo htmlspecialchars($fechaHasta); ?>"
                        style="min-width:140px;">
                </div>
 
                <button type="submit" class="btn-search">
                    <i class="ri-filter-line"></i> Filtrar
                </button>
                <?php if ($estatus || $fechaDesde || $fechaHasta): ?>
                <a href="?" class="btn-search" style="background:#f1f3f5;color:#495057;">
                    <i class="ri-close-line"></i> Limpiar
                </a>
                <?php endif; ?>
            </form>
 
            <!-- ── Tabla ───────────────────────────────────────────────── -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-sort="numero_recibo">RECIBO</th>
                            <th data-sort="fecha_pago">FECHA</th>
                            <th data-sort="nombre_paciente">PACIENTE</th>
                            <th data-sort="nombre_especialista">ESPECIALISTA</th>
                            <th data-sort="metodo_pago">MÉTODO</th>
                            <th class="text-right" data-sort="monto_total">TOTAL</th>
                            <th class="text-right" data-sort="monto_neto">NETO</th>
                            <th class="text-center" data-sort="estatus">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pagos)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-secure-payment-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin pagos registrados</h3>
                                    <p class="empty-state-text">Registra el primer pago con el botón "Registrar pago"</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p): ?>
                            <tr>
                                <td data-label="Recibo">
                                    <code style="font-size:12px;background:#f1f3f5;
                                        padding:2px 6px;border-radius:4px;">
                                        <?php echo htmlspecialchars($p['numero_recibo']); ?>
                                    </code>
                                </td>
 
                                <td class="text-center" data-label="Fecha">
                                    <?php echo date('d/m/Y', strtotime($p['fecha_pago'])); ?>
                                </td>
 
                                <td data-label="Paciente">
                                    <?php echo htmlspecialchars($p['nombre_paciente']); ?>
                                    <div style="font-size:11px;color:#6c757d;">
                                        <?php echo date('d/m/Y', strtotime($p['fecha_cita'])); ?>
                                        · <?php echo substr($p['hora_inicio'], 0, 5); ?>
                                    </div>
                                </td>
 
                                <td data-label="Especialista">
                                    <?php echo htmlspecialchars($p['nombre_especialista']); ?>
                                </td>
 
                                <td data-label="Método">
                                    <?php echo htmlspecialchars($p['metodo_pago']); ?>
                                    <?php if ($p['referencia_pago']): ?>
                                        <div style="font-size:11px;color:#6c757d;">
                                            Ref: <?php echo htmlspecialchars($p['referencia_pago']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
 
                                <td class="text-right" data-label="Total">
                                    $<?php echo number_format((float)$p['monto_total'], 2); ?>
                                </td>
 
                                <td class="text-right" data-label="Neto">
                                    <strong>$<?php echo number_format((float)$p['monto_neto'], 2); ?></strong>
                                    <?php if ((float)$p['monto_neto'] < (float)$p['monto_total']): ?>
                                        <div style="font-size:11px;color:#28a745;">
                                            <?php
                                                $desc = (float)$p['monto_total'] - (float)$p['monto_neto'];
                                                echo '-$' . number_format($desc, 2) . ' desc.';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
 
                                <td class="text-center" data-label="Estatus">
                                    <span class="badge <?php echo $p['estatus'] === 'Pagado' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo htmlspecialchars($p['estatus']); ?>
                                    </span>
                                </td>
 
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver detalle"
                                            onclick="pagoController.ver(<?php echo $p['id_pago']; ?>)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- ── Paginación ──────────────────────────────────────────── -->
            <?php if ($total > 0): ?>
            <?php
                $params  = http_build_query(array_filter([
                    'buscar'      => $buscar,
                    'estatus'     => $estatus,
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta,
                ]));
                $urlBase = view_url('pagos.php') . '?' . $params . '&pagina=';
                $inicio  = (($paginaActual - 1) * $porPagina) + 1;
                $fin     = min($paginaActual * $porPagina, $total);
            ?>
            <div class="pagination">
                <a href="<?php echo $urlBase . ($paginaActual - 1); ?>"
                   class="pagination-btn <?php echo $paginaActual <= 1 ? 'disabled' : ''; ?>"
                   <?php echo $paginaActual <= 1 ? 'onclick="return false;"' : ''; ?>>
                    <i class="ri-arrow-left-line"></i> Anterior
                </a>
                <span class="pagination-info">
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?>
                    de <?php echo $total; ?> pago(s)
                    &nbsp;·&nbsp; Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?>
                </span>
                <a href="<?php echo $urlBase . ($paginaActual + 1); ?>"
                   class="pagination-btn <?php echo $paginaActual >= $totalPaginas ? 'disabled' : ''; ?>"
                   <?php echo $paginaActual >= $totalPaginas ? 'onclick="return false;"' : ''; ?>>
                    Siguiente <i class="ri-arrow-right-line"></i>
                </a>
            </div>
            <?php endif; ?>
 
        </main>
 
<script>
var API_URL        = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_PAGO = <?php echo json_encode($catalogos); ?>;
</script>
 
<?php include '../includes/modal_pago.php'; ?>
<script src="<?php echo asset('js/pagos.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>