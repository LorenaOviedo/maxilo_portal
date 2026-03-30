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

require_once __DIR__ . '/../models/Especialista.php';

$db  = getDB();
$model = new Especialista($db);

// Paginación y búsqueda
$buscar    = trim($_GET['buscar']    ?? '');
$pagina    = max(1, (int) ($_GET['pagina']    ?? 1));
$porPagina = 10;

$filtros = [];
if ($buscar !== '') $filtros['buscar'] = $buscar;

$total      = $model->contarTotal($filtros);
$totalPags  = max(1, (int) ceil($total / $porPagina));
$pagina     = min($pagina, $totalPags);
$inicio     = $total > 0 ? (($pagina - 1) * $porPagina) + 1 : 0;
$fin        = min($pagina * $porPagina, $total);
$especialistas = $model->getAll($filtros, $pagina, $porPagina);

// Catálogos para el modal
$catalogos = $model->getCatalogos();

// CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Especialistas';
$page_css   = ['catalogos-tabla.css'];
$page_js    = ['especialistas.js'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Variables globales -->
<script>
    var API_URL  = '<?php echo ajax_url('Api.php'); ?>';
    var CATALOGOS_ESP = <?php echo json_encode($catalogos); ?>;
</script>

<!-- Modal especialista -->
<?php include '../includes/modal_especialista.php'; ?>

<!-- Contenido principal -->
<main class="main-content">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <div class="breadcrumb-item">
            <a href="<?php echo view_url('especialistas.php'); ?>">Especialistas</a>
        </div>
        <span class="breadcrumb-separator">▶</span>
        <div class="breadcrumb-item">
            <span class="breadcrumb-current">Especialistas</span>
        </div>
    </nav>

    <!-- Encabezado -->
    <div class="page-header">
        <h1>Especialistas</h1>
        <p class="page-description">Administre la información del personal especializado.</p>
    </div>

    <!-- Búsqueda y agregar -->
    <div class="search-actions-bar">
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput"
                placeholder="Buscar por nombre, apellido..."
                value="<?php echo htmlspecialchars($buscar); ?>">
        </div>
        <button type="button" class="btn-search">
            <i class="ri-search-line"></i> Buscar
        </button>
        <button type="button" class="btn-add-new"
            onclick="especialistaController.abrir()">
            <i class="ri-add-line"></i> Agregar nuevo
        </button>
    </div>

    <!-- Tabla -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-id">NO.<br>ESPECIALISTA</th>
                    <th class="col-name">NOMBRE<br>COMPLETO</th>
                    <th>ESPECIALIDAD(ES)</th>
                    <th class="col-tel">TELÉFONO</th>
                    <th class="col-date">FECHA DE<br>CONTRATACIÓN</th>
                    <th class="col-status">ESTATUS</th>
                    <th class="col-actions">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($especialistas)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="ri-folder-open-line"></i>
                            </div>
                            <h3 class="empty-state-title">No hay especialistas registrados</h3>
                            <p class="empty-state-text">Comienza agregando tu primer especialista</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($especialistas as $e): ?>
                    <tr>
                        <td class="col-id text-center">
                            E-<?php echo str_pad($e['id_especialista'], 3, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td class="col-name">
                            <?php echo htmlspecialchars($e['nombre_completo']); ?>
                        </td>
                        <td>
                            <?php if (!empty($e['especialidades'])): ?>
                                <?php foreach (explode(', ', $e['especialidades']) as $esp): ?>
                                    <span class="badge badge-info" style="margin:1px 2px; font-size:11px;">
                                        <?php echo htmlspecialchars($esp); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:#adb5bd;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-tel">
                            <?php echo htmlspecialchars($e['telefono'] ?? '—'); ?>
                        </td>
                        <td class="col-date">
                            <?php
                                echo $e['fecha_contratacion']
                                    ? date('d/m/Y', strtotime($e['fecha_contratacion']))
                                    : '—';
                            ?>
                        </td>
                        <td class="col-status text-center">
                            <?php if ($e['id_estatus'] == 1): ?>
                                <span class="badge badge-active">ACTIVO</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">INACTIVO</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <!-- Ver / Editar -->
                                <button type="button" class="btn-action btn-view"
                                    title="Ver / Editar especialista"
                                    onclick="especialistaController.abrir(<?php echo $e['id_especialista']; ?>)">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <!-- Activar / Desactivar -->
                                <?php if ($e['id_estatus'] == 1): ?>
                                <button type="button" class="btn-action btn-delete"
                                    title="Desactivar especialista"
                                    onclick="especialistaController.cambiarEstatus(<?php echo $e['id_especialista']; ?>, 2)">
                                    <i class="ri-toggle-line"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-action btn-edit"
                                    title="Activar especialista"
                                    onclick="especialistaController.cambiarEstatus(<?php echo $e['id_especialista']; ?>, 1)">
                                    <i class="ri-toggle-fill"></i>
                                </button>
                                <?php endif; ?>
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
    <div class="pagination">
        <button class="pagination-btn"
            <?php echo $pagina <= 1 ? 'disabled' : ''; ?>
            onclick="window.location.href='?pagina=<?php echo $pagina - 1; ?>&buscar=<?php echo urlencode($buscar); ?>'">
            <i class="ri-arrow-left-line"></i> Anterior
        </button>
        <span class="pagination-info">
            Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> especialistas
            &nbsp;·&nbsp; Página <?php echo $pagina; ?> de <?php echo $totalPags; ?>
        </span>
        <button class="pagination-btn"
            <?php echo $pagina >= $totalPags ? 'disabled' : ''; ?>
            onclick="window.location.href='?pagina=<?php echo $pagina + 1; ?>&buscar=<?php echo urlencode($buscar); ?>'">
            Siguiente <i class="ri-arrow-right-line"></i>
        </button>
    </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>
