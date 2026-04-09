<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/OrdenCompra.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Órdenes de Compra';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar       = trim($_GET['buscar'] ?? '');
 
$db    = getDB();
$model = new OrdenCompra($db);
 
$filtros = [];
if ($buscar !== '') $filtros['buscar'] = $buscar;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$compras      = $model->getAll($filtros, $paginaActual, $porPagina);
$catalogos    = $model->getCatalogos();
 
// Badges de estatus
function badgeEstatus(string $estatus): string {
    $map = [
        'Pendiente'  => 'badge-warning',
        'Enviada'    => 'badge-info',
        'Recibida'   => 'badge-success',
        'Cancelada'  => 'badge-danger',
    ];
    $class = $map[$estatus] ?? 'badge-secondary';
    return "<span class=\"badge {$class}\">" . htmlspecialchars($estatus) . "</span>";
}
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <!-- Contenido principal -->
        <main class="main-content">
 
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('compras_proveedores.php'); ?>">Compras y Proveedores</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Órdenes de Compra</span>
                </div>
            </nav>
 
            <div class="page-header">
                <h1>Órdenes de Compra</h1>
                <p class="page-description">Gestión de órdenes de compra a proveedores</p>
            </div>
 
            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por folio, proveedor..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="compraController.abrir()">
                    <i class="ri-add-line"></i>
                    Nueva orden
                </button>
            </div>
 
            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-sort="folio_compra">FOLIO</th>
                            <th data-sort="razon_social">PROVEEDOR</th>
                            <th data-sort="tipo_compra">TIPO</th>
                            <th data-sort="fecha_emision">FECHA DE EMISIÓN</th>
                            <th data-sort="fecha_entrega_estimada">ENTREGA ESTIMADA</th>
                            <th class="text-right">TOTAL</th>
                            <th data-sort="estatus_orden_compra">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compras)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-shopping-cart-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay órdenes de compra registradas</h3>
                                    <p class="empty-state-text">Comienza creando tu primera orden de compra</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $c): ?>
                            <tr>
                                <td data-label="Folio" data-col="folio_compra">
                                    <strong><?php echo htmlspecialchars($c['folio_compra']); ?></strong>
                                </td>
 
                                <td data-label="Proveedor" data-col="razon_social">
                                    <?php echo htmlspecialchars($c['proveedor']); ?>
                                </td>
 
                                <td data-label="Tipo">
                                    <?php echo htmlspecialchars($c['tipo_compra']); ?>
                                </td>
 
                                <td class="text-center" data-label="Fecha emisión" data-col="fecha_emision">
                                    <?php echo $c['fecha_emision']
                                        ? date('d/m/Y', strtotime($c['fecha_emision'])) : '—'; ?>
                                </td>
 
                                <td class="text-center" data-label="Entrega estimada">
                                    <?php echo $c['fecha_entrega_estimada']
                                        ? date('d/m/Y', strtotime($c['fecha_entrega_estimada'])) : '—'; ?>
                                </td>
 
                                <td class="text-right" data-label="Total">
                                    <strong>
                                        <?php echo $c['moneda'] . ' $' . number_format($c['total'], 2); ?>
                                    </strong>
                                </td>
 
                                <td class="text-center" data-label="Estatus">
                                    <?php echo badgeEstatus($c['estatus_orden_compra']); ?>
                                </td>
 
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver detalle"
                                            onclick="compraController.abrir(<?php echo $c['id_compra']; ?>, true)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar"
                                            onclick="compraController.abrir(<?php echo $c['id_compra']; ?>, false)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button type="button" class="btn-action btn-delete"
                                            title="Cancelar orden"
                                            onclick="compraController.cancelar(
                                                <?php echo $c['id_compra']; ?>,
                                                '<?php echo htmlspecialchars($c['folio_compra'], ENT_QUOTES); ?>'
                                            )">
                                            <i class="ri-forbid-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- Paginación -->
            <?php if ($total > 0): ?>
            <?php
                $urlBase = view_url('compras.php') . '?buscar=' . urlencode($buscar) . '&pagina=';
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
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> orden(es)
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
var API_URL         = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_OC    = <?php echo json_encode($catalogos); ?>;
</script>
 
<?php include '../includes/modal_compra.php'; ?>
<script src="<?php echo asset('js/compras.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>
