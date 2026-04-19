<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/MotivoConsulta.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Motivos de Consulta';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
$db     = getDB();
$modelo = new MotivoConsulta($db);
$motivos = $modelo->getAll();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
<main class="main-content">
 
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <div class="breadcrumb-item">
            <a href="<?php echo view_url('catalogos.php'); ?>">Catálogos</a>
        </div>
        <span class="breadcrumb-separator">▶</span>
        <div class="breadcrumb-item">
            <span class="breadcrumb-current">Motivos de consulta</span>
        </div>
    </nav>
 
    <!-- Encabezado -->
    <div class="page-header">
        <h1>Motivos de consulta</h1>
        <p class="page-description">Catálogo de motivos de consulta para las citas</p>
    </div>
 
    <!-- Búsqueda y agregar -->
    <div class="search-actions-bar">
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput"
                placeholder="Buscar por motivo o descripción...">
        </div>
        <button type="button" class="btn-search">
            <i class="ri-search-line"></i> Buscar
        </button>
        <button type="button" class="btn-add-new" onclick="abrirModalNuevo()">
            <i class="ri-add-line"></i> Agregar nuevo
        </button>
    </div>
 
    <!-- Tabla -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-id"   data-sort="id">NO.</th>
                    <th class="col-name" data-sort="motivo">MOTIVO DE CONSULTA</th>
                    <th                  data-sort="descripcion">DESCRIPCIÓN</th>
                    <th class="col-actions">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($motivos)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="ri-folder-open-line"></i></div>
                                <h3 class="empty-state-title">No hay motivos registrados</h3>
                                <p class="empty-state-text">Comienza agregando el primer motivo de consulta</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($motivos as $m): ?>
                        <tr>
                            <td class="col-id" data-label="No."><?php echo $m['id_motivo_consulta']; ?></td>
                            <td class="col-name" data-label="Motivo">
                                <?php echo htmlspecialchars($m['motivo_consulta']); ?>
                            </td>
                            <td data-label="Descripción">
                                <?php echo htmlspecialchars($m['descripcion'] ?? '—'); ?>
                            </td>
                            <td class="col-actions" data-label="Acciones">
                                <div class="action-buttons">
                                    <button type="button" class="btn-action btn-view" title="Ver"
                                        onclick="abrirModalVer(<?php echo $m['id_motivo_consulta']; ?>)">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-edit" title="Editar"
                                        onclick="abrirModalEditar(<?php echo $m['id_motivo_consulta']; ?>)">
                                        <i class="ri-edit-box-line"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                        onclick="eliminarConfirmar(
                                            <?php echo $m['id_motivo_consulta']; ?>,
                                            '<?php echo htmlspecialchars($m['motivo_consulta']); ?>'
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
 
    <?php if (!empty($motivos)): ?>
        <div class="pagination">
            <button class="pagination-btn" disabled><i class="ri-arrow-left-line"></i> Anterior</button>
            <span class="pagination-info"><?php echo count($motivos); ?> motivo(s)</span>
            <button class="pagination-btn" disabled>Siguiente <i class="ri-arrow-right-line"></i></button>
        </div>
    <?php endif; ?>
 
</main>
 
<script>
    var API_URL = '<?php echo ajax_url('Api.php'); ?>';
</script>
 
<?php include '../includes/modal_motivo_consulta.php'; ?>
<script src="<?php echo asset('js/motivos_consulta.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>