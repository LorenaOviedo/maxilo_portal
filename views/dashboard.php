<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_start();

//Incluir configuración
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

$auth = new AuthController();

// Verificar que el usuario esté autenticado
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

//CONFIGURACIÓN DE LA PÁGINA
$page_title = 'Dashboard';

//Producción
// CSS dela página (array)
//$page_css = ['.css'];
// JS de la página (array)
//$page_js = ['.js', '.js'];

//LLAMAR HEADER Y SIDEBAR
include '../includes/header.php';
include '../includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">
            <div class="content-header">
                <h1>Bienvenido de nuevo, Dr. <?php echo htmlspecialchars(explode(' ', $nombreUsuario)[0]); ?></h1>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr 1fr; margin-bottom: 30px;">
                <!-- Citas programadas para hoy -->
                <div class="card card-primary">
                    <h3 class="card-title">Citas programadas para el día de hoy</h3>
                    <div class="card-number" style="font-size: 48px; color: #20a89e; margin: 15px 0;">8</div>
                    <div class="card-detail">
                        <div style="margin-bottom: 8px;"><strong>3</strong> Control</div>
                        <div style="margin-bottom: 8px;"><strong>4</strong> Control Ortodoncia</div>
                        <div><strong>1</strong> Extracción</div>
                    </div>
                </div>
                
                <!-- Citas atendidas ayer -->
                <div class="card card-info">
                    <h3 class="card-title">Citas atendidas ayer</h3>
                    <div class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">13</div>
                </div>
                
                <!-- Citas atendidas hoy -->
                <div class="card card-success">
                    <h3 class="card-title">Citas atendidas hoy</h3>
                    <div class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">4</div>
                </div>
            </div>
            
            <!-- Agenda, Calendario, Facturas fila 2-->
            <div class="dashboard-grid">
                <!-- Agenda -->
                <div class="agenda-card">
                    <h3>Agenda</h3>
                    <ul class="agenda-list">
                        <li class="agenda-item" style="background-color: #b8e6e3;">
                            <span class="agenda-item-name">Mariana Lopez Lopez</span>
                            <span class="agenda-item-time">4:00 PM</span>
                        </li>
                        <li class="agenda-item" style="background-color: #a8e0a3;">
                            <span class="agenda-item-name">Roberto Perez Rivas</span>
                            <span class="agenda-item-time">5:00 PM</span>
                        </li>
                        <li class="agenda-item" style="background-color: #a8e0a3;">
                            <span class="agenda-item-name">Alejandra Rojas Rojas</span>
                            <span class="agenda-item-time">6:00 PM</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Calendario -->
                <div class="calendar-card">
                    <h3>Noviembre</h3>
                    <div class="calendar">
                        <div class="calendar-header">
                            <div>L</div>
                            <div>M</div>
                            <div>M</div>
                            <div>J</div>
                            <div>V</div>
                            <div>S</div>
                            <div>D</div>
                        </div>
                        <div class="calendar-body">
                            <!-- Diass del calendario -->
                            <?php
                            $dias_mes_anterior = [27, 28, 29, 30, 31];
                            foreach ($dias_mes_anterior as $dia) {
                                echo '<div class="calendar-day prev-month">' . $dia . '</div>';
                            }
                            
                            for ($dia = 1; $dia <= 30; $dia++) {
                                $clase = ($dia == 20) ? 'calendar-day current-day' : 'calendar-day';
                                echo '<div class="' . $clase . '">' . $dia . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Facturas pendientes -->
                <div class="stats-card">
                    <h3>Facturas pendientes</h3>
                    <div class="card-number" style="font-size: 64px; color: #20a89e; text-align: center; margin: 30px 0;">5</div>
                </div>
            </div>
        </main>

<?php
//INCLUIR FOOTER
include '../includes/footer.php';
?>