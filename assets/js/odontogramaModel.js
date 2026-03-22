/**
 * MODELO - OdontogramaModel.js
 *
 * Responsabilidad: datos puros, catálogos y estructura de piezas dentales.
 * No conoce la vista ni el controlador.
 */

const odontogramaModel = {

  // ─────────────────────────────────────────
  // CATÁLOGOS (deben coincidir con la BD)
  // ─────────────────────────────────────────

  catalogoAnomalias: [
    'Caries',
    'Fractura',
    'Ausente',
    'Restauración previa',
    'Corona',
    'Endodoncia previa',
    'Enfermedad periodontal',
    'Movilidad dental',
    'Manchas / Hipoplasia',
    'Impactado / Retenido',
    'Extrusión / Intrusión',
    'Otro',
  ],

  catalogoCaras: [
    'Vestibular',
    'Palatina',
    'Mesial',
    'Oclusal',
    'Lingual',
    'General',
  ],

  catalogoProcedimientos: [
    'Obturación (resina)',
    'Obturación (amalgama)',
    'Extracción simple',
    'Extracción quirúrgica',
    'Endodoncia',
    'Corona dental',
    'Implante dental',
    'Raspado y alisado radicular',
    'Gingivectomía',
    'Blanqueamiento',
    'Sellador dental',
    'Aplicación de flúor',
    'Otro',
  ],

  catalogoEstatus: [
    'Pendiente',
    'En proceso',
    'Tratado',
  ],

  // ─────────────────────────────────────────
  // NOMBRES FDI DE PIEZAS DENTALES
  // ─────────────────────────────────────────

  nombresPiezas: {
    11: 'Inc. central sup. der.',   12: 'Inc. lateral sup. der.',
    13: 'Canino sup. der.',         14: '1er premolar sup. der.',
    15: '2do premolar sup. der.',   16: '1er molar sup. der.',
    17: '2do molar sup. der.',      18: '3er molar sup. der.',
    21: 'Inc. central sup. izq.',   22: 'Inc. lateral sup. izq.',
    23: 'Canino sup. izq.',         24: '1er premolar sup. izq.',
    25: '2do premolar sup. izq.',   26: '1er molar sup. izq.',
    27: '2do molar sup. izq.',      28: '3er molar sup. izq.',
    31: 'Inc. central inf. izq.',   32: 'Inc. lateral inf. izq.',
    33: 'Canino inf. izq.',         34: '1er premolar inf. izq.',
    35: '2do premolar inf. izq.',   36: '1er molar inf. izq.',
    37: '2do molar inf. izq.',      38: '3er molar inf. izq.',
    41: 'Inc. central inf. der.',   42: 'Inc. lateral inf. der.',
    43: 'Canino inf. der.',         44: '1er premolar inf. der.',
    45: '2do premolar inf. der.',   46: '1er molar inf. der.',
    47: '2do molar inf. der.',      48: '3er molar inf. der.',
  },

  // ─────────────────────────────────────────
  // ARCADAS (numeración FDI)
  // ─────────────────────────────────────────

  numerosSuperior: [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28],
  numerosInferior: [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38],

  // ─────────────────────────────────────────
  // MÉTODOS DE CONSTRUCCIÓN
  // ─────────────────────────────────────────

  /**
   * Retorna el nombre de una pieza por su número FDI
   * @param {number} numero
   * @returns {string}
   */
  getNombrePieza(numero) {
    return this.nombresPiezas[numero] || `Pieza ${numero}`;
  },

  /**
   * Construye el array de objetos de pieza para una arcada
   * @param {number[]} numeros
   * @param {string}   arcada  - 'Superior' | 'Inferior'
   * @returns {Array<{numero, nombre, arcada, icono}>}
   */
  construirArcada(numeros, arcada) {
    return numeros.map((numero) => ({
      numero,
      nombre: this.getNombrePieza(numero),
      arcada,
      icono: this._iconoPorNumero(numero),
    }));
  },

  /**
   * Retorna el icono según el tipo de pieza
   * @param {number} numero
   * @returns {string}
   */
  _iconoPorNumero(numero) {
    // Terceros molares (muelas del juicio)
    //if ([18, 28, 38, 48].includes(numero)) return 'ri-tooth-line';
    //return 'ri-tooth-line'; // Icono genérico para todas las piezas
    
    const base = ASSETS_URL;
    
    // Terceros molares (muelas del juicio)
    if ([18, 28, 38, 48].includes(numero)) return `${base}3molar.png`;
    
    // Molares
    if ([16, 17, 26, 27, 36, 37, 46, 47].includes(numero)) return `${base}molar.png`;
    
    // Premolares
    if ([14, 15, 24, 25, 34, 35, 44, 45].includes(numero)) return `${base}premolar.png`;
    
    // Caninos
    if ([13, 23, 33, 43].includes(numero)) return `${base}canino.png`;
    
    // Incisivos
    return `${base}incisivo.png`;
  },

  /**
   * Crea un objeto de registro vacío (estructura base)
   * @returns {{anomalia, caras, procedimiento, estatus}}
   */
  registroVacio() {
    return {
      anomalia:      '',
      caras:         [],
      procedimiento: '',
      estatus:       '',
    };
  },
};