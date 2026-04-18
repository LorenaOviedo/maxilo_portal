<?php
/* MODAL DE PACIENTE REUTILIZABLE VER/EDITAR */
$modal_id = 'modalPaciente';
?>

<script>
    const ASSETS_URL = '<?php echo asset('img/odontograma/'); ?>';
</script>
 
<!-- Overlay -->
<div id="<?php echo $modal_id; ?>-overlay" class="modal-overlay"></div>
 
<!-- Contenedor del modal-->
<div id="<?php echo $modal_id; ?>" class="modal-container">
    <!-- Header -->
    <div class="modal-header">
        <div class="modal-title-wrapper">
            <h2 class="modal-title">
                Detalles del Paciente <span class="highlight" id="modalPacienteNumero"></span>
            </h2>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-add" onclick="nuevoEnModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-plus"></i>
                Agregar nuevo
            </button>
            <button type="button" class="btn-modal-close" onclick="cerrarModal('<?php echo $modal_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
 
    <!-- Tabs -->
    <div class="modal-tabs">
        <button class="modal-tab active" data-tab="tabInfoPersonal"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabInfoPersonal')">
            Información<br>Personal
        </button>
        <button class="modal-tab" data-tab="tabContacto"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabContacto')">
            Información<br>de Contacto
        </button>
        <button class="modal-tab" data-tab="tabHistorial"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabHistorial')">
            Historial Clínico
        </button>
        <button class="modal-tab" data-tab="tabAnamnesis"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabAnamnesis')">
            Anamnesis
        </button>
        <button class="modal-tab" data-tab="tabPlanes" onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabPlanes')">
            Planes de Tratamiento
        </button>
        <button class="modal-tab" data-tab="tabOdontograma"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabOdontograma')">
            Odontograma
        </button>
        <button class="modal-tab" data-tab="tabArchivos"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabArchivos')">
            Archivos médicos
        </button>
    </div>
 
    <!-- Body -->
    <div class="modal-body">
        <!-- Tab 1: información personal -->
        <div id="tabInfoPersonal" class="modal-tab-content active">
            <form class="modal-form" id="formPaciente">
                <!-- F1 Número, nombre, apellido paterno -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">No. Expediente</label>
                        <input type="number" name="id_paciente_expediente" class="form-input" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre(s)<span class="required">*</span></label>
                        <input type="text" name="nombre" class="form-input" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$"
                            title="Solo letras y espacios">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido paterno<span class="required">*</span></label>
                        <input type="text" name="apellido_paterno" class="form-input"
                            pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$" title="Solo letras y espacios">
                    </div>
                </div>
 
 
                <!-- F2 apellido materno, fecha de nacimiento -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Apellido materno<span class="required">*</span></label>
                        <input type="text" name="apellido_materno" class="form-input"
                            pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$" title="Solo letras y espacios">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento<span class="required">*</span></label>
                        <input type="date" name="fecha_nacimiento" class="form-input">
                    </div>
                </div>
 
                <!-- F3 Sexo, calle y número -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Sexo</label>
                        <select name="sexo" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="M">MASCULINO</option>
                            <option value="F">FEMENINO</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ocupación</label>
                        <select name="id_ocupacion" class="form-select">
                            <option value="">Seleccionar</option>
                            <?php foreach (json_decode($catalogosJson, true)['ocupaciones'] as $oc): ?>
                                <option value="<?php echo $oc['id_ocupacion']; ?>">
                                    <?php echo htmlspecialchars($oc['ocupacion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
 
                <!-- F4: Calle (fila propia) -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Calle<span class="required">*</span></label>
                        <input type="text" name="calle" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Exterior</label>
                        <input type="text" name="numero_exterior" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número Interior</label>
                        <input type="text" name="numero_interior" class="form-input">
                    </div>
                </div>
 
                <!-- F5 codigo postal, colonia -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Código postal</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="codigo_postal" id="inputCP" class="form-input" maxlength="5"
                                autocomplete="off" placeholder="5 dígitos">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Colonia<span class="required">*</span></label>
                        <input type="text" name="colonia" id="inputColonia" class="form-input" list="listaColonias"
                            autocomplete="off" placeholder="Busca por CP o escribe la colonia">
                        <datalist id="listaColonias"></datalist>
                    </div>
                </div>
 
                <!-- F6 estado, municipio, país -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Estado<span class="required">*</span></label>
                        <input type="text" name="estado" id="inputEstado" class="form-input" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Municipio<span class="required">*</span></label>
                        <input type="text" name="municipio" id="inputMunicipio" class="form-input" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">País<span class="required">*</span></label>
                        <input type="text" name="pais" class="form-input" value="MEXICO">
                    </div>
                </div>
            </form>
        </div>
 
        <!-- Tab 2 Contacto -->
        <div id="tabContacto" class="modal-tab-content">
            <form class="modal-form" id="formContacto">
 
                <!-- Sección: Datos de contacto del paciente -->
                <div class="form-section-title">
                    Datos de contacto del paciente<br>
 
                </div>
 
                <!-- F1: Email, teléfono -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-input" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono<span class="required">*</span></label>
                        <input type="tel" name="telefono" class="form-input" maxlength="10" placeholder="10 dígitos">
                    </div>
                </div>
 
                <!-- Sección: Contacto de emergencia -->
                <div class="form-section-title" style="margin-top: 20px;">
                    Contacto de emergencia<br>
 
                </div>
 
                <!-- F2: Nombre del contacto, parentesco -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nombre del contacto</label>
                        <input type="text" name="contacto_emergencia" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relación / Parentesco</label>
                        <select name="id_tipo_parentesco" class="form-select">
                            <option value="">Seleccionar parentesco...</option>
                            <?php foreach (json_decode($catalogosJson, true)['parentescos'] as $par): ?>
                                <option value="<?php echo $par['id_tipo_parentesco']; ?>">
                                    <?php echo htmlspecialchars($par['parentesco']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
 
                <!-- F3: Teléfono de emergencia -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Teléfono del contacto</label>
                        <input type="tel" name="telefono_emergencia" class="form-input" maxlength="10"
                            placeholder="10 dígitos">
                    </div>
                </div>
            </form>
        </div>
 
        <!-- Tab 3 historial clínico -->
        <div id="tabHistorial" class="modal-tab-content">
            <div class="modal-form">
 
                <!-- Tipo de sangre -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tipo de sangre</label>
                        <select name="id_tipo_sangre" id="selectTipoSangre" class="form-select">
                            <option value="">Seleccionar</option>
                            <?php foreach (json_decode($catalogosJson, true)['tiposSangre'] as $ts): ?>
                                <option value="<?php echo $ts['id_tipo_sangre']; ?>">
                                    <?php echo htmlspecialchars($ts['tipo_sangre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
 
                <!-- Antecedentes médicos: chips agrupados por tipo -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">
                            Antecedentes médicos
                            <span class="antecedentes-leyenda">
                                <span class="chip chip--alerta chip--demo">
                                    <i class="ri-alert-line chip-alerta-icon"></i> Implica alerta médica
                                </span>
                            </span>
                        </label>
                        <!-- Los chips se renderizan-->
                        <div id="app-antecedentes"></div>
                    </div>
                </div>
 
                <!-- Notas del historial -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Notas del historial clínico</label>
                        <textarea name="notas_historial" class="form-input form-textarea" rows="3"
                            placeholder="Notas adicionales, complicaciones previas, observaciones...">
                </textarea>
                    </div>
                </div>
 
            </div>
        </div><!-- /#tabHistorial -->
 
        <!-- Tab Anamnesis -->
        <div id="tabAnamnesis" class="modal-tab-content">
            <div class="modal-form">
 
                <!-- ── Sección 1: Antecedentes generales ──────────────── -->
                <div class="form-section-title">Antecedentes generales</div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Antecedentes familiares</label>
                        <textarea name="antecedentes_familiares" class="form-input form-textarea" rows="3"
                            placeholder="Ej: Padre con diabetes, madre con hipertensión..."></textarea>
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="alergia_latex" value="1" class="form-checkbox">
                            <span>Alergia al látex</span>
                        </label>
                    </div>
                </div>
 
                <!-- ── Sección 2: Estilo de vida ──────────────────────── -->
                <div class="form-section-title" style="margin-top: 20px;">Estilo de vida</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Salud general</label>
                        <select name="salud_general" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="mala">Mala</option>
                            <option value="buena">Buena</option>
                            <option value="muy_buena">Muy buena</option>
                            <option value="excelente">Excelente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Actividad física</label>
                        <select name="actividad_fisica" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="sedentario">Sedentario</option>
                            <option value="ligero">Ligero</option>
                            <option value="activo">Activo</option>
                            <option value="muy_activo">Muy activo</option>
                        </select>
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Número de comidas al día</label>
                        <input type="number" name="numero_comidas" class="form-input" min="1" max="10"
                            placeholder="Ej: 3">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consumo de agua</label>
                        <select name="consumo_agua" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="muy_poca">Muy poca</option>
                            <option value="poca">Poca</option>
                            <option value="regular">Regular</option>
                            <option value="mucha">Mucha</option>
                        </select>
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="toma_alcohol" value="1" class="form-checkbox">
                            <span>Consume alcohol</span>
                        </label>
                    </div>
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="fuma" value="1" class="form-checkbox">
                            <span>Fuma</span>
                        </label>
                    </div>
                </div>
 
                <!-- ── Sección 3: Salud bucodental ────────────────────── -->
                <div class="form-section-title" style="margin-top: 20px;">Salud bucodental</div>
 
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Veces que se cepilla al día</label>
                        <input type="number" name="veces_cepillado" class="form-input" min="0" max="10"
                            placeholder="Ej: 2">
                    </div>
                </div>
 
                <div class="form-row cols-2">
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="sensibilidad_dental" value="1" class="form-checkbox">
                            <span>Sensibilidad dental</span>
                        </label>
                    </div>
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="bruxismo" value="1" class="form-checkbox">
                            <span>Bruxismo (rechinar dientes)</span>
                        </label>
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group form-group--checkbox">
                        <label class="form-checkbox-label">
                            <input type="checkbox" name="ulceras_frecuentes" value="1" class="form-checkbox">
                            <span>Úlceras bucales frecuentes</span>
                        </label>
                    </div>
                </div>
 
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Historial de extracciones</label>
                        <textarea name="historial_extracciones" class="form-input form-textarea" rows="3"
                            placeholder="Describe extracciones dentales previas..."></textarea>
                    </div>
                </div>
 
            </div>
        </div><!-- /#tabAnamnesis -->
 
        <!-- Tab 4 planes de tratamiento -->
        <div id="tabPlanes" class="modal-tab-content">
            <div class="modal-form">
 
                <!-- Toolbar -->
                <div class="tab-toolbar">
                    <button type="button" class="btn-modal-add" id="btnNuevoPlan">
                        <i class="ri-add-line"></i> Nuevo plan
                    </button>
                </div>
 
                <!-- Formulario nuevo plan (oculto por defecto) -->
                <div id="formNuevoPlanContainer" style="display:none;">
                    <div class="plan-form-card">
                        <h4 class="plan-form-titulo">Nuevo plan de tratamiento</h4>
 
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Fecha de creación <span class="required">*</span></label>
                                <input type="date" id="planFecha" class="form-input" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Especialista <span class="required">*</span></label>
                                <select id="planEspecialista" class="form-select">
                                    <option value="">Seleccionar</option>
                                </select>
                            </div>
                        </div>
 
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Estatus</label>
                                <select id="planEstatus" class="form-select">
                                    <option value="">Seleccionar</option>
                                </select>
                            </div>
                        </div>
 
                        <div class="form-row cols-1">
                            <div class="form-group">
                                <label class="form-label">Notas del plan</label>
                                <textarea id="planNotas" class="form-input form-textarea" rows="2"
                                    placeholder="Observaciones, descripción del tratamiento..."></textarea>
                            </div>
                        </div>
 
                        <!-- Procedimientos del plan -->
                        <div class="plan-procedimientos-section">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <label class="form-label" style="margin:0;">Procedimientos</label>
                                <button type="button" class="btn-agregar-proc" id="btnAgregarProcPlan">
                                    <i class="ri-add-line"></i> Agregar procedimiento
                                </button>
                            </div>
 
                            <!-- Fila para agregar procedimiento -->
                            <div id="rowAgregarProc" class="proc-add-row" style="display:none;">
                                <select id="procSelect" class="form-select proc-select">
                                    <option value="">Seleccionar procedimiento...</option>
                                </select>
                                <input type="number" id="procPieza" class="form-input proc-pieza"
                                    placeholder="No. pieza" min="11" max="48">
                                <input type="number" id="procDescuento" class="form-input proc-descuento"
                                    placeholder="Precio especial" step="0.01" min="0">
                                <button type="button" class="btn-confirmar-proc" id="btnConfirmarProc">
                                    <i class="ri-check-line"></i>
                                </button>
                                <button type="button" class="btn-cancelar-proc" id="btnCancelarProc">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
 
                            <!-- Lista de procedimientos agregados -->
                            <table class="plan-table" id="tablaProcsPlan">
                                <thead>
                                    <tr>
                                        <th>PROCEDIMIENTO</th>
                                        <th>PIEZA</th>
                                        <th>PRECIO BASE</th>
                                        <th>PRECIO ESPECIAL</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="bodyProcsPlan">
                                    <tr id="rowSinProcs">
                                        <td colspan="5" style="text-align:center; color:#adb5bd; padding:16px;">
                                            Sin procedimientos agregados
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="text-align:right; font-weight:600; padding:8px 12px;">
                                            Total estimado:
                                        </td>
                                        <td id="totalPlan" style="font-weight:700; color:#20a89e; padding:8px 12px;">
                                            $0.00
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
 
                        <!-- Botones del formulario -->
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                            <button type="button" class="btn-modal-cancel" id="btnCancelarPlan">Cancelar</button>
                            <button type="button" class="btn-modal-save" id="btnGuardarPlan">
                                <i class="ri-save-line"></i> Guardar plan
                            </button>
                        </div>
                    </div>
                </div>
 
                <!-- Lista de planes existentes -->
                <div id="listaPlanesContainer">
                    <div id="planesLoading" style="text-align:center; padding:30px; color:#adb5bd; display:none;">
                        <i class="ri-loader-4-line" style="font-size:24px;"></i>
                        <p>Cargando planes...</p>
                    </div>
                    <div id="planesSinDatos" style="text-align:center; padding:30px; color:#adb5bd;">
                        <i class="ri-file-list-3-line" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                        <p>Sin planes de tratamiento registrados</p>
                    </div>
                </div>
            </div>
        </div><!-- /#tabPlanes -->
 
        <!-- Tab 5 odontograma -->
        <div id="tabOdontograma" class="modal-tab-content">
            <div id="app-odontograma">
 
                <!-- Toolbar especialista -->
                <div class="odonto-toolbar">
                    <div class="odonto-toolbar-grupo">
                        <label class="campo-label">
                            Especialista responsable <span class="required">*</span>
                        </label>
                        <select id="odontEspecialista" class="form-select odonto-esp-select">
                            <option value="">Seleccionar especialista</option>
                        </select>
                    </div>
                </div>
 
                <!-- Cargando -->
                <div v-if="cargando" class="odonto-cargando">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    Cargando odontograma...
                </div>
 
                <div v-else class="odonto-wrapper">
 
                    <!-- PANEL IZQUIERDO: Arcadas -->
                    <div class="arcadas-panel">
                        <p class="arcadas-panel-sub">
                            Haz clic en una pieza dental para ver su historial o registrar un avance.
                        </p>
 
                        <div class="arcada-section">
                            <p class="arcada-label">Arcada superior</p>
                            <div class="arcada-row arcada-superior">
                                <div v-for="pieza in arcadaSuperior" :key="pieza.numero" class="diente"
                                    @click="seleccionarDiente(pieza)">
                                    <div class="diente-icon" :class="[
                                        `estado-${estadoDiente(pieza.numero)}`,
                                        { activo: dienteActivo?.numero === pieza.numero }
                                    ]">
                                        <img :src="pieza.icono" :alt="pieza.nombre"
                                            style="width:18px; height:18px; object-fit:contain;">
                                        <span v-if="registros[pieza.numero]?.length" class="diente-badge">
                                            {{ registros[pieza.numero].length }}
                                        </span>
                                    </div>
                                    <span class="diente-num">{{ pieza.numero }}</span>
                                </div>
                            </div>
                        </div>
 
                        <div class="arcada-divider">Maxilar · Mandíbula</div>
 
                        <div class="arcada-section">
                            <p class="arcada-label">Arcada inferior</p>
                            <div class="arcada-row arcada-inferior">
                                <div v-for="pieza in arcadaInferior" :key="pieza.numero" class="diente"
                                    @click="seleccionarDiente(pieza)">
                                    <div class="diente-icon" :class="[
                                        `estado-${estadoDiente(pieza.numero)}`,
                                        { activo: dienteActivo?.numero === pieza.numero }
                                    ]">
                                        <img :src="pieza.icono" :alt="pieza.nombre"
                                            style="width:18px; height:18px; object-fit:contain;">
                                        <span v-if="registros[pieza.numero]?.length" class="diente-badge">
                                            {{ registros[pieza.numero].length }}
                                        </span>
                                    </div>
                                    <span class="diente-num">{{ pieza.numero }}</span>
                                </div>
                            </div>
                        </div>
 
                        <div class="leyenda">
                            <div class="leyenda-item">
                                <div class="leyenda-dot sano"></div>Sin registro
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-dot anomalia"></div>En proceso
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-dot atencion"></div>Pendiente
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-dot tratado"></div>Tratado
                            </div>
                        </div>
                    </div><!-- /.arcadas-panel -->
 
                    <!-- PANEL DERECHO -->
                    <div class="odonto-form-panel">
 
                        <transition name="slide-fade">
                            <div v-if="!dienteActivo" class="odonto-panel-empty">
                                <div class="odonto-panel-empty-icon"><i class="ri-tooth-line"></i></div>
                                <p>Selecciona una pieza dental para ver su historial o registrar un nuevo avance</p>
                            </div>
                        </transition>
 
                        <transition name="slide-fade">
                            <div v-if="dienteActivo" class="odonto-panel-active">
 
                                <!-- Header -->
                                <div class="odonto-panel-header">
                                    <div class="odonto-panel-header-icon">
                                        <img :src="dienteActivo.icono" :alt="dienteActivo.nombre"
                                            style="width:22px; height:22px; object-fit:contain;">
                                    </div>
                                    <div class="odonto-panel-header-info">
                                        <h3>Pieza {{ dienteActivo.numero }}</h3>
                                        <p>{{ dienteActivo.nombre }} · {{ dienteActivo.arcada }}</p>
                                    </div>
                                </div>
 
                                <!-- Cuerpo -->
                                <div class="odonto-panel-body">
 
                                    <!-- ── Registros existentes ── -->
                                    <div v-if="registrosDiente.length" class="registros-previos">
                                        <p class="registros-titulo">
                                            Registros ({{ registrosDiente.length }})
                                        </p>
 
                                        <div v-for="(reg, idx) in registrosDiente" :key="reg.id_odontograma ?? idx"
                                            class="registro-item" :class="{ 'registro-pendiente': reg._pendiente }">
 
                                            <!-- Fila superior: anomalía + estatus + acciones -->
                                            <div class="registro-item-top">
                                                <span class="registro-anomalia">
                                                    {{ reg.nombre_anomalia }}
                                                </span>
                                                <div style="display:flex; gap:4px; align-items:center; flex-wrap:wrap;">
 
                                                    <!-- Badge de estatus (cuando no está editando) -->
                                                    <span v-if="!estaEditando(reg.id_odontograma)"
                                                        class="registro-estatus"
                                                        :class="(reg.estatus_hallazgo ?? '').toLowerCase().replace(/\s+/g,'-')">
                                                        {{ reg.estatus_hallazgo }}
                                                    </span>
 
                                                    <!-- Select inline de edición de estatus -->
                                                    <template
                                                        v-if="!reg._pendiente && estaEditando(reg.id_odontograma)">
                                                        <select :id="`odontEditarEstatus_${reg.id_odontograma}`"
                                                            class="campo-select"
                                                            style="font-size:12px; padding:4px 6px; min-width:120px; max-width:160px;">
                                                        </select>
                                                        <!-- Confirmar -->
                                                        <button class="btn-confirmar-proc"
                                                            @click="guardarEstatus(reg.id_odontograma)"
                                                            title="Guardar estatus">
                                                            <i class="ri-check-line"></i>
                                                        </button>
                                                        <!-- Cancelar edición -->
                                                        <button class="btn-cancelar-proc"
                                                            @click="toggleEditarEstatus(reg.id_odontograma, reg.id_estatus_hallazgo)"
                                                            title="Cancelar">
                                                            <i class="ri-close-line"></i>
                                                        </button>
                                                    </template>
 
                                                    <!-- Botón editar estatus (lápiz) -->
                                                    <button v-if="!reg._pendiente && !estaEditando(reg.id_odontograma)"
                                                        class="btn-eliminar-registro"
                                                        style="color:#20a89e; min-width:28px; min-height:28px;"
                                                        @click="toggleEditarEstatus(reg.id_odontograma, reg.id_estatus_hallazgo)"
                                                        title="Editar estatus">
                                                        <i class="fas fa-pen" style="font-size:12px;"></i>
                                                    </button>
 
                                                    <!-- Botón eliminar -->
                                                    <button v-if="!reg._pendiente && !estaEditando(reg.id_odontograma)"
                                                        class="btn-eliminar-registro"
                                                        style="min-width:28px; min-height:28px;"
                                                        @click="eliminarRegistro(idx)"
                                                        title="Eliminar registro">
                                                        <i class="fas fa-times" style="font-size:12px;"></i>
                                                    </button>
 
                                                    <!-- Spinner pendiente -->
                                                    <i v-if="reg._pendiente" class="fas fa-circle-notch fa-spin"
                                                        style="font-size:11px; color:#adb5bd;"></i>
                                                </div>
                                            </div>
 
                                            <!-- Cara(s) -->
                                            <div class="registro-cara" v-if="reg.cara">
                                                <i class="fas fa-map-marker-alt"
                                                    style="font-size:9px; margin-right:3px;"></i>
                                                Cara(s): {{ reg.cara }}
                                            </div>
 
                                            <!-- Procedimiento -->
                                            <div class="registro-procedimiento" v-if="reg.nombre_procedimiento &&
                                                      reg.nombre_procedimiento !== 'Sin procedimiento asignado'">
                                                <i class="fas fa-stethoscope"
                                                    style="font-size:9px; margin-right:3px;"></i>
                                                {{ reg.nombre_procedimiento }}
                                            </div>
 
                                            <!-- Especialista y fecha -->
                                            <div class="registro-meta" v-if="reg.nombre_especialista">
                                                <i class="fas fa-user-md" style="font-size:9px; margin-right:3px;"></i>
                                                {{ reg.nombre_especialista }}
                                                <span v-if="reg.fecha_cita" style="margin-left:6px; color:#adb5bd;">
                                                    · {{ reg.fecha_cita }}
                                                </span>
                                            </div>
 
                                        </div><!-- /.registro-item -->
                                    </div><!-- /.registros-previos -->
 
                                    <!-- ── Nuevo registro ── -->
                                    <p class="nuevo-registro-titulo">
                                        <i class="fas fa-plus" style="font-size:9px;"></i>
                                        Nuevo registro
                                    </p>
 
                                    <div class="campo-grupo">
                                        <label class="campo-label">Anomalía / Diagnóstico *</label>
                                        <select id="odontAnomalia" class="campo-select">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
 
                                    <div class="campo-grupo">
                                        <label class="campo-label">Cara(s) afectada(s) *</label>
                                        <div id="odontCarasGrid" class="caras-grid"></div>
                                    </div>
 
                                    <div class="campo-grupo">
                                        <label class="campo-label">Procedimiento *</label>
                                        <select id="odontProc" class="campo-select">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
 
                                    <div class="campo-grupo">
                                        <label class="campo-label">Estatus *</label>
                                        <select id="odontEstatus" class="campo-select">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
 
                                </div><!-- /.odonto-panel-body -->
 
                                <div class="odonto-panel-footer">
                                    <button class="btn-odonto-cancelar" @click="cancelar">
                                        Cancelar
                                    </button>
                                    <button class="btn-odonto-guardar" @click="guardarRegistro">
                                        <i class="fas fa-save" style="font-size:11px; margin-right:4px;"></i>
                                        Guardar
                                    </button>
                                </div>
 
                            </div><!-- /.odonto-panel-active -->
                        </transition>
 
                    </div><!-- /.odonto-form-panel -->
 
                </div><!-- /.odonto-wrapper -->
 
                <!-- Notificación flotante -->
                <transition name="notif">
                    <div v-if="notif.visible" class="odonto-notif" :class="`odonto-notif--${notif.tipo}`">
                        <i :class="{
                            'fas fa-check-circle':         notif.tipo === 'success',
                            'fas fa-exclamation-circle':   notif.tipo === 'error',
                            'fas fa-circle-notch fa-spin': notif.tipo === 'info'
                        }"></i>
                        {{ notif.texto }}
                    </div>
                </transition>
 
            </div><!-- /#app-odontograma -->
        </div><!-- /#tabOdontograma -->
 
        <!-- Tab 6 archivos medicos -->
        <div id="tabArchivos" class="modal-tab-content">
            <div class="modal-form">
                <!-- Tabla de archivos -->
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th>RADIOGRAFÍAS / FOTOGRAFÍAS</th>
                            <th>FECHA</th>
                        </tr>
                    </thead>
                    <tbody id="listaArchivos">
                        <tr>
                            <td><a href="#" class="archivo-link">RADIOGRAFIA_CONSULTA_PRIMERA_VEZ_455.jpg</a></td>
                            <td>01/07/2025</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
 
    <!-- Footer -->
    <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="cerrarModal('<?php echo $modal_id; ?>')">
            Cancelar
        </button>
        <button type="button" class="btn-modal-save" id="btnGuardarPaciente">
            Guardar cambios
        </button>
    </div>
</div>
 
 
<!-- JavaScript del modal, lógica se pasa a pacientes.js -->
<script>
    window.addEventListener('load', function () {
 
        const _cambiarTabOrig = window.cambiarTab;
        const _cerrarModalOrig = window.cerrarModal;
 
        // ── cambiarTab ────────────────────────────────────────────────────
        window.cambiarTab = function (modalId, tabId) {
            if (typeof _cambiarTabOrig === 'function') _cambiarTabOrig(modalId, tabId);
 
            if (tabId === 'tabOdontograma') {
                const num = document.getElementById('formPaciente')?.dataset?.numeroPaciente;
                if (num) odontogramaController.cargar(num);
            }
 
            if (tabId === 'tabPlanes') {
                const num = document.getElementById('formPaciente')?.dataset?.numeroPaciente;
                if (num) planesController.cargar(num);
            }
        };
 
        // ── cerrarModal ───────────────────────────────────────────────────
        // Solo limpiar odontograma, no interferir con el cierre normal
        window.cerrarModal = function (modalId) {
            try { odontogramaController.limpiar(); } catch (e) { }
            if (typeof _cerrarModalOrig === 'function') _cerrarModalOrig(modalId);
        };
 
    });
</script>