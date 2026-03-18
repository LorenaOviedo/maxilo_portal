<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Procedimientos.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
// Configuración de la página
$page_title = 'Procedimientos Dentales';
$page_css   = ['catalogos-tabla.css'];
$page_js    = ['catalogos-tabla.js'];
 
// Obtener datos de la BD
$db = getDB();
$modeloProcedimiento = new Procedimiento($db);
$procedimientos  = $modeloProcedimiento->getAll();
$especialidades  = $modeloProcedimiento->getEspecialidades();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <!-- Contenido principal -->
        <main class="main-content">
 
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('catalogos.php'); ?>">Catálogos</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Procedimientos dentales</span>
                </div>
            </nav>
 
            <!-- Encabezado -->
            <div class="page-header">
                <h1>Procedimientos dentales</h1>
                <p class="page-description">Catálogo de procedimientos dentales y sus costos</p>
            </div>
 
            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por procedimiento, tipo, especialidad..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="abrirModalNuevo()">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>
 
            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id">ID</th>
                            <th class="col-name" data-sort="nombre">NOMBRE DEL PROCEDIMIENTO</th>
                            <th class="col-type" data-sort="tipo">TIPO</th>
                            <th class="col-specialty" data-sort="especialidad">ESPECIALIDAD</th>
                            <th class="col-description">DESCRIPCIÓN</th>
                            <th class="col-price" data-sort="precio">PRECIO BASE</th>
                            <th class="col-time">TIEMPO<br>ESTIMADO</th>
                            <th class="col-authorization">REQUIERE<br>AUTORIZACIÓN</th>
                            <th class="col-status">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($procedimientos)): ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-folder-open-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay procedimientos registrados</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer procedimiento dental</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($procedimientos as $p): ?>
                            <tr>
                                <td class="col-id" data-label="ID">
                                    <?php echo $p['id_procedimiento']; ?>
                                </td>
                                <td class="col-name" data-label="Nombre" data-col="nombre">
                                    <?php echo htmlspecialchars($p['nombre_procedimiento']); ?>
                                </td>
                                <td class="col-type" data-label="Tipo" data-col="tipo">
                                    <?php echo htmlspecialchars($p['tipo'] ?? '—'); ?>
                                </td>
                                <td class="col-specialty" data-label="Especialidad" data-col="especialidad">
                                    <?php echo htmlspecialchars($p['especialidad']); ?>
                                </td>
                                <td class="col-description" data-label="Descripción">
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($p['descripcion'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($p['descripcion'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td class="col-price" data-label="Precio" data-col="precio">
                                    $<?php echo number_format((float)$p['precio_base'], 2); ?>
                                </td>
                                <td class="col-time text-center" data-label="Tiempo (min)">
                                    <?php echo $p['tiempo_estimado'] ? $p['tiempo_estimado'] . ' min' : '—'; ?>
                                </td>
                                <td class="col-authorization text-center" data-label="Autorización">
                                    <?php if ($p['requiere_autorizacion']): ?>
                                        <span class="badge badge-yes">SÍ</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">NO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-status text-center" data-label="Estatus">
                                    <?php $activo = (int)$p['id_estatus'] === 1; ?>
                                    <span class="badge <?php echo $activo ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo htmlspecialchars(strtoupper($p['estatus'])); ?>
                                    </span>
                                </td>
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="btn-action btn-edit"
                                            title="Editar procedimiento"
                                            onclick="abrirModalEditar(<?php echo $p['id_procedimiento']; ?>)"
                                        >
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn-action <?php echo $activo ? 'btn-delete' : 'btn-activate'; ?>"
                                            title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>"
                                            onclick="cambiarEstatusConfirmar(
                                                <?php echo $p['id_procedimiento']; ?>,
                                                <?php echo $activo ? 2 : 1; ?>,
                                                '<?php echo htmlspecialchars($p['nombre_procedimiento']); ?>'
                                            )"
                                        >
                                            <i class="<?php echo $activo ? 'ri-forbid-line' : 'ri-checkbox-circle-line'; ?>"></i>
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
            <?php if (!empty($procedimientos)): ?>
            <div class="pagination">
                <button class="pagination-btn" disabled>
                    <i class="ri-arrow-left-line"></i> Anterior
                </button>
                <span class="pagination-info">
                    <?php echo count($procedimientos); ?> procedimiento(s)
                </span>
                <button class="pagination-btn" disabled>
                    Siguiente <i class="ri-arrow-right-line"></i>
                </button>
            </div>
            <?php endif; ?>
 
        </main>
 
<?php
// Pasar especialidades al modal via JSON para el select dinámico
$especialidadesJson = json_encode($especialidades);
?>
 
<!-- ==================== MODAL PROCEDIMIENTO ==================== -->
<?php include '../includes/modal_procedimiento.php'; ?>
 
<script>
// Especialidades disponibles (desde PHP)
const ESPECIALIDADES = <?php echo $especialidadesJson; ?>;
</script>
 
<?php include '../includes/footer.php'; ?>