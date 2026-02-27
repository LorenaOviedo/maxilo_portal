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
$page_title = 'Proveedores';
$page_css = ['catalogos-tabla.css', 'modal.css'];
$page_js = ['catalogos-tabla.js', 'modal.js'];

// Datos de ejemplo para proveedores
$proveedores = [
    [
        'numero_proveedor' => 'PR-001',
        'razon_social' => 'PRODUCTOS ODONTOLOGICOS LOPEZ MARTINEZ S.A. DE C.V.',
        'rfc' => 'PDO0101019A1',
        'tipo_persona' => 'MORAL',
        'tipo_producto_servicio' => 'MEDICAMENTOS',
        'domicilio_fiscal' => 'AV. DE LA PAZ 48...',
        'correo_contacto' => 'JUAN.LO@PRODUCTOSODONTOLOGICOS.COM'
    ],
    [
        'numero_proveedor' => 'PR-002',
        'razon_social' => 'MATERIALES ODONTOLOGICOS GOMEZ S.A. DE C.V.',
        'rfc' => 'IME0101019A1',
        'tipo_persona' => 'MORAL',
        'tipo_producto_servicio' => 'MATERIALES ODONTOLOGICOS',
        'domicilio_fiscal' => 'AV. MEXICO 123...',
        'correo_contacto' => 'MIGUEL.GOMEZ@MATERIALESODONTOLOGICOS.COM'
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

            <!-- Busqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Buscar por número de proveedor, RFC..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="window.location.href='<?php echo view_url('proveedor_form.php'); ?>'">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Contenedor de la tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-id-proveedor">NÚMERO DE<br>PROVEEDOR</th>
                            <th class="col-razon-social">RAZÓN SOCIAL</th>
                            <th class="col-rfc">RFC</th>
                            <th class="col-tipo-persona">TIPO<br>PERSONA</th>
                            <th class="col-tipo-producto-servicio">TIPO<br>PRODUCTO/SERVICIO</th>
                            <th class="col-domicilio-fiscal">DOMICILIO<br>FISCAL</th>
                            <th class="col-correo-contacto">CORREO<br>CONTACTO</th>
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
                            <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td class="col-id"><?php echo htmlspecialchars($proveedor['numero']); ?></td>
                                <td class="col-razon-social"><?php echo htmlspecialchars($proveedor['razon_social']); ?></td>
                                <td class="col-rfc"><?php echo htmlspecialchars($proveedor['rfc']); ?></td>
                                <td class="col-tipo-persona"><?php echo htmlspecialchars($proveedor['tipo_persona']); ?></td>
                                <td class="col-tipo-producto-servicio"><?php echo htmlspecialchars($proveedor['tipo_producto_servicio']); ?></td>
                                <td class="col-domicilio-fiscal"><?php echo htmlspecialchars($proveedor['domicilio_fiscal']); ?></td>
                                <td class="col-correo-contacto">
                                    <span class="text-truncate" title="<?php echo htmlspecialchars($proveedor['correo_contacto']); ?>">
                                        <?php echo htmlspecialchars($proveedor['correo_contacto']); ?>
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
                                        <button 
                                            type="button" 
                                            class="btn-action btn-view" 
                                            title="Ver proveedor"
                                            onclick='verProveedor(<?php echo json_encode($proveedor); ?>)'    
                                        >
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-edit" 
                                            title="Editar proveedor"
                                            onclick="editarRegistro('<?php echo $proveedor['numero']; ?>', '<?php echo view_url('proveedor_form.php'); ?>')"
                                        >
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-delete" 
                                            title="Eliminar proveedor"
                                            onclick="eliminarRegistro('<?php echo $proveedor['numero']; ?>', null, '¿Eliminar el proveedor <?php echo htmlspecialchars($proveedor['nombre']); ?>?')"
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
            <?php if (!empty($proveedores)): ?>
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