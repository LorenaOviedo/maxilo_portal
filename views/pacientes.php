<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Paciente.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
// Configuración de la página
$page_title = 'Pacientes';
$page_css   = ['catalogos-tabla.css', 'modal.css', 'odontograma.css'];
$page_js    = [];
 
// Paginación
$porPagina    = 10;
$paginaActual = max(1, (int)($_GET['pagina'] ?? 1));
 
// Obtener datos de la BD
$db             = getDB();
$modeloPaciente = new Paciente($db);
$total          = $modeloPaciente->contarTotal();
$totalPaginas   = max(1, (int)ceil($total / $porPagina));
$paginaActual   = min($paginaActual, $totalPaginas);
$pacientes      = $modeloPaciente->getAll([], $paginaActual, $porPagina);
$tiposSangre    = $modeloPaciente->getTiposSangre();
$parentescos    = $modeloPaciente->getTiposParentesco();
$ocupaciones    = $modeloPaciente->getOcupaciones();
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <!-- Contenido principal -->
        <main class="main-content">
 
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('pacientes.php'); ?>">Pacientes</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Pacientes</span>
                </div>
            </nav>
 
            <!-- Header -->
            <div class="page-header">
                <h1>Pacientes</h1>
                <p class="page-description">Registro y control de pacientes</p>
            </div>
 
            <!-- Búsqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por número, nombre, teléfono..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="abrirModalNuevoPaciente()">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>
 
            <!-- Tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id"     data-sort="numero">NÚMERO DE<br>PACIENTE</th>
                            <th class="col-name"   data-sort="nombre">NOMBRE COMPLETO</th>
                            <th class="col-tel">TELÉFONO</th>
                            <th class="col-email">CORREO ELECTRÓNICO</th>
                            <th class="col-edad"   data-sort="edad">EDAD</th>
                            <th class="col-visit">ÚLTIMA VISITA</th>
                            <th class="col-status">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pacientes)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-folder-open-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay pacientes registrados</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer paciente</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pacientes as $p): ?>
                            <?php $activo = (int)$p['id_estatus'] === 1; ?>
                            <tr>
                                <td class="col-id" data-label="No. Paciente" data-col="numero">
                                    <span style="font-weight:700;">P-<?php echo str_pad($p['numero_paciente'], 3, '0', STR_PAD_LEFT); ?></span>
                                    <br>
                                    <span style="font-size:11px; color:#6c757d;">Exp: <?php echo htmlspecialchars($p['id_paciente_expediente']); ?></span>
                                </td>
                                <td class="col-name" data-label="Nombre" data-col="nombre">
                                    <?php echo htmlspecialchars($p['nombre_completo']); ?>
                                </td>
                                <td class="col-tel" data-label="Teléfono">
                                    <?php echo htmlspecialchars($p['telefono'] ?? '—'); ?>
                                </td>
                                <td class="col-email" data-label="Correo">
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($p['email'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($p['email'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td class="col-edad text-center" data-label="Edad" data-col="edad">
                                    <?php echo $p['edad'] ?? '—'; ?>
                                </td>
                                <td class="col-visit text-center" data-label="Última visita">
                                    <?php echo $p['ultima_visita']
                                        ? date('d/m/Y', strtotime($p['ultima_visita']))
                                        : '—'; ?>
                                </td>
                                <td class="col-status text-center" data-label="Estatus">
                                    <span class="badge <?php echo $activo ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo strtoupper($p['estatus']); ?>
                                    </span>
                                </td>
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver paciente"
                                            onclick="abrirModalVerPaciente(<?php echo $p['numero_paciente']; ?>)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button type="button" class="btn-action btn-edit"
                                            title="Editar paciente"
                                            onclick="abrirModalEditarPaciente(<?php echo $p['numero_paciente']; ?>)">
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button type="button"
                                            class="btn-action <?php echo $activo ? 'btn-delete' : 'btn-activate'; ?>"
                                            title="<?php echo $activo ? 'Desactivar' : 'Activar'; ?>"
                                            onclick="cambiarEstatusPaciente(
                                                <?php echo $p['numero_paciente']; ?>,
                                                <?php echo $activo ? 2 : 1; ?>,
                                                '<?php echo htmlspecialchars($p['nombre_completo']); ?>'
                                            )">
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
            <?php if ($total > 0): ?>
            <?php
                $urlBase      = view_url('pacientes.php') . '?pagina=';
                $inicio       = (($paginaActual - 1) * $porPagina) + 1;
                $fin          = min($paginaActual * $porPagina, $total);
            ?>
            <div class="pagination">
                <a href="<?php echo $urlBase . ($paginaActual - 1); ?>"
                   class="pagination-btn <?php echo $paginaActual <= 1 ? 'disabled' : ''; ?>"
                   <?php echo $paginaActual <= 1 ? 'onclick="return false;"' : ''; ?>>
                    <i class="ri-arrow-left-line"></i> Anterior
                </a>
                <span class="pagination-info">
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?> de <?php echo $total; ?> paciente(s)
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
// Variables globales para pacientes.js
$catalogosJson = json_encode([
    'tiposSangre' => $tiposSangre,
    'parentescos' => $parentescos,
    'ocupaciones' => $ocupaciones,
]);
?>
 
<!-- Variables globales — antes del modal y el JS -->
<script>
var API_URL   = '<?php echo ajax_url('Api.php'); ?>';
var CATALOGOS = <?php echo $catalogosJson; ?>;
</script>
 
<!-- Modal paciente -->
<?php include '../includes/modal_ver_paciente.php'; ?>
 
<!-- JS específico del módulo -->
<script src="<?php echo asset('js/pacientes.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>