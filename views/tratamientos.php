<?php
session_start();

// Incluir configuración
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

$auth = new AuthController();

// Verificar autenticación
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Tratamientos Dentales';
$page_css = ['catalogos-tabla.css'];
$page_js = ['catalogos-tabla.js'];

// Datos de ejemplo
$tratamientos = [
    [
        'numero' => 'T-001',
        'nombre' => 'PROFILAXIS',
        'tipo' => 'PREVENTIVO',
        'descripcion' => 'LIMPIEZA DENTAL PROF...',
        'precio' => '$800.00',
        'tiempo' => '60',
        'autorizacion' => 'NO',
        'estatus' => 'ACTIVO'
    ],
    [
        'numero' => 'T-002',
        'nombre' => 'EXTRACCIÓN DENTAL',
        'tipo' => 'CIRUGÍA',
        'descripcion' => 'EXTRACCIÓN P2 DENTA...',
        'precio' => '$2500.00',
        'tiempo' => '60',
        'autorizacion' => 'SI',
        'estatus' => 'ACTIVO'
    ],
];

//LLAMAR HEADER Y SIDEBAR
include '../includes/header.php';
include '../includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Breadcrumb flechas-->
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('catalogos.php'); ?>">Catálogos</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Tratamientos dentales</span>
                </div>
            </nav>

            <!-- Encabezado de la página -->
            <div class="page-header">
                <h1>Tratamientos dentales</h1>
                <p class="page-description">Catálogo para tratamientos dentales y sus costos</p>
            </div>

            <!-- Busqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Buscar por tratamiento, tipo..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="window.location.href='<?php echo view_url('tratamiento_form.php'); ?>'">
                    <i class="fas fa-plus"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Contenedor de la tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id">NÚMERO DE<br>TRATAMIENTO</th>
                            <th class="col-name">NOMBRE DEL<br>TRATAMIENTO</th>
                            <th class="col-type">TIPO</th>
                            <th class="col-description">DESCRIPCIÓN</th>
                            <th class="col-price">PRECIO</th>
                            <th class="col-time">TIEMPO<br>ESTIMADO</th>
                            <th class="col-authorization">REQUIERE<br>AUTORIZACIÓN</th>
                            <th class="col-status">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tratamientos)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay tratamientos registrados</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer tratamiento dental</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tratamientos as $tratamiento): ?>
                            <tr>
                                <td class="col-id"><?php echo htmlspecialchars($tratamiento['numero']); ?></td>
                                <td class="col-name"><?php echo htmlspecialchars($tratamiento['nombre']); ?></td>
                                <td class="col-type"><?php echo htmlspecialchars($tratamiento['tipo']); ?></td>
                                <td class="col-description">
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($tratamiento['descripcion']); ?>">
                                        <?php echo htmlspecialchars($tratamiento['descripcion']); ?>
                                    </span>
                                </td>
                                <td class="col-price"><?php echo htmlspecialchars($tratamiento['precio']); ?></td>
                                <td class="col-time text-center"><?php echo htmlspecialchars($tratamiento['tiempo']); ?></td>
                                <td class="col-authorization text-center">
                                    <?php if ($tratamiento['autorizacion'] === 'SI'): ?>
                                        <span class="badge badge-yes">SI</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">NO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-status text-center">
                                    <?php if ($tratamiento['estatus'] === 'ACTIVO'): ?>
                                        <span class="badge badge-active">ACTIVO</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">INACTIVO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <button 
                                            type="button" 
                                            class="btn-action btn-edit" 
                                            title="Editar tratamiento"
                                            onclick="editarRegistro('<?php echo $tratamiento['numero']; ?>', '<?php echo view_url('tratamiento_form.php'); ?>')"
                                        >
                                            <i class="hgi hgi-stroke hgi-pencil-edit-02"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-delete" 
                                            title="Eliminar tratamiento"
                                            onclick="eliminarRegistro('<?php echo $tratamiento['numero']; ?>', null, '¿Eliminar el tratamiento <?php echo htmlspecialchars($tratamiento['nombre']); ?>?')"
                                        >
                                            <i class="hgi hgi-stroke hgi-delete-02"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination-->
            <?php if (!empty($tratamientos)): ?>
            <div class="pagination">
                <button class="pagination-btn" disabled>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-info">Página 1 de 1</span>
                <button class="pagination-btn" disabled>
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <?php endif; ?>
        </main>

<?php
////INCLUIR FOOTER (pendiente se carga mediante $page_js)
include '../includes/footer.php';
?>