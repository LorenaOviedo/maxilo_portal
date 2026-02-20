<?php
/**
 * Vista de Citas
 * Sistema Maxilofacial Texcoco
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

session_start();

$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}

$page_title = 'Citas';
$page_css = ['citas.css'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb">Citas</div>
                    <h1 class="page-title">Citas</h1>
                    <p class="page-subtitle">Gestione y administre citas de sus pacientes.</p>
                </div>
                <button class="btn-nueva-cita" id="btnNuevaCita">
                    Nueva cita <i class="ri-add-line"></i>
                </button>
            </div>

            <!-- Layout principal -->
            <div class="citas-layout">

                <!-- ===== CALENDARIO ===== -->
                <div class="calendar-card">
                    <div class="calendar-header">
                        <button class="calendar-nav" id="btnPrevMes">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>
                        <h2 class="calendar-title" id="calendarTitle"></h2>
                        <button class="calendar-nav" id="btnNextMes">
                            <i class="ri-arrow-right-s-line"></i>
                        </button>
                    </div>

                    <div class="calendar-grid">
                        <div class="calendar-day-header">Dom</div>
                        <div class="calendar-day-header">Lun</div>
                        <div class="calendar-day-header">Mar</div>
                        <div class="calendar-day-header">Mié</div>
                        <div class="calendar-day-header">Jue</div>
                        <div class="calendar-day-header">Vie</div>
                        <div class="calendar-day-header">Sáb</div>
                    </div>

                    <div class="calendar-body" id="calendarBody"></div>

                    <div class="calendar-legend">
                        <span class="legend-item">
                            <span class="legend-dot disponible"></span> Disponible
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot poca"></span> Poca disponibilidad
                        </span>
                        <span class="legend-item">
                            <span class="legend-dot ocupado"></span> No disponible
                        </span>
                    </div>
                </div>

                <!-- ===== PANEL DE CITAS DEL DÍA ===== -->
                <div class="citas-panel">
                    <div class="citas-panel-header">
                        <h3 class="citas-panel-title">Citas del día</h3>
                        <span class="citas-panel-fecha" id="citasFechaSeleccionada"></span>
                    </div>

                    <div class="citas-filtros">
                        <button class="filtro-btn active" data-estatus="todas">Todas</button>
                        <button class="filtro-btn" data-estatus="Confirmada">Confirmadas</button>
                        <button class="filtro-btn" data-estatus="Pendiente">Pendientes</button>
                        <button class="filtro-btn" data-estatus="Cancelada">Canceladas</button>
                    </div>

                    <div class="citas-lista" id="citasLista">
                        <div class="citas-loading">
                            <i class="ri-loader-4-line spin"></i>
                            <span>Cargando citas...</span>
                        </div>
                    </div>
                </div>

            </div><!-- /citas-layout -->
        </main>

<!-- ==================== MODAL NUEVA/EDITAR CITA ==================== -->
<div class="modal-overlay" id="modalCita">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nueva Cita</h3>
            <button class="modal-close" id="btnCerrarModal">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div class="modal-body">
            <form id="formCita">
                <input type="hidden" id="citaId" name="id_cita">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-user-line"></i> Paciente *
                        </label>
                        <select class="form-select" id="selectPaciente" name="id_paciente" required>
                            <option value="">Seleccionar paciente...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-file-list-line"></i> Tipo de Paciente *
                        </label>
                        <select class="form-select" id="selectTipoPaciente" name="tipoPaciente" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="Primera vez">Primera vez</option>
                            <option value="Seguimiento">Seguimiento</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-stethoscope-line"></i> Especialista *
                        </label>
                        <select class="form-select" id="selectEspecialista" name="id_especialista" required>
                            <option value="">Seleccionar especialista...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-heart-pulse-line"></i> Motivo de Consulta *
                        </label>
                        <select class="form-select" id="selectMotivo" name="id_motivo_consulta" required>
                            <option value="">Seleccionar motivo...</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-calendar-line"></i> Fecha *
                        </label>
                        <input type="date" class="form-input" id="inputFecha" name="fecha_cita"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-time-line"></i> Hora de Inicio *
                        </label>
                        <input type="time" class="form-input" id="inputHora" name="hora_inicio" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-timer-line"></i> Duración
                        </label>
                        <select class="form-select" id="selectDuracion" name="duracion_aproximada">
                            <option value="30">30 minutos</option>
                            <option value="60" selected>60 minutos</option>
                            <option value="90">90 minutos</option>
                            <option value="120">2 horas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="ri-money-dollar-circle-line"></i> Costo Estimado
                        </label>
                        <input type="number" class="form-input" id="inputCosto" name="costo_estimado"
                               placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>

                <!-- Estatus: solo visible al editar -->
                <div class="form-group" id="groupEstatus" style="display: none;">
                    <label class="form-label">
                        <i class="ri-checkbox-circle-line"></i> Estatus
                    </label>
                    <select class="form-select" id="selectEstatus" name="estatus">
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>

                <div class="form-alert" id="alertConflicto" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span id="alertMessage"></span>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button class="btn-secondary" id="btnCancelarModal">Cancelar</button>
            <button class="btn-primary" id="btnGuardarCita">
                <i class="ri-save-line"></i> Guardar Cita
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL DETALLE ==================== -->
<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Detalle de Cita</h3>
            <button class="modal-close" id="btnCerrarDetalle">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body" id="detalleContent"></div>
        <div class="modal-footer">
            <button class="btn-secondary" id="btnCerrarDetalle2">Cerrar</button>
            <button class="btn-warning" id="btnEditarDesdeDetalle">
                <i class="ri-edit-line"></i> Editar
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL CONFIRMAR ELIMINAR ==================== -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal modal-xs">
        <div class="modal-header">
            <h3 class="modal-title">Confirmar eliminación</h3>
            <button class="modal-close" id="btnCerrarEliminar">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-content">
                <div class="confirm-icon">
                    <i class="ri-delete-bin-line"></i>
                </div>
                <p class="confirm-text">¿Estás seguro de que deseas eliminar esta cita?</p>
                <p class="confirm-subtext">Esta acción no se puede deshacer.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" id="btnCancelarEliminar">Cancelar</button>
            <button class="btn-danger" id="btnConfirmarEliminar">
                <i class="ri-delete-bin-line"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<!-- JS del módulo -->
<script src="<?php echo asset('js/citas.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

    </div><!-- /dashboard-container (abierto por header.php) -->
</body>
</html>

<?php
//INCLUIR FOOTER
include '../includes/footer.php';
?>