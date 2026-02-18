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

//CONFIGURACIÓN DE LA PAGINA
$page_title = 'Pacientes';
$page_css = ['catalogos-tabla.css', 'modal.css'];
$page_js = ['catalogos-tabla.js', 'modal.js'];

// Datos de ejemplo para pacientes
$pacientes = [
    [
        'numero' => 'P-001',
        'nombre' => 'MARIANA LOPEZ LOPEZ',
        'telefono' => '5589552913',
        'correo' => 'LOPLOPMAR@GMAIL.COM',
        'edad' => '32',
        'ultima_visita' => '09/10/2025',
        'estatus' => 'ACTIVO'
    ],
    [
        'numero' => 'P-002',
        'nombre' => 'ROBERTO PEREZ RIVAS',
        'telefono' => '5529521426',
        'correo' => 'ROBERTRIV@GMAIL.COM',
        'edad' => '40',
        'ultima_visita' => '09/10/2025',
        'estatus' => 'ACTIVO'
    ],
];

//LLAMAR HEADER Y SIDEBAR
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

            <!-- Busqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Buscar por número de paciente, nombre..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="window.location.href='<?php echo view_url('paciente_form.php'); ?>'">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Contenedor de la tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id">NÚMERO DE<br>PACIENTE</th>
                            <th class="col-name">NOMBRE<br>COMPLETO</th>
                            <th class="col-tel">TELEFONO</th>
                            <th class="col-email">CORREO<br>ELECTRÓNICO</th>
                            <th class="col-edad">EDAD</th>
                            <th class="col-visit">ULTIMA<br>VISITA</th>
                            <th class="col-status">ESTATUS</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pacientes)): ?>
                        <tr>
                            <td colspan="9">
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
                            <?php foreach ($pacientes as $paciente): ?>
                            <tr>
                                <td class="col-id"><?php echo htmlspecialchars($paciente['numero']); ?></td>
                                <td class="col-name"><?php echo htmlspecialchars($paciente['nombre']); ?></td>
                                <td class="col-tel"><?php echo htmlspecialchars($paciente['telefono']); ?></td>
                                <td class="col-email">
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($paciente['correo']); ?>">
                                        <?php echo htmlspecialchars($paciente['correo']); ?>
                                    </span>
                                </td>
                                <td class="col-edad"><?php echo htmlspecialchars($paciente['edad']); ?></td>
                                <td class="col-visit text-center"><?php echo htmlspecialchars($paciente['ultima_visita']); ?></td>
                                <td class="col-status text-center">
                                    <?php if ($paciente['estatus'] === 'ACTIVO'): ?>
                                        <span class="badge badge-active">ACTIVO</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">INACTIVO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <button 
                                            type="button" 
                                            class="btn-action btn-view" 
                                            title="Ver paciente"
                                            onclick='verPaciente(<?php echo json_encode($paciente); ?>)'    
                                        >
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-edit" 
                                            title="Editar paciente"
                                            onclick="editarRegistro('<?php echo $paciente['numero']; ?>', '<?php echo view_url('paciente_form.php'); ?>')"
                                        >
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-delete" 
                                            title="Eliminar paciente"
                                            onclick="eliminarRegistro('<?php echo $paciente['numero']; ?>', null, '¿Eliminar el paciente <?php echo htmlspecialchars($paciente['nombre']); ?>?')"
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
            <?php if (!empty($pacientes)): ?>
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
//INCLUIR FOOTER (pendiente se carga mediante $page_js)
include '../includes/modal_ver_paciente.php';
include '../includes/footer.php';
?>