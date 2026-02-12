/*Modal Reutilizable con Tabs*/

const Modal = {
    //CONFIGURACIÓN
    currentModal: null,
    
    //ABRIR MODAL   
    /**
     * Abrir modal
     * @param {string} modalId
     */
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        const overlay = document.getElementById(modalId + '-overlay');
        
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }
        
        //Mostra overlay y modal
        if (overlay) {
            overlay.classList.add('active');
        }
        
        modal.classList.add('active');
        this.currentModal = modalId;
        
        //Previene scroll del body
        document.body.style.overflow = 'hidden';
        
        console.log('Modal abierto:', modalId);
    },
    
    //CERRAR MODAL
    
    /**
     * Cerrar modal
     * @param {string} modalId
     */
    close: function(modalId = null) {
        const id = modalId || this.currentModal;
        
        if (!id) {
            return;
        }
        
        const modal = document.getElementById(id);
        const overlay = document.getElementById(id + '-overlay');
        
        if (modal) {
            modal.classList.remove('active');
        }
        
        if (overlay) {
            overlay.classList.remove('active');
        }
        
        //Restaura scroll del body
        document.body.style.overflow = '';
        
        this.currentModal = null;
        
        console.log('Modal cerrado:', id);
    },
    
    //TABS
    
    /**
     * Cambiar tab activo
     * @param {string} modalId
     * @param {string} tabId
     */
    switchTab: function(modalId, tabId) {
        // Desactivar todos los demás tabs
        const tabs = document.querySelectorAll(`#${modalId} .modal-tab`);
        tabs.forEach(tab => tab.classList.remove('active'));
        
        //Desactivar todos los contenidos
        const contents = document.querySelectorAll(`#${modalId} .modal-tab-content`);
        contents.forEach(content => content.classList.remove('active'));
        
        //Activar tab seleccionado
        const selectedTab = document.querySelector(`#${modalId} .modal-tab[data-tab="${tabId}"]`);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        //Activar contenido seleccionado
        const selectedContent = document.getElementById(tabId);
        if (selectedContent) {
            selectedContent.classList.add('active');
        }
        
        console.log('Tab cambiado:', tabId);
    },
    
    //CARGAR DATOS 
    /**
     * Cargar datos en el modal
     * @param {string} modalId - ID del modal
     * @param {object} data - Datos a cargar
     */
    loadData: function(modalId, data) {
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }
        
        //Llenar campos del formulario
        Object.keys(data).forEach(key => {
            const field = modal.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key];
                } else if (field.type === 'radio') {
                    const radio = modal.querySelector(`[name="${key}"][value="${data[key]}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
                } else {
                    field.value = data[key];
                }
            }
        });
        
        console.log('Datos cargados en modal:', modalId);
    },
    
    //MODO VER
    
    /**
     * Establecer modo lectura
     * @param {string} modalId
     * @param {boolean} readOnly --->true, deshabilita los campos
     */
    setReadOnly: function(modalId, readOnly = true) {
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }
        
        //Deshabilita/habilita todas las entradas del formulario
        const inputs = modal.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = readOnly;
        });
        
        //Oculta/mostra botón de guardar
        const saveBtn = modal.querySelector('.btn-modal-save');
        if (saveBtn) {
            saveBtn.style.display = readOnly ? 'none' : 'block';
        }
        
        console.log('Modo solo lectura:', readOnly);
    },
    
    //LIMPIAR FORMULARIO
    
    /**
     * Limpiar todos los campos del modal
     * @param {string} modalId
     */
    clear: function(modalId) {
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }
        
        //Limpia inputs
        const inputs = modal.querySelectorAll('input:not([type="radio"]):not([type="checkbox"])');
        inputs.forEach(input => {
            input.value = '';
        });
        
        //Desmarca checkboxes
        const checkboxes = modal.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        //Restaura selects-
        const selects = modal.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });
        
        //Limpiar textareas
        const textareas = modal.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.value = '';
        });
        
        console.log('Modal limpiado:', modalId);
    },
    
    //INICIALIZACIÓN
    
    /**
     * Inicializar funcionalidades del modal
     */
    init: function() {
        console.log('Inicializando Modal...');
        
        //Cierra modal al hacer clic en overlay
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.close();
            }
        });
        
        //Cerrar modal con tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.currentModal) {
                this.close();
            }
        });
        
        console.log('Modal inicializado correctamente');
    }
};

//FUNCIONES GLOBALES

/**
 * Abre modal
 */
function abrirModal(modalId) {
    Modal.open(modalId);
}

/**
 * Cierra modal
 */
function cerrarModal(modalId = null) {
    Modal.close(modalId);
}

/**
 * Cambiaa tab
 */
function cambiarTab(modalId, tabId) {
    Modal.switchTab(modalId, tabId);
}

/**
 * Abrir modal en modo lectura boton VER
 */
function verEnModal(modalId, data) {
    Modal.clear(modalId);
    Modal.loadData(modalId, data);
    Modal.setReadOnly(modalId, true);
    Modal.open(modalId);
}

/**
 * Abrir modal en modo edición boton EDITAR
 */
function editarEnModal(modalId, data) {
    Modal.clear(modalId);
    Modal.loadData(modalId, data);
    Modal.setReadOnly(modalId, false);
    Modal.open(modalId);
}

/**
 * Abrir modal nuevo sin datos
 */
function nuevoEnModal(modalId) {
    Modal.clear(modalId);
    Modal.setReadOnly(modalId, false);
    Modal.open(modalId);
}

//INICIALIZACIÓN AUTOMÁTICA

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Modal.init();
    });
} else {
    Modal.init();
}