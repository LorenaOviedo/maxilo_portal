<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/MovimientoInventario.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Movimientos de Inventario';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 15;
$paginaActual = max(1, (int) ($_GET['pagina']             ?? 1));
$buscar       = trim($_GET['buscar']                       ?? '');
$tipoFiltro   = (int) ($_GET['id_tipo_movimiento']         ?? 0);
$fechaDesde   = trim($_GET['fecha_desde']                  ?? '');
$fechaHasta   = trim($_GET['fecha_hasta']                  ?? '');
 
$db    = getDB();
$model = new MovimientoInventario($db);
 
$filtros = [];
if ($buscar !== '')    $filtros['buscar']             = $buscar;
if ($tipoFiltro > 0)  $filtros['id_tipo_movimiento'] = $tipoFiltro;
if ($fechaDesde !== '') $filtros['fecha_desde']       = $fechaDesde;
if ($fechaHasta !== '') $filtros['fecha_hasta']       = $fechaHasta;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$movimientos  = $model->getAll($filtros, $paginaActual, $porPagina);
$catalogos    = $model->getCatalogos();
 
// Badge por tipo de movimiento
function badgeTipo(string $tipo, int $id): string {
    $map = [
        1 => ['badge-success', 'ri-arrow-down-circle-line'],
        2 => ['badge-danger',  'ri-arrow-up-circle-line'],
        3 => ['badge-info',    'ri-settings-3-line'],
    ];
    [$cls, $icon] = $map[$id] ?? ['badge-secondary', 'ri-swap-box-line'];
    return "<span class=\"badge {$cls}\"><i class=\"{$icon}\"></i> " . htmlspecialchars($tipo) . "</span>";
}
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <main class="main-content">
 
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('catalogos_inventario.php'); ?>">Inventarios</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Movimientos</span>
                </div>
            </nav>
 
            <div class="page-header">
                <h1>Movimientos de Inventario</h1>
                <p class="page-description">Historial de entradas, salidas y ajustes</p>
            </div>
 
            <!-- ── Búsqueda y nuevo movimiento ────────────────────────── -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por código, producto, lote..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i> Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="movimientoController.abrir()">
                    <i class="ri-add-line"></i> Nuevo movimiento
                </button>
            </div>
 
            <!-- ── Filtros ─────────────────────────────────────────────── -->
            <form method="GET" action=""
                style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:flex-end;">
                <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>">
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">
                        Tipo de movimiento
                    </label>
                    <select name="id_tipo_movimiento" class="form-select" style="min-width:160px;">
                        <option value="0">Todos</option>
                        <?php foreach ($catalogos['tiposMovimiento'] as $t): ?>
                        <option value="<?php echo $t['id_tipo_movimiento']; ?>"
                            <?php echo $tipoFiltro == $t['id_tipo_movimiento'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['tipo_movimiento']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">
                        Desde
                    </label>
                    <input type="date" name="fecha_desde" class="form-input"
                        value="<?php echo htmlspecialchars($fechaDesde); ?>"
                        style="min-width:140px;">
                </div>
 
                <div>
                    <label style="font-size:12px;color:#6c757d;display:block;margin-bottom:4px;">
                        Hasta
                    </label>
                    <input type="date" name="fecha_hasta" class="form-input"
                        value="<?php echo htmlspecialchars($fechaHasta); ?>"
                        style="min-width:140px;">
                </div>
 
                <button type="submit" class="btn-search">
                    <i class="ri-filter-line"></i> Filtrar
                </button>
                <?php if ($tipoFiltro || $fechaDesde || $fechaHasta): ?>
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
                            <th class="text-center" data-sort="fecha_movimiento">FECHA</th>
                            <th data-sort="tipo_movimiento">TIPO</th>
                            <th data-sort="codigo_producto">CÓDIGO</th>
                            <th data-sort="nombre_producto">PRODUCTO</th>
                            <th data-sort="lote">LOTE</th>
                            <th class="text-center" data-sort="cantidad">CANTIDAD</th>
                            <th class="text-center" data-sort="stock_actual">STOCK ACTUAL</th>
                            <th data-sort="usuario">USUARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-exchange-box-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin movimientos registrados</h3>
                                    <p class="empty-state-text">
                                        Registra el primer movimiento con el botón "Nuevo movimiento"
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td class="text-center" data-label="Fecha">
                                    <?php echo date('d/m/Y', strtotime($m['fecha_movimiento'])); ?>
                                </td>
 
                                <td data-label="Tipo">
                                    <?php echo badgeTipo($m['tipo_movimiento'], (int)$m['id_tipo_movimiento']); ?>
                                </td>
 
                                <td data-label="Código">
                                        <?php echo htmlspecialchars($m['codigo_producto']); ?>
                                </td>
 
                                <td data-label="Producto">
                                    <strong><?php echo htmlspecialchars($m['nombre_producto']); ?></strong>
                                    <?php if ($m['marca']): ?>
                                        <div style="font-size:12px;color:#6c757d;">
                                            <?php echo htmlspecialchars($m['marca']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
 
                                <td data-label="Lote">
                                    <?php echo htmlspecialchars($m['lote'] ?? '—'); ?>
                                </td>
 
                                <td class="text-center" data-label="Cantidad">
                                    <?php
                                        $tipo = (int)$m['id_tipo_movimiento'];
                                        $cls  = $tipo === 1 ? 'color:#2e7d32;font-weight:700;'
                                              : ($tipo === 2 ? 'color:#c62828;font-weight:700;'
                                              : 'color:#1565c0;font-weight:700;');
                                        $pre  = $tipo === 1 ? '+' : ($tipo === 2 ? '−' : '=');
                                    ?>
                                    <span style="<?php echo $cls; ?>">
                                        <?php echo $pre . (int)$m['cantidad']; ?>
                                    </span>
                                </td>
 
                                <td class="text-center" data-label="Stock actual">
                                    <span class="badge badge-info">
                                        <?php echo (int)$m['stock_actual']; ?>
                                    </span>
                                </td>
 
                                <td data-label="Usuario">
                                    <?php echo htmlspecialchars($m['usuario']); ?>
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
                    'buscar'             => $buscar,
                    'id_tipo_movimiento' => $tipoFiltro ?: '',
                    'fecha_desde'        => $fechaDesde,
                    'fecha_hasta'        => $fechaHasta,
                ]));
                $urlBase = view_url('movimientos.php') . '?' . $params . '&pagina=';
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
                    de <?php echo $total; ?> movimiento(s)
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
var API_URL          = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_MOV    = <?php echo json_encode($catalogos); ?>;
var SESSION_USUARIO  = <?php echo (int)$_SESSION['usuario_id']; ?>;
</script>
 
<?php include '../includes/modal_movimiento.php'; ?>
<script src="<?php echo asset('js/movimientos.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>