<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Dashboard.php';


$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

// Configuración de la página
$page_title = 'Dashboard';

// Obtener datos de la BD — mismo patrón que pacientes.php
$db = getDB();
$dashboard = new Dashboard($db);
$datos = $dashboard->resumen();

$citasHoy = $datos['citas_hoy'];
$citasHoyMotivo = $datos['citas_hoy_motivo'];
$citasAtendidasHoy = $datos['citas_atendidas_hoy'];
$citasAtendidasAyer = $datos['citas_atendidas_ayer'];
$agenda = $datos['agenda'];
$facturasPendientes = $datos['facturas_pendientes'];

$agendaColores = ['#b8e6e3', '#a8e0a3', '#ffe0a3', '#d4b8e6', '#b8cee6'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Contenido principal -->
<main class="main-content">
    <div class="content-header">
        <h1>Bienvenido de nuevo, Dr. <?php echo htmlspecialchars(explode(' ', $nombreUsuario)[0]); ?></h1>
    </div>

    <!-- Fila 1: Métricas de citas -->
    <div class="dashboard-grid grid-citas mb-30">

        <div class="card card-primary">
            <h3 class="card-title">Citas programadas para el día de hoy</h3>
            <div id="citas-hoy-total" class="card-number" style="font-size: 48px; color: #20a89e; margin: 15px 0;">
                <?php echo $citasHoy; ?>
            </div>
            <div id="citas-hoy-motivo" class="card-detail">
                <?php if (!empty($citasHoyMotivo)): ?>
                    <?php foreach ($citasHoyMotivo as $fila): ?>
                        <div style="margin-bottom: 8px;">
                            <strong><?php echo (int) $fila['total']; ?></strong>
                            <?php echo htmlspecialchars($fila['motivo']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: #999;">Sin citas registradas</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-info">
            <h3 class="card-title">Citas atendidas ayer</h3>
            <div id="citas-atendidas-ayer" class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">
                <?php echo $citasAtendidasAyer; ?>
            </div>
        </div>

        <div class="card card-success">
            <h3 class="card-title">Citas atendidas hoy</h3>
            <div id="citas-atendidas-hoy" class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">
                <?php echo $citasAtendidasHoy; ?>
            </div>
        </div>
    </div>

    <!-- Fila 2: Agenda, Calendario, Facturas -->
    <div class="dashboard-grid">

        <div class="agenda-card">
            <h3>Agenda de hoy</h3>
            <ul id="agenda-lista" class="agenda-list">
                <?php if (!empty($agenda)): ?>
                    <?php foreach ($agenda as $i => $cita): ?>
                        <li class="agenda-item"
                            style="background-color: <?php echo $agendaColores[$i % count($agendaColores)]; ?>;">
                            <span class="agenda-item-name">
                                <?php echo htmlspecialchars($cita['paciente']); ?>
                                <small style="display:block; font-size:11px; opacity:.75;">
                                    <?php echo htmlspecialchars($cita['motivo']); ?>
                                </small>
                            </span>
                            <span class="agenda-item-time"><?php echo htmlspecialchars($cita['hora']); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="agenda-item" style="background-color: #f5f5f5; color: #999;">
                        <span class="agenda-item-name">Sin citas pendientes</span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="calendar-card">
            <?php
            $meses = [
                'Enero',
                'Febrero',
                'Marzo',
                'Abril',
                'Mayo',
                'Junio',
                'Julio',
                'Agosto',
                'Septiembre',
                'Octubre',
                'Noviembre',
                'Diciembre'
            ];
            $mesActual = (int) date('n');
            $anioActual = (int) date('Y');
            $diaActual = (int) date('j');
            $diasEnMes = (int) date('t');
            $primerDia = (int) date('N', mktime(0, 0, 0, $mesActual, 1, $anioActual)) - 1;
            $diasMesAnt = (int) date('t', mktime(0, 0, 0, $mesActual - 1, 1, $anioActual));
            ?>
            <h3><?php echo $meses[$mesActual - 1] . ' ' . $anioActual; ?></h3>
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
                    <?php
                    for ($i = $primerDia - 1; $i >= 0; $i--):
                        echo '<div class="calendar-day prev-month">' . ($diasMesAnt - $i) . '</div>';
                    endfor;
                    for ($dia = 1; $dia <= $diasEnMes; $dia++):
                        $clase = ($dia === $diaActual) ? 'calendar-day current-day' : 'calendar-day';
                        echo '<div class="' . $clase . '">' . $dia . '</div>';
                    endfor;
                    $restantes = (7 - (($primerDia + $diasEnMes) % 7)) % 7;
                    for ($i = 1; $i <= $restantes; $i++):
                        echo '<div class="calendar-day next-month">' . $i . '</div>';
                    endfor;
                    ?>
                </div>
            </div>
        </div>

        <div class="stats-card">
            <h3>Facturas pendientes</h3>
            <div id="facturas-pendientes" class="card-number"
                style="font-size: 64px; color: #20a89e; text-align: center; margin: 30px 0;">
                <?php echo $facturasPendientes; ?>
            </div>
        </div>
    </div>
</main>

<script>
    var API_URL = '<?php echo ajax_url('Api.php'); ?>';
</script>

<script src="<?php echo asset('js/dashboard.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

<?php
//INCLUIR FOOTER
include '../includes/footer.php';
?>