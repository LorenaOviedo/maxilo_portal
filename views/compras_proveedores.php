<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
session_start();


$auth = new AuthController();

//Verificar autenticación
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Compraas y proveedores';
$page_css = ['catalogos.css'];

//Definir catálogoss
$catalogos = [
    [
        'titulo' => 'Proveedores',
        'descripcion' => 'Administre el catálogo de proveedores.',
        'url' => 'proveedores.php'
    ],
    [
        'titulo' => 'Compras',
        'descripcion' => 'Administre las órdenes de compra.',
        'url' => 'compras.php'
    ],
];

// LLAMAR  HEADER Y SIDEBAR
include '../includes/header.php';
include '../includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">
            <div class="content-header">
                <h1>Catálogos</h1>
            </div>
            
            <!-- Grid de tarjetas de catálogos -->
            <div class="catalog-grid">
                <?php foreach ($catalogos as $catalogo): ?>
                <div class="catalog-card">
                    <div class="catalog-card-header">
                        <h3 class="catalog-card-title"><?php echo htmlspecialchars($catalogo['titulo']); ?></h3>
                        <p class="catalog-card-description"><?php echo htmlspecialchars($catalogo['descripcion']); ?></p>
                    </div>
                    <div class="catalog-card-footer">
                        <a href="<?php echo view_url($catalogo['url']); ?>" class="btn-catalog">
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