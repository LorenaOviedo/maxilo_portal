<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/AntecedenteMedico.php';

session_start();

$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

$page_title = 'Antecedentes Médicos';
$page_css = ['catalogos-tabla.css', 'modal.css'];
$page_js = [];

$db = getDB();
$modelo = new AntecedenteMedico($db);
$antecedentes = $modelo->getAll();

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
            <span class="breadcrumb-current">Antecedentes médicos</span>
        </div>
    </nav>

    <!-- Encabezado -->
    <div class="page-header">
        <h1>Antecedentes médicos</h1>
        <p class="page-description">Catálogo de antecedentes médicos para el historial de pacientes</p>
    </div>

    <!-- Búsqueda y agregar -->
    <div class="search-actions-bar">
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput" placeholder="Buscar por nombre o tipo...">
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
                    <th class="col-id" data-sort="id">NO.</th>
                    <th class="col-name" data-sort="nombre">NOMBRE DEL ANTECEDENTE</th>
                    <th data-sort="tipo">TIPO</th>
                    <th class="col-authorization text-center">IMPLICA ALERTA<br>MÉDICA</th>
                    <th class="col-actions">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($antecedentes)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="ri-folder-open-line"></i></div>
                                <h3 class="empty-state-title">No hay antecedentes registrados</h3>
                                <p class="empty-state-text">Comienza agregando el primer antecedente médico</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($antecedentes as $a): ?>
                        <tr>
                            <td class="col-id" data-label="No."><?php echo $a['id_antecedente']; ?></td>
                            <td class="col-name" data-label="Nombre">
                                <?php echo htmlspecialchars($a['nombre_antecedente']); ?>
                            </td>
                            <td data-label="Tipo">
                                <?php echo htmlspecialchars($a['tipo'] ?? '—'); ?>
                            </td>
                            <td class="text-center" data-label="Alerta médica">
                                <?php if ((int) $a['implica_alerta_medica'] === 1): ?>
                                    <span class="badge badge-yes">SÍ</span>
                                <?php else: ?>
                                    <span class="badge badge-no">NO</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-actions" data-label="Acciones">
                                <div class="action-buttons">
                                    <button type="button" class="btn-action btn-view" title="Ver"
                                        onclick="abrirModalVer(<?php echo $a['id_antecedente']; ?>)">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button type="button" class="btn-action btn-edit" title="Editar"
                                        onclick="abrirModalEditar(<?php echo $a['id_antecedente']; ?>)">
                                        <i class="ri-edit-box-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($antecedentes)): ?>
        <div class="pagination">
            <button class="pagination-btn" disabled><i class="ri-arrow-left-line"></i> Anterior</button>
            <span class="pagination-info"><?php echo count($antecedentes); ?> antecedente(s)</span>
            <button class="pagination-btn" disabled>Siguiente <i class="ri-arrow-right-line"></i></button>
        </div>
    <?php endif; ?>

</main>

<script>
    var API_URL = '<?php echo ajax_url('Api.php'); ?>';
</script>

<?php include '../includes/modal_antecedentes_medicos.php'; ?>
<script src="<?php echo asset('js/antecedentes_medicos.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

<?php include '../includes/footer.php'; ?>