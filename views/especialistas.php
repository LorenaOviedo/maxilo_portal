<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Especialista.php';

session_start();

$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

// Verificar permiso específico al módulo
verificarPermiso('especialistas');

// Configuración de la página
$page_title = 'Especialistas';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];

// Paginación
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar       = trim($_GET['buscar'] ?? '');

// Obtener datos de la BD
$db    = getDB();
$model = new Especialista($db);

$filtros = [];
if ($buscar !== '') $filtros['buscar'] = $buscar;

$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$especialistas = $model->getAll($filtros, $paginaActual, $porPagina);
$catalogos    = $model->getCatalogos();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

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

            <!-- Header -->
            <div class="page-header">
                <h1>Especialistas</h1>
                <p class="page-description">Registro y control del personal especializado</p>
            </div>

            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por nombre, apellido..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new"
                    onclick="especialistaController.abrir()">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id" data-sort="id_especialista">NO. ESPECIALISTA</th>
                            <th class="col-name" data-sort="apellido_paterno">NOMBRE COMPLETO</th>
                            <th data-sort="especialidades">ESPECIALIDAD(ES)</th>
                            <th class="col-tel">TELÉFONO</th>
                            <th class="col-date" data-sort="fecha_contratacion">FECHA DE CONTRATACIÓN</th>
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
                            <?php $activo = (int) $e['id_estatus'] === 1; ?>
                            <tr>
                                <td class="col-id text-center"
                                    data-label="No. Especialista"
                                    data-col="id_especialista">
                                    <span style="font-weight:700;">
                                        <?php echo $e['id_especialista']; ?>
                                    </span>
                                </td>

                                <td class="col-name"
                                    data-label="Nombre"
                                    data-col="apellido_paterno">
                                    <?php echo htmlspecialchars($e['nombre_completo']); ?>
                                </td>

                                <td data-label="Especialidad(es)">
                                    <?php if (!empty($e['especialidades'])): ?>
                                        <?php foreach (explode(', ', $e['especialidades']) as $esp): ?>
                                            <span class="badge badge-info"
                                                style="margin:1px 2px; font-size:11px; white-space:nowrap;">
                                                <?php echo htmlspecialchars(trim($esp)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color:#adb5bd;">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="col-tel" data-label="Teléfono">
                                    <?php echo htmlspecialchars($e['telefono'] ?? '—'); ?>
                                </td>

                                <td class="col-date text-center"
                                    data-label="Fecha contratación"
                                    data-col="fecha_contratacion">
                                    <?php echo $e['fecha_contratacion']
                                        ? date('d/m/Y', strtotime($e['fecha_contratacion']))
                                        : '—'; ?>
                                </td>

                                <td class="col-status text-center" data-label="Estatus">
                                    <span class="badge <?php echo $activo ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo $activo ? 'ACTIVO' : 'INACTIVO'; ?>
                                    </span>
                                </td>

                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <!-- Ver (solo lectura) -->
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver especialista"
                                            onclick="especialistaController.abrir(<?php echo $e['id_especialista']; ?>, true)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <!-- Editar -->
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar especialista"
                                            onclick="especialistaController.abrir(<?php echo $e['id_especialista']; ?>, false)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <!-- Activar / Desactivar -->
                                        <button type="button"
                                            class="btn-action <?php echo $activo ? 'btn-delete' : 'btn-activate'; ?>"
                                            title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>"
                                            onclick="especialistaController.cambiarEstatus(
                                                <?php echo $e['id_especialista']; ?>,
                                                <?php echo $activo ? 2 : 1; ?>,
                                                '<?php echo htmlspecialchars($e['nombre_completo']); ?>'
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
                $urlBase = view_url('especialistas.php') . '?buscar=' . urlencode($buscar) . '&pagina=';
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
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> especialista(s)
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
var API_URL          = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS_ESP    = <?php echo $catalogosJson; ?>;
</script>

<!-- Modal especialista -->
<?php include '../includes/modal_especialista.php'; ?>

<!-- JS específico del módulo -->
<script src="<?php echo asset('js/especialistas.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

<?php include '../includes/footer.php'; ?>