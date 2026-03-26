<?php
//Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new AuthController();

// Verificar que el usuario está autenticado
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

// Cargar datos reales para el render inicial (sin AJAX)
$db = getDB();
$dashCtrl = new DashboardController($db);
$datos = $dashCtrl->prepararVista();

$citasHoy = $datos['citas_hoy'];
$citasHoyMotivo = $datos['citas_hoy_motivo'];
$citasAtendidasHoy = $datos['citas_atendidas_hoy'];
$citasAtendidasAyer = $datos['citas_atendidas_ayer'];
$agenda = $datos['agenda'];
$facturasPendientes = $datos['facturas_pendientes'];

// Colores de fondo para los ítems de la agenda (ciclo)
$agendaColores = ['#b8e6e3', '#a8e0a3', '#ffe0a3', '#d4b8e6', '#b8cee6'];

$page_title = 'Dashboard';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Contenido principal -->
<main class="main-content">
    <div class="content-header">
        <h1>Bienvenido de nuevo, Dr. <?php echo htmlspecialchars(explode(' ', $nombreUsuario)[0]); ?></h1>
    </div>

    <!-- ── Fila 1: Métricas de citas ─────────────────────────────── -->
    <div class="dashboard-grid grid-citas mb-30">

        <!-- Citas programadas para hoy -->
        <div class="card card-primary">
            <h3 class="card-title">Citas programadas para el día de hoy</h3>
            <div id="citas-hoy-total" class="card-number" style="font-size: 48px; color: #20a89e; margin: 15px 0;">
                <?php echo $citasHoy; ?>
            </div>
            <div id="citas-hoy-motivo" class="card-detail">
                <?php foreach ($citasHoyMotivo as $fila): ?>
                    <div style="margin-bottom: 8px;">
                        <strong><?php echo (int) $fila['total']; ?></strong>
                        <?php echo htmlspecialchars($fila['motivo']); ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($citasHoyMotivo)): ?>
                    <div style="color: #999;">Sin citas registradas</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Citas atendidas ayer -->
        <div class="card card-info">
            <h3 class="card-title">Citas atendidas ayer</h3>
            <div id="citas-atendidas-ayer" class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">
                <?php echo $citasAtendidasAyer; ?>
            </div>
        </div>

        <!-- Citas atendidas hoy -->
        <div class="card card-success">
            <h3 class="card-title">Citas atendidas hoy</h3>
            <div id="citas-atendidas-hoy" class="card-number" style="font-size: 56px; color: #20a89e; margin: 20px 0;">
                <?php echo $citasAtendidasHoy; ?>
            </div>
        </div>
    </div>

    <!-- ── Fila 2: Agenda, Calendario, Facturas ───────────────────── -->
    <div class="dashboard-grid">

        <!-- Agenda -->
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

        <!-- Calendario dinámico -->
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
            // día de la semana del día 1 (0=dom … 6=sab → convertir a lun=0)
            $primerDia = (int) date('N', mktime(0, 0, 0, $mesActual, 1, $anioActual)); // 1=lun,7=dom
            $primerDia = $primerDia - 1; // 0=lun … 6=dom
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
                    // Días del mes anterior
                    for ($i = $primerDia - 1; $i >= 0; $i--) {
                        echo '<div class="calendar-day prev-month">' . ($diasMesAnt - $i) . '</div>';
                    }
                    // Días del mes actual
                    for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                        $clase = ($dia === $diaActual) ? 'calendar-day current-day' : 'calendar-day';
                        echo '<div class="' . $clase . '">' . $dia . '</div>';
                    }
                    // Días del mes siguiente
                    $totalCeldas = $primerDia + $diasEnMes;
                    $restantes = (7 - ($totalCeldas % 7)) % 7;
                    for ($i = 1; $i <= $restantes; $i++) {
                        echo '<div class="calendar-day next-month">' . $i . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Facturas pendientes -->
        <div class="stats-card">
            <h3>Facturas pendientes</h3>
            <div id="facturas-pendientes" class="card-number"
                style="font-size: 64px; color: #20a89e; text-align: center; margin: 30px 0;">
                <?php echo $facturasPendientes; ?>
            </div>
        </div>
    </div>
</main>

<!-- ── Script: refresco automático cada 60 s ─────────────────── -->
<script>
    const DASHBOARD_API = '<?php echo BASE_URL; ?>ajax/api.php?modulo=dashboard&accion=resumen';
    const AGENDA_COLORES = ['#b8e6e3', '#a8e0a3', '#ffe0a3', '#d4b8e6', '#b8cee6'];

    async function refrescarDashboard() {
        try {
            const res = await fetch(DASHBOARD_API);
            const json = await res.json();

            if (!json.success) return;

            // Métricas numéricas
            document.getElementById('citas-hoy-total').textContent = json.citas_hoy;
            document.getElementById('citas-atendidas-hoy').textContent = json.citas_atendidas_hoy;
            document.getElementById('citas-atendidas-ayer').textContent = json.citas_atendidas_ayer;
            document.getElementById('facturas-pendientes').textContent = json.facturas_pendientes;

            // Desglose por motivo
            const motivoEl = document.getElementById('citas-hoy-motivo');
            if (json.citas_hoy_motivo.length === 0) {
                motivoEl.innerHTML = '<div style="color:#999;">Sin citas registradas</div>';
            } else {
                motivoEl.innerHTML = json.citas_hoy_motivo.map(f =>
                    `<div style="margin-bottom:8px;"><strong>${f.total}</strong> ${escHtml(f.motivo)}</div>`
                ).join('');
            }

            // Agenda
            const agendaEl = document.getElementById('agenda-lista');
            if (json.agenda.length === 0) {
                agendaEl.innerHTML = `
                <li class="agenda-item" style="background-color:#f5f5f5; color:#999;">
                    <span class="agenda-item-name">Sin citas pendientes</span>
                </li>`;
            } else {
                agendaEl.innerHTML = json.agenda.map((c, i) => `
                <li class="agenda-item" style="background-color:${AGENDA_COLORES[i % AGENDA_COLORES.length]};">
                    <span class="agenda-item-name">
                        ${escHtml(c.paciente)}
                        <small style="display:block;font-size:11px;opacity:.75;">${escHtml(c.motivo)}</small>
                    </span>
                    <span class="agenda-item-time">${escHtml(c.hora)}</span>
                </li>`).join('');
            }

        } catch (err) {
            console.warn('Dashboard: error al refrescar', err);
        }
    }

    // Escapa HTML para prevenir XSS al insertar datos dinámicos
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    // Refresco automático cada 60 segundos
    setInterval(refrescarDashboard, 60_000);
</script>
<?php
//INCLUIR FOOTER
include '../includes/footer.php';
?>