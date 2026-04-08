<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Proveedor.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
// Configuración de la página
$page_title = 'Proveedores';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar       = trim($_GET['buscar'] ?? '');
 
// Datos desde BD
$db    = getDB();
$model = new Proveedor($db);
 
$filtros = [];
if ($buscar !== '') $filtros['buscar'] = $buscar;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$proveedores  = $model->getAll($filtros, $paginaActual, $porPagina);
$catalogos    = $model->getCatalogos();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <!-- Contenido principal -->
        <main class="main-content">
 
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('compras_proveedores.php'); ?>">Compras y Proveedores</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Proveedores</span>
                </div>
            </nav>
 
            <!-- Header -->
            <div class="page-header">
                <h1>Proveedores</h1>
                <p class="page-description">Registro y control de proveedores</p>
            </div>
 
            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por RFC, razón social..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="proveedorController.abrir()">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>
 
            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id"     data-sort="id_proveedor">NO.</th>
                            <th class="col-rfc"    data-sort="rfc">RFC</th>
                            <th class="col-name"   data-sort="razon_social">RAZÓN SOCIAL</th>
                            <th                    data-sort="tipo_persona">TIPO<br>PERSONA</th>
                            <th                    data-sort="tipo_producto_proveedor">TIPO<br>PRODUCTO/SERVICIO</th>
                            <th class="col-tel">TELÉFONO</th>
                            <th class="col-email">CORREO</th>
                            <th class="col-status" data-sort="estatus">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-folder-open-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay proveedores registrados</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer proveedor</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($proveedores as $p): ?>
                            <?php $activo = (int) $p['id_estatus'] === 1; ?>
                            <tr>
                                <td class="col-id text-center"
                                    data-label="No."
                                    data-col="id_proveedor">
                                    <span style="font-weight:700;">
                                        <?php echo $p['id_proveedor']; ?>
                                    </span>
                                </td>
 
                                <td class="col-rfc"
                                    data-label="RFC"
                                    data-col="rfc">
                                    <?php echo htmlspecialchars($p['rfc']); ?>
                                </td>
 
                                <td class="col-name"
                                    data-label="Razón social"
                                    data-col="razon_social">
                                    <?php echo htmlspecialchars($p['razon_social']); ?>
                                </td>
 
                                <td data-label="Tipo persona">
                                    <span class="badge badge-info" style="font-size:11px;">
                                        <?php echo htmlspecialchars($p['tipo_persona']); ?>
                                    </span>
                                </td>
 
                                <td data-label="Tipo producto/servicio">
                                    <?php echo htmlspecialchars($p['tipo_producto_proveedor'] ?? '—'); ?>
                                </td>
 
                                <td class="col-tel" data-label="Teléfono">
                                    <?php echo htmlspecialchars($p['telefono'] ?? '—'); ?>
                                </td>
 
                                <td class="col-email" data-label="Correo">
                                    <span class="text-truncate"
                                        title="<?php echo htmlspecialchars($p['email'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($p['email'] ?? '—'); ?>
                                    </span>
                                </td>
 
                                <td class="col-status text-center" data-label="Estatus">
                                    <span class="badge <?php echo $activo ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $activo ? 'ACTIVO' : 'INACTIVO'; ?>
                                    </span>
                                </td>
 
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <!-- Ver -->
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver proveedor"
                                            onclick="proveedorController.abrir(<?php echo $p['id_proveedor']; ?>, true)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <!-- Editar -->
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar proveedor"
                                            onclick="proveedorController.abrir(<?php echo $p['id_proveedor']; ?>, false)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <!-- Activar / Desactivar -->
                                        <button type="button"
                                            class="btn-action <?php echo $activo ? 'btn-delete' : 'btn-activate'; ?>"
                                            title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>"
                                            onclick="proveedorController.cambiarEstatus(
                                                <?php echo $p['id_proveedor']; ?>,
                                                <?php echo $activo ? 2 : 1; ?>,
                                                '<?php echo htmlspecialchars($p['razon_social'], ENT_QUOTES); ?>'
                                            )">
                                            <i class="ri-<?php echo $activo ? 'forbid' : 'check'; ?>-line"></i>
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
                $urlBase = view_url('proveedores.php') . '?buscar=' . urlencode($buscar) . '&pagina=';
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
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> proveedor(es)
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
 
<?php
$catalogosJson = json_encode($catalogos);
?>
 
<!-- Variables globales -->
<script>
var API_URL            = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_PROV     = <?php echo $catalogosJson; ?>;
</script>
 
<!-- Modal proveedor -->
<?php include '../includes/modal_proveedor.php'; ?>
 
<!-- JS específico del módulo -->
<script src="<?php echo asset('js/proveedores.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>