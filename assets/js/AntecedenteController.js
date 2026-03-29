/**
 * CONTROLADOR - AntecedentesController.js
 *
 * Responsabilidad: estado reactivo Vue para el componente
 * de chips de antecedentes médicos.
 
 *
 * Depende de: vue.global.js, antecedentesModel.js
 *
 * USO:
 *   antecedentesController.montar()            :al abrir el modal
 *   antecedentesController.cargar(ids)         :poblar seleccionados
 *   antecedentesController.obtenerIds()        :recolectar al guardar
 *   antecedentesController.limpiar()           :al nuevo paciente
 *   antecedentesController.desmontar()         :al cerrar el modal
 *   antecedentesController.setReadonly(bool)   :modo ver (sin toggle)
 */

const antecedentesController = {

    _appInstance: null,
    _seleccionados: null,  // ref Vue expuesta para acceso externo
    _readonly: false,

    // ── Montar la app Vue ─────────────────────────────────────────
    montar() {
        if (this._appInstance) return;

        const { createApp, ref, computed } = Vue;
        const self = this;

        this._appInstance = createApp({
            setup() {

                // IDs de antecedentes seleccionados (array reactivo)
                const seleccionados = ref([]);

                // Grupos del catálogo { tipo: [antecedentes] }
                const grupos = computed(() => antecedentesModel.agruparPorTipo());

                // Verificar si un chip está activo
                function estaActivo(id) {
                    return seleccionados.value.includes(parseInt(id));
                }

                // Toggle de chip
                function toggleChip(id) {
                    if (self._readonly) return;
                    id = parseInt(id);
                    const idx = seleccionados.value.indexOf(id);
                    if (idx === -1) {
                        seleccionados.value.push(id);
                    } else {
                        seleccionados.value.splice(idx, 1);
                    }
                }

                // Exponer ref para acceso externo desde pacientes.js
                self._seleccionados = seleccionados;

                return {
                    grupos,
                    seleccionados,
                    estaActivo,
                    toggleChip,
                    // Exponer para la vista
                    implicaAlerta: (id) => antecedentesModel.implicaAlerta(id),
                    readonly: computed(() => self._readonly),
                };
            },

            // Template inline — mismo patrón que odontograma
            template: `
                <div class="contenedor-antecedentes">
                    <div v-if="!Object.keys(grupos).length" class="antecedentes-cargando">
                        Cargando antecedentes...
                    </div>
                    <div v-for="(items, tipo) in grupos" :key="tipo" class="antecedentes-grupo">
                        <p class="antecedentes-grupo-titulo">{{ tipo }}</p>
                        <div class="chips-container">
                            <div
                                v-for="ant in items"
                                :key="ant.id_antecedente"
                                :class="[
                                    'chip',
                                    { 'chip--activo': estaActivo(ant.id_antecedente) },
                                    { 'chip--alerta': implicaAlerta(ant.id_antecedente) },
                                    { 'chip--readonly': readonly }
                                ]"
                                :title="implicaAlerta(ant.id_antecedente) ? 'Implica alerta médica' : ''"
                                @click="toggleChip(ant.id_antecedente)"
                            >
                                <i v-if="implicaAlerta(ant.id_antecedente)"
                                   class="ri-alert-line chip-alerta-icon"></i>
                                {{ ant.nombre_antecedente }}
                            </div>
                        </div>
                    </div>
                </div>
            `,
        }).mount('#app-antecedentes');
    },

    // ── Cargar IDs seleccionados (al abrir modal con paciente) ────
    cargar(ids = []) {
        if (!this._seleccionados) return;
        this._seleccionados.value = ids.map(id => parseInt(id));
    },

    // ── Obtener IDs seleccionados (al guardar) ────────────────────
    obtenerIds() {
        if (!this._seleccionados) return [];
        return [...this._seleccionados.value];
    },

    // ── Limpiar selección (nuevo paciente) ────────────────────────
    limpiar() {
        if (this._seleccionados) {
            this._seleccionados.value = [];
        }
    },

    // ── Modo readonly (tab Ver) ───────────────────────────────────
    setReadonly(bool) {
        this._readonly = bool;
    },

    // ── Desmontar (al cerrar modal) ───────────────────────────────
    desmontar() {
        if (this._appInstance) {
            this._appInstance.unmount();
            this._appInstance = null;
            this._seleccionados = null;
        }
        this._readonly = false;
    },
};