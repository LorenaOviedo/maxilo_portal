/**
 * MODELO DEL ODONTOGRAMA
 *
 * Responsabilidad: datos, catálogos y estructura de piezas dentales.
 * No conoce la vista ni el controlador.
 */

const odontogramaModel = {
 
  // ─────────────────────────────────────────────────────────────────────────
  // CATÁLOGOS — se pueblan al llamar cargarCatalogos()
  // Cada item: { id: int, nombre: string }
  // ─────────────────────────────────────────────────────────────────────────
 
  catalogoAnomalias:      [],
  catalogoCaras:          [],
  catalogoProcedimientos: [],  // items también tienen precio_base
  catalogoEstatus:        [],
  // catalogoEspecialistas eliminado — ya no se usa en el odontograma
 
  _cargandoCatalogos: false,
  _catalogosCargados: false,
 
  /**
   * Descarga los catálogos desde la API y los almacena en este modelo.
   * Es idempotente: si ya se cargaron no vuelve a hacer la petición.
   * @returns {Promise<boolean>}
   */
  async cargarCatalogos() {
    if (this._catalogosCargados)  return true;
    if (this._cargandoCatalogos)  return false;
 
    this._cargandoCatalogos = true;
    try {
      const res = await fetch(
        'ajax/api.php?modulo=odontograma&accion=get_catalogos_odontograma'
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
 
      const data = await res.json();
      if (!data.success) throw new Error(data.message);
 
      this.catalogoAnomalias      = data.anomalias      ?? [];
      this.catalogoCaras          = data.caras          ?? [];
      this.catalogoProcedimientos = data.procedimientos ?? [];
      this.catalogoEstatus        = data.estatus        ?? [];
      // data.especialistas ya no existe en la respuesta
 
      this._catalogosCargados = true;
      return true;
    } catch (err) {
      console.error('odontogramaModel.cargarCatalogos:', err);
      return false;
    } finally {
      this._cargandoCatalogos = false;
    }
  },
 
  // ─────────────────────────────────────────────────────────────────────────
  // NOMBRES FDI
  // ─────────────────────────────────────────────────────────────────────────
 
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
 
  // ─────────────────────────────────────────────────────────────────────────
  // ARCADAS
  // ─────────────────────────────────────────────────────────────────────────
 
  numerosSuperior: [18,17,16,15,14,13,12,11, 21,22,23,24,25,26,27,28],
  numerosInferior: [48,47,46,45,44,43,42,41, 31,32,33,34,35,36,37,38],
 
  getNombrePieza(numero) {
    return this.nombresPiezas[numero] || `Pieza ${numero}`;
  },
 
  construirArcada(numeros, arcada) {
    return numeros.map(numero => ({
      numero,
      nombre:      this.getNombrePieza(numero),
      arcada,
      icono:       this._iconoPorNumero(numero),
      iconoActivo: this._iconoPorNumero(numero, true),
    }));
  },
 
  _iconoPorNumero(numero, activo = false) {
    const base = (typeof ASSETS_URL !== 'undefined') ? ASSETS_URL : '';
    const suf  = activo ? '_clear' : '';
    if ([18,28,38,48].includes(numero))                   return `${base}3molar${suf}.png`;
    if ([16,17,26,27,36,37,46,47].includes(numero))       return `${base}molar${suf}.png`;
    if ([14,15,24,25,34,35,44,45].includes(numero))       return `${base}premolar${suf}.png`;
    if ([13,23,33,43].includes(numero))                   return `${base}canino${suf}.png`;
    return `${base}incisivo${suf}.png`;
  },
 
  // ─────────────────────────────────────────────────────────────────────────
  // FORMULARIO VACÍO
  // ─────────────────────────────────────────────────────────────────────────
 
  registroVacio() {
    return {
      id_anomalia:      null,   // int   — FK AnomaliasDentales
      id_caras:         [],     // int[] — FK CarasDentales (múltiple)
      id_procedimiento: null,   // int   — FK Procedimientos (obligatorio)
      id_estatus:       null,   // int   — FK EstadosHallazgos
    };
  },
 
  // ─────────────────────────────────────────────────────────────────────────
  // HELPERS de presentación
  // ─────────────────────────────────────────────────────────────────────────
 
  nombreAnomalia(id) {
    return this.catalogoAnomalias.find(a => +a.id === +id)?.nombre
           ?? `Anomalía #${id}`;
  },
 
  nombresCaras(ids) {
    return (ids ?? [])
      .map(id => this.catalogoCaras.find(c => +c.id === +id)?.nombre ?? `Cara #${id}`)
      .join(', ');
  },
 
  nombreProcedimiento(id) {
    if (!id) return null;
    return this.catalogoProcedimientos.find(p => +p.id === +id)?.nombre
           ?? `Procedimiento #${id}`;
  },
 
  nombreEstatus(id) {
    if (!id) return 'Pendiente';
    return this.catalogoEstatus.find(e => +e.id === +id)?.nombre
           ?? `Estatus #${id}`;
  },
  // nombreEspecialista() eliminado — ya no se usa
};