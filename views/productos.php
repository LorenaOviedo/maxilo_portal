<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Producto.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Productos';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar       = trim($_GET['buscar'] ?? '');
 
$db    = getDB();
$model = new Producto($db);
 
$filtros = [];
if ($buscar !== '') $filtros['buscar'] = $buscar;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$productos    = $model->getAll($filtros, $paginaActual, $porPagina);
$catalogos    = $model->getCatalogos();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <!-- Contenido principal -->
        <main class="main-content">
 
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('catalogos_inventario.php'); ?>">Inventarios</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Productos</span>
                </div>
            </nav>
 
            <div class="page-header">
                <h1>Productos</h1>
                <p class="page-description">Catálogo de productos e insumos</p>
            </div>
 
            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por nombre, código, marca..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="productoController.abrir()">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>
 
            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-sort="codigo_producto">CÓDIGO</th>
                            <th data-sort="nombre_producto">NOMBRE</th>
                            <th data-sort="nombre_tipo_producto">TIPO</th>
                            <th data-sort="marca">MARCA</th>
                            <th class="text-right" data-sort="precio_compra">PRECIO COMPRA</th>
                            <th class="text-center">CADUCIDAD</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-medicine-bottle-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay productos registrados</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer producto</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $p): ?>
                            <?php
                                $hoy = date('Y-m-d');
                                $porCaducar = $p['fecha_caducidad'] && $p['fecha_caducidad'] <= date('Y-m-d', strtotime('+30 days'));
                                $caducado   = $p['fecha_caducidad'] && $p['fecha_caducidad'] < $hoy;
                            ?>
                            <tr>
                                <td data-label="Código" data-col="codigo_producto">
                                        <?php echo htmlspecialchars($p['codigo_producto']); ?>
                                </td>
 
                                <td data-label="Nombre" data-col="nombre_producto">
                                    <strong><?php echo htmlspecialchars($p['nombre_producto']); ?></strong>
                                    <?php if ($p['descripcion']): ?>
                                        <div style="font-size:12px; color:#6c757d; margin-top:2px;">
                                            <?php echo htmlspecialchars(mb_substr($p['descripcion'], 0, 60)) . (mb_strlen($p['descripcion']) > 60 ? '…' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
 
                                <td data-label="Tipo">
                                    <span class="badge badge-info" style="font-size:11px;">
                                        <?php echo htmlspecialchars($p['nombre_tipo_producto']); ?>
                                    </span>
                                </td>
 
                                <td data-label="Marca">
                                    <?php echo htmlspecialchars($p['marca'] ?? '—'); ?>
                                </td>
 
                                <td class="text-right" data-label="Precio compra">
                                    $<?php echo number_format((float)$p['precio_compra'], 2); ?>
                                </td>
 
                                <td class="text-center" data-label="Caducidad">
                                    <?php if (!$p['fecha_caducidad']): ?>
                                        <span style="color:#adb5bd;">—</span>
                                    <?php elseif ($caducado): ?>
                                        <span class="badge badge-danger">
                                            <?php echo date('d/m/Y', strtotime($p['fecha_caducidad'])); ?>
                                        </span>
                                    <?php elseif ($porCaducar): ?>
                                        <span class="badge badge-warning">
                                            <?php echo date('d/m/Y', strtotime($p['fecha_caducidad'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo date('d/m/Y', strtotime($p['fecha_caducidad'])); ?>
                                    <?php endif; ?>
                                </td>
 
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <!-- Ver -->
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver producto"
                                            onclick="productoController.abrir(<?php echo $p['id_producto']; ?>, true)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <!-- Editar -->
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar producto"
                                            onclick="productoController.abrir(<?php echo $p['id_producto']; ?>, false)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <!-- Eliminar -->
                                        <button type="button" class="btn-action btn-delete"
                                            title="Eliminar producto"
                                            onclick="productoController.eliminar(
                                                <?php echo $p['id_producto']; ?>,
                                                '<?php echo htmlspecialchars($p['nombre_producto'], ENT_QUOTES); ?>'
                                            )">
                                            <i class="ri-delete-bin-6-line"></i>
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
                $urlBase = view_url('productos.php') . '?buscar=' . urlencode($buscar) . '&pagina=';
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
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> producto(s)
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
var CATALOGOS_PROD  = <?php echo json_encode($catalogos); ?>;
</script>
 
<?php include '../includes/modal_producto.php'; ?>
<script src="<?php echo asset('js/productos.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>