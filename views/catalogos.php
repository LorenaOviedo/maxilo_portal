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

// Verificar permiso específico al módulo
verificarPermiso('catalogos');

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Catálogos';
$page_css = ['catalogos.css'];

//Definir catálogoss
$catalogos = [
    [
        'titulo' => 'Procedimientos dentales',
        'descripcion' => 'Catálogo para procedimientos dentales y sus costos.',
        'url' => 'procedimientos.php'
    ],
    [
        'titulo' => 'Motivos de consulta',
        'descripcion' => 'Catálogo para los motivos de consulta de un paciente.',
        'url' => 'motivos_consulta.php'
    ],
    [
        'titulo' => 'Antecedentes médicos',
        'descripcion' => 'Catálogo para registrar los antecedentes médicos más comunes.',
        'url' => 'antecedentes_medicos.php'
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