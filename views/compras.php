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
$page_title = 'Compras';
$page_css = ['catalogos-tabla.css'];
$page_js = ['catalogos-tabla.js'];

// Datos de ejemplo
$compras = [
    [
        'numero_compra' => 'FO-001',
        'proveedor' => 'PRODUCTOS ODONTOLOGICOS SA DE CV',
        'producto' => 'RESINA AUTOSELLABLE',
        'cantidad' => '15',
        'fecha_emision' => '2026-01-15',
        'fecha_entrega' => '2026-01-20',
    ],
    [
        'numero_compra' => 'FO-002',
        'proveedor' => 'MEDICAMENTOS Y EQUIPO ODONTOLOGICO SA DE CV',
        'producto' => 'RESINA SECADO RAPIDO 20 ML',
        'cantidad' => '25',
        'fecha_emision' => '2026-01-18',
        'fecha_entrega' => '2026-01-25',

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
                    <a href="<?php echo view_url('compras_proveedores.php'); ?>">Compras y proveedores</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Compras</span>
                </div>
            </nav>

            <!-- Encabezado de la página -->
            <div class="page-header">
                <h1>Compras</h1>
                <p class="page-description">Administre las órdenes de compra</p>
            </div>

            <!-- Busqueda y agregar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Buscar por número de compra, proveedor..."
                        id="searchInput"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
                <button type="button" class="btn-add-new" onclick="window.location.href='<?php echo view_url('tratamiento_form.php'); ?>'">
                    <i class="ri-add-line"></i>
                    Agregar nuevo
                </button>
            </div>

            <!-- Contenedor de la tabla -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-folio">FOLIO DE ORDEN<br>COMPRA</th>
                            <th class="col-proveedor">PROVEEDOR</th>
                            <th class="col-producto">PRODUCTO</th>
                            <th class="col-cantidad">CANTIDAD</th>
                            <th class="col-emision">FECHA DE<br>EMISIÓN</th>
                            <th class="col-entrega">FECHA DE<br>ENTREGA</th>
                            <th class="col-acciones">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($compras)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-folder-open-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">No hay órdenes de compra registradas</h3>
                                    <p class="empty-state-text">Comienza agregando tu primer orden de compra</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td class="col-folio"><?php echo htmlspecialchars($compra['numero_compra']); ?></td>
                                <td class="col-proveedor"><?php echo htmlspecialchars($compra['proveedor']); ?></td>
                                <td class="col-producto"><?php echo htmlspecialchars($compra['producto']); ?></td>
                                <td class="col-cantidad"><?php echo htmlspecialchars($compra['cantidad']); ?></td>
                                <td class="col-emision"><?php echo htmlspecialchars($compra['fecha_emision']); ?></td>
                                <td class="col-entrega"><?php echo htmlspecialchars($compra['fecha_entrega']); ?></td>
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
                                            title="Editar compra"
                                            onclick="editarRegistro('<?php echo $compra['folio']; ?>', '<?php echo view_url('compra_form.php'); ?>')"
                                        >
                                            <i class="ri-edit-box-line"></i>
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-action btn-delete" 
                                            title="Eliminar compra"
                                            onclick="eliminarRegistro('<?php echo $compra['folio']; ?>', null, '¿Eliminar la compra <?php echo htmlspecialchars($compra['folio']); ?>?')"
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
            <?php if (!empty($compras)): ?>
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