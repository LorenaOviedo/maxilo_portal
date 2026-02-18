<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

session_start();


$auth = new AuthController();

// Verificar autenticación
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Especialistas';
$page_css = ['catalogos-tabla.css'];
$page_js = ['catalogos-tabla.js'];

// Datos de ejemplo
$especialistas = [
    [
        'numero' => 'E-001',
        'nombre' => 'ADRIANA PEREZ SANCHEZ',
        'telefono' => '5589552913',
        'correo' => 'adriana.perez@maxilo.com',
        'fecha_nacimiento' => '1993-09-10',
        'fecha_contratacion' => '2019-10-09',
        'estatus' => 'ACTIVO'
    ],
    [
        'numero' => 'E-002',
        'nombre' => 'DAVID ORTEGA ARRELLANO',
        'telefono' => '5529521426',
        'correo' => 'david.ortega@maxilo.com',
        'fecha_nacimiento' => '1995-07-01',
        'fecha_contratacion' => '2022-10-20',
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
                    <a href="<?php echo view_url('especialistas.php'); ?>">Especialistas</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Especialistas</span>
                </div>
            </nav>

            <!-- Encabezado de la página -->
            <div class="page-header">
                <h1>Especialistas</h1>
                <p class="page-description">Administre la información del personal especializado.</p>
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
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="window.location.href='<?php echo view_url('especialista_form.php'); ?>'">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Contenedor de la tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id">NÚMERO DE<br>ESPECIALISTA</th>
                            <th class="col-name">NOMBRE DEL<br>COMPLETO</th>
                            <th class="col-tel">TELÉFONO</th>
                            <th class="col-email">CORREO</th>
                            <th class="col-date-nacimiento">FECHA DE<br>NACIMIENTO</th>
                            <th class="col-date-contratacion">FECHA DE<br>CONTRATACIÓN</th>
                            <th class="col-status">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($especialistas)): ?>
                        <tr>
                            <td colspan="9">
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
                            <?php foreach ($especialistas as $especialista): ?>
                            <tr>
                                <td class="col-id"><?php echo htmlspecialchars($especialista['numero']); ?></td>
                                <td class="col-name"><?php echo htmlspecialchars($especialista['nombre']); ?></td>
                                <td class="col-tel"><?php echo htmlspecialchars($especialista['telefono']); ?></td>
                                <td class="col-email"><?php echo htmlspecialchars($especialista['correo']); ?></td>
                                <td class="col-date-nacimiento"><?php echo htmlspecialchars($especialista['fecha_nacimiento']); ?></td>
                                <td class="col-date-contratacion"><?php echo htmlspecialchars($especialista['fecha_contratacion']); ?></td>
                                <td class="col-status text-center">
                                    <?php if ($especialista['estatus'] === 'ACTIVO'): ?>
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
                                            title="Editar especialista"
                                            onclick="editarRegistro('<?php echo $especialista['numero']; ?>', '<?php echo view_url('especialista_form.php'); ?>')"
                                        >
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-delete" 
                                            title="Eliminar especialista"
                                            onclick="eliminarRegistro('<?php echo $especialista['numero']; ?>', null, '¿Eliminar el especialista <?php echo htmlspecialchars($especialista['nombre']); ?>?')"
                                        >
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

            <!-- Pagination-->
            <?php if (!empty($especialistas)): ?>
            <div class="pagination">
                <button class="pagination-btn" disabled>
                    <i class="ri-arrow-left-line"></i> Anterior
                </button>
                <span class="pagination-info">Página 1 de 1</span>
                <button class="pagination-btn" disabled>
                    Siguiente <i class="ri-arrow-right-line"></i>
                </button>
            </div>
            <?php endif; ?>
        </main>

<?php
////INCLUIR FOOTER (pendiente se carga mediante $page_js)
include '../includes/footer.php';
?>