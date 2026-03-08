<?php
/* MODAL DE PACIENTE REUTILIZABLE VER/EDITAR */
$modal_id = 'modalPaciente';
?>

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
            Información de<br>Contacto
        </button>
        <button class="modal-tab" data-tab="tabHistorial"
            onclick="cambiarTab('<?php echo $modal_id; ?>', 'tabHistorial')">
            Historial Clínico
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
                        <label class="form-label">Número de paciente</label>
                        <input type="text" name="id" class="form-input" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre(s)</label>
                        <input type="text" name="nombre" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido paterno</label>
                        <input type="text" name="apellido_paterno" class="form-input">
                    </div>
                </div>


                <!-- F2 apellido materno, fecha de nacimiento -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Apellido materno</label>
                        <input type="text" name="apellido_materno" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-input">
                    </div>
                </div>

                <!-- F3 genero, calle y número -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Género</label>
                        <select name="genero" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calle y número</label>
                        <input type="text" name="calle" class="form-input">
                    </div>
                </div>

                <!-- F4 codigo postal, colonia -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Código postal</label>
                        <input type="text" name="codigo_postal" class="form-input" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Colonia</label>
                        <input type="text" name="colonia" class="form-input">
                    </div>
                </div>

                <!-- F5 estado, ciudad, país -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <input type="text" name="estado" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">País</label>
                        <input type="text" name="pais" class="form-input" value="México">
                    </div>
                </div>
            </form>
        </div>

        <!-- Tab 2 Contacto -->
        <div id="tabContacto" class="modal-tab-content">
            <form class="modal-form" id="formContacto">

                <div class="form-card">

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
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-input" maxlength="10"
                                placeholder="10 dígitos">
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
                            <input type="text" name="relacion" class="form-input"
                                placeholder="Ej: Madre, Hermano, Esposo/a...">
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
                </div>

            </form>
        </div>

        <!-- Tab 3 historial clínico -->
        <div id="tabHistorial" class="modal-tab-content">
            <div class="modal-form">
                <!-- Tipo de sangre y antecedentes médicos -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tipo de sangre</label>
                        <input type="text" name="tipo_sangre" class="form-input" placeholder="Ej: A+, B-, O+...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Antecedentes médicos</label>
                        <input type="text" name="antecedentes_medicos" class="form-input">
                    </div>
                </div>

                <!-- Medicamentos actuales -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Medicamentos actuales</label>
                        <textarea name="medicamentos" class="form-input form-textarea" rows="3"
                            placeholder="Lista los medicamentos que toma actualmente..."></textarea>
                    </div>
                </div>

                <!-- Cirugías previas -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Cirugías previas</label>
                        <textarea name="cirugias_previas" class="form-input form-textarea" rows="3"
                            placeholder="Describe cirugías o procedimientos previos..."></textarea>
                    </div>
                </div>

                <!-- Notas -->
                <div class="form-row cols-1">
                    <div class="form-group">
                        <label class="form-label">Notas</label>
                        <textarea name="notas_historial" class="form-input form-textarea" rows="3"
                            placeholder="Notas adicionales del historial clínico..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4 planes de tratamiento -->
        <div id="tabPlanes" class="modal-tab-content">
            <div class="modal-form">
                <!-- Botón agregar nuevo plan -->
                <div class="tab-toolbar">
                    <button type="button" class="btn-modal-add" onclick="agregarPlan()">
                        Agregar nuevo <i class="fas fa-plus"></i>
                    </button>
                </div>

                <!-- Contenedor de planes (se llena dinámicamente) -->
                <div id="listaPlanesContainer">
                    <!-- Ejemplo de tarjeta de plan -->
                    <div class="plan-card form-card">
                        <div class="plan-card-header">
                            <div class="plan-card-info">
                                <span class="plan-nombre">Plan de Ortodoncia</span>
                                <span class="plan-descripcion">Tratamiento de ortodoncia con brackets metálicos</span>
                                <span class="plan-meta"><strong>Creado:</strong> 08/08/2025</span>
                                <span class="plan-meta"><strong>Estatus:</strong> En Progreso</span>
                            </div>
                            <div class="plan-card-totales">
                                <span><strong>Costo total:</strong> $17700.00</span>
                                <span><strong>Pagado:</strong> $17000.00</span>
                            </div>
                        </div>

                        <!-- Tabla de procedimientos del plan -->
                        <table class="plan-table">
                            <thead>
                                <tr>
                                    <th>PROCEDIMIENTO</th>
                                    <th>FECHA</th>
                                    <th>COSTO</th>
                                    <th>ESTATUS</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ESTUDIO RADIOGRAFÍA CEFALICA LATERAL</td>
                                    <td>07/10/2025</td>
                                    <td>$2000.00</td>
                                    <td><span class="badge-estatus completado">COMPLETADO</span></td>
                                    <td class="acciones-cell">
                                        <button class="btn-accion editar" onclick="editarProcedimientoPlan(1)"><i
                                                class="ri-edit-box-line"></i></button>
                                        <button class="btn-accion eliminar" onclick="eliminarProcedimientoPlan(1)"><i
                                                class="ri-delete-bin-6-line"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>CIMENTACIÓN DE BRACKETS</td>
                                    <td>07/10/2025</td>
                                    <td>$14000.00</td>
                                    <td><span class="badge-estatus completado">COMPLETADO</span></td>
                                    <td class="acciones-cell">
                                        <button class="btn-accion editar" onclick="editarProcedimientoPlan(2)"><i
                                                class="ri-edit-box-line"></i></button>
                                        <button class="btn-accion eliminar" onclick="eliminarProcedimientoPlan(2)"><i
                                                class="ri-delete-bin-6-line"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>AJUSTE MENSUAL</td>
                                    <td>08/12/2025</td>
                                    <td>$700.00</td>
                                    <td><span class="badge-estatus pendiente">PENDIENTE</span></td>
                                    <td class="acciones-cell">
                                        <button class="btn-accion editar" onclick="editarProcedimientoPlan(3)"><i
                                                class="ri-edit-box-line"></i></button>
                                        <button class="btn-accion eliminar" onclick="eliminarProcedimientoPlan(3)"><i
                                                class="ri-delete-bin-6-line"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div><!-- /.plan-card -->
                </div><!-- /#listaPlanesContainer -->

                <!-- Botón imprimir -->
                <div class="tab-footer-actions">
                    <button type="button" class="btn-imprimir" onclick="imprimirPlanes()">
                        Imprimir
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab 5 odontograma -->
        <div id="tabOdontograma" class="modal-tab-content">
            <div id="app-odontograma">
                <!-- Estado de carga inicial -->
                <div v-if="cargando" class="odonto-cargando">
                    <i class="fas fa-circle-notch"></i>
                    Cargando odontograma...
                </div>

                <!-- Contenido principal -->
                <div v-else class="odonto-wrapper">

                    <!-- PANEL IZQUIERDO: Arcadas dentales-->
                    <div class="arcadas-panel">
                        <p class="arcadas-panel-title">Odontograma</p>
                        <p class="arcadas-panel-sub">
                            Haz clic en una pieza dental para ver su historial o registrar un avance
                        </p>

                        <!-- Arcada Superior -->
                        <div class="arcada-section">
                            <p class="arcada-label">Arcada superior</p>
                            <div class="arcada-row">
                                <div v-for="pieza in arcadaSuperior" :key="pieza.numero" class="diente"
                                    @click="seleccionarDiente(pieza)">
                                    <div class="diente-icon" :class="[
                `estado-${estadoDiente(pieza.numero)}`,
                { activo: dienteActivo?.numero === pieza.numero }
              ]">
                                        {{ pieza.emoji }}
                                        <span v-if="registros[pieza.numero]?.length" class="diente-badge">
                                            {{ registros[pieza.numero].length }}
                                        </span>
                                    </div>
                                    <span class="diente-num">{{ pieza.numero }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="arcada-divider">Maxilar · Mandíbula</div>

                        <!-- Arcada Inferior -->
                        <div class="arcada-section">
                            <p class="arcada-label">Arcada inferior</p>
                            <div class="arcada-row">
                                <div v-for="pieza in arcadaInferior" :key="pieza.numero" class="diente"
                                    @click="seleccionarDiente(pieza)">
                                    <div class="diente-icon" :class="[
                `estado-${estadoDiente(pieza.numero)}`,
                { activo: dienteActivo?.numero === pieza.numero }
              ]">
                                        {{ pieza.emoji }}
                                        <span v-if="registros[pieza.numero]?.length" class="diente-badge">
                                            {{ registros[pieza.numero].length }}
                                        </span>
                                    </div>
                                    <span class="diente-num">{{ pieza.numero }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Leyenda de colores -->
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
                    </div>

                    <!--PANEL DERECHO: Formulario de registro-->
                    <div class="odonto-form-panel">

                        <!-- Sin diente seleccionado -->
                        <transition name="slide-fade">
                            <div v-if="!dienteActivo" class="odonto-panel-empty">
                                <div class="odonto-panel-empty-icon">🦷</div>
                                <p>Selecciona una pieza dental para ver su historial o registrar un nuevo avance</p>
                            </div>
                        </transition>

                        <!-- Diente seleccionado -->
                        <transition name="slide-fade">
                            <div v-if="dienteActivo" class="odonto-panel-active">

                                <!-- Header -->
                                <div class="odonto-panel-header">
                                    <div class="odonto-panel-header-icon">{{ dienteActivo.emoji }}</div>
                                    <div class="odonto-panel-header-info">
                                        <h3>Pieza {{ dienteActivo.numero }}</h3>
                                        <p>{{ dienteActivo.nombre }} · {{ dienteActivo.arcada }}</p>
                                    </div>
                                </div>

                                <!-- Cuerpo -->
                                <div class="odonto-panel-body">

                                    <!-- Registros anteriores -->
                                    <div v-if="registrosDiente.length" class="registros-previos">
                                        <p class="registros-titulo">
                                            Registros anteriores ({{ registrosDiente.length }})
                                        </p>
                                        <div v-for="(reg, idx) in registrosDiente" :key="idx" class="registro-item">
                                            <div class="registro-item-top">
                                                <span class="registro-anomalia">{{ reg.anomalia }}</span>
                                                <div style="display:flex; gap:6px; align-items:center;">
                                                    <span class="registro-estatus"
                                                        :class="reg.estatus.toLowerCase().replace(' ','-')">
                                                        {{ reg.estatus }}
                                                    </span>
                                                    <button class="btn-eliminar-registro" @click="eliminarRegistro(idx)"
                                                        title="Eliminar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="registro-cara" v-if="reg.caras.length">
                                                <i class="fas fa-map-marker-alt"
                                                    style="font-size:9px;margin-right:3px;"></i>
                                                Cara(s): {{ reg.caras.join(', ') }}
                                            </div>
                                            <div class="registro-procedimiento" v-if="reg.procedimiento">
                                                <i class="fas fa-stethoscope"
                                                    style="font-size:9px;margin-right:3px;"></i>
                                                {{ reg.procedimiento }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nuevo registro -->
                                    <p class="nuevo-registro-titulo">
                                        <i class="fas fa-plus" style="font-size:9px;"></i>
                                        Nuevo registro
                                    </p>

                                    <div class="campo-grupo">
                                        <label class="campo-label">Anomalía / Diagnóstico *</label>
                                        <select v-model="form.anomalia" class="campo-select">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="a in catalogoAnomalias" :key="a" :value="a">{{ a }}</option>
                                        </select>
                                    </div>

                                    <div class="campo-grupo">
                                        <label class="campo-label">Cara(s) dental(es) afectada(s)</label>
                                        <div class="caras-grid">
                                            <div v-for="cara in catalogoCaras" :key="cara">
                                                <input type="checkbox" :id="`cara-${cara}-${dienteActivo.numero}`"
                                                    :value="cara" v-model="form.caras" class="cara-check">
                                                <label :for="`cara-${cara}-${dienteActivo.numero}`" class="cara-label">
                                                    {{ cara }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="campo-grupo">
                                        <label class="campo-label">Tratamiento / Procedimiento</label>
                                        <select v-model="form.procedimiento" class="campo-select">
                                            <option value="">Sin procedimiento aún</option>
                                            <option v-for="p in catalogoProcedimientos" :key="p" :value="p">{{ p }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="campo-grupo">
                                        <label class="campo-label">Estatus *</label>
                                        <select v-model="form.estatus" class="campo-select">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="e in catalogoEstatus" :key="e" :value="e">{{ e }}</option>
                                        </select>
                                    </div>

                                </div><!-- /.odonto-panel-body -->

                                <!-- Footer botones -->
                                <div class="odonto-panel-footer">
                                    <button class="btn-odonto-cancelar" @click="cancelar">Cancelar</button>
                                    <button class="btn-odonto-guardar" :disabled="!formularioValido"
                                        @click="guardarRegistro">
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
                    <div v-if="notif.visible" class="odonto-notif">
                        <i class="fas fa-check-circle"></i>
                        {{ notif.texto }}
                    </div>
                </transition>
            </div><!-- /#app-odontograma -->
        </div><!-- /#tabOdontograma -->

        <!-- Tab 6 archivos medicos -->
        <div id="tabArchivos" class="modal-tab-content">
            <div class="modal-form">
                <div class="form-card">

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

                </div><!-- /.form-card -->
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

<!-- JavaScript para modal de paciente -->
<script>
// Función para ver paciente
function verPaciente(data) {
    // Actualizar número en título
    document.getElementById('modalPacienteNumero').textContent = data.id || '';

    // Cargar datos para lectura
    verEnModal('<?php echo $modal_id; ?>', data);
}

// Función para editar paciente
function editarPaciente(data) {
    // Actualizar número en título
    document.getElementById('modalPacienteNumero').textContent = data.id || '';

    // Cargar datos en edición
    editarEnModal('<?php echo $modal_id; ?>', data);
}

// Evento de guardar paciente
document.getElementById('btnGuardarPaciente')?.addEventListener('click', function() {
    const form = document.getElementById('formPaciente');
    const formData = new FormData(form);

    // Convertir a objeto
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    console.log('Datos a guardar:', data);

    // Pendiente implementación de guardado

    // Cerrar modal
    CatalogTable.showNotification('Función de guardado pendiente de implementar.....', 'info');
});
</script>
<script>
window.addEventListener('load', function() {
    const _cambiarTabOrig = window.cambiarTab;
    const _cerrarModalOrig = window.cerrarModal;

    window.cambiarTab = function(modalId, tabId) {
        if (typeof _cambiarTabOrig === 'function') _cambiarTabOrig(modalId, tabId);

        if (tabId === 'tabOdontograma') {
            const numeroPaciente = document.querySelector('#formPaciente [name="id"]')?.value || null;
            odontogramaController.montar(numeroPaciente);
        }
    };

    window.cerrarModal = function(modalId) {
        if (typeof _cerrarModalOrig === 'function') _cerrarModalOrig(modalId);
        odontogramaController.desmontar();
    };
});
</script>