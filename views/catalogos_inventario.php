<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
session_start();


$auth = new AuthController();

//Verificar autenticación
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Inventarios';
$page_css = ['catalogos.css'];

//Definir catálogoss
$inventarios = [
    [
        'titulo' => 'Productos',
        'descripcion' => 'Administre los productos del sistema.',
        'url' => 'productos.php'
    ],
    [
        'titulo' => 'Movimientos de almacén',
        'descripcion' => 'Registre los movimientos de productos en el almacén.',
        'url' => 'movimientos_almacen.php'
    ],
    [
        'titulo' => 'Inventarios',
        'descripcion' => 'Gestione el inventario de productos del sistema.',
        'url' => 'inventario.php'
    ],
];

// LLAMAR  HEADER Y SIDEBAR
include '../includes/header.php';
include '../includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">
            <div class="content-header">
                <h1>Inventarios</h1>
            </div>
            
            <!-- Grid de tarjetas de catálogos -->
            <div class="catalog-grid">
                <?php foreach ($inventarios as $inventario): ?>
                <div class="catalog-card">
                    <div class="catalog-card-header">
                        <h3 class="catalog-card-title"><?php echo htmlspecialchars($inventario['titulo']); ?></h3>
                        <p class="catalog-card-description"><?php echo htmlspecialchars($inventario['descripcion']); ?></p>
                    </div>
                    <div class="catalog-card-footer">
                        <a href="<?php echo view_url($inventario['url']); ?>" class="btn-catalog">
                            Gestionar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

<?php
//INCLUIR FOOTER
include '../includes/footer.php';
?>