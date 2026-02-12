<?php
session_start();

// Incluir configuración
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

$auth = new AuthController();

//Verificar autenticación
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Catálogos';
$page_css = ['catalogos.css'];

//Definir catálogoss
$catalogos = [
    [
        'titulo' => 'Tratamientos dentales',
        'descripcion' => 'Catálogo para tratamientos dentales y sus costos.',
        'url' => 'tratamientos.php'
    ],
    [
        'titulo' => 'Permisos',
        'descripcion' => 'Administre los permisos para cada usuario.',
        'url' => 'permisos.php'
    ],
    [
        'titulo' => 'Motivos de consulta',
        'descripcion' => 'Catálogo para los motivos de consulta de un paciente.',
        'url' => 'motivos.php'
    ],
    [
        'titulo' => 'Antecedentes médicos',
        'descripcion' => 'Catálogo para registrar los antecedentes médicos más comunes.',
        'url' => 'antecedentes.php'
    ],
    [
        'titulo' => 'Roles',
        'descripcion' => 'Administre los roles de usuario para el sistema.',
        'url' => 'roles.php'
    ],
    [
        'titulo' => 'Odontograma',
        'descripcion' => 'Administre las referencias del odontograma.',
        'url' => 'odontograma.php'
    ],
    [
        'titulo' => 'Países, estados y ciudades',
        'descripcion' => 'Administre países, estados y ciudades.',
        'url' => 'ubicaciones.php'
    ],
    [
        'titulo' => 'Claves telefónicas',
        'descripcion' => 'Catalógo de claves telefónicas.',
        'url' => 'claves_telefonicas.php'
    ],
    [
        'titulo' => 'CFDI',
        'descripcion' => 'Catálogo de CFDI existentes para facturación.',
        'url' => 'cfdi.php'
    ]
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