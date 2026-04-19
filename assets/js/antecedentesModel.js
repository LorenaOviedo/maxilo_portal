/**
 * MODELO PARA SUBMODULO DE ANTECEDENTES
 *
 * Responsabilidad: datos del catálogo de antecedentes.
 * No conoce la vista ni el controlador..
 * Se carga antes que AntecedentesController.js
 */

const antecedentesModel = {

    // Cache del catálogo cargado desde la API
    // Estructura: [ { id_antecedente, implica_alerta_medica, tipo, nombre_antecedente } ]
    catalogo: [],

    /**
     * Agrupar el catálogo por campo `tipo`
     * @returns { [tipo: string]: Array }
     */
    agruparPorTipo() {
        const grupos = {};
        this.catalogo.forEach(ant => {
            if (!grupos[ant.tipo]) grupos[ant.tipo] = [];
            grupos[ant.tipo].push(ant);
        });
        return grupos;
    },

    /**
     * Verificar si un antecedente implica alerta médica
     * @param {number} id
     * @returns {boolean}
     */
    implicaAlerta(id) {
        const ant = this.catalogo.find(a => a.id_antecedente === parseInt(id));
        return ant ? parseInt(ant.implica_alerta_medica) === 1 : false;
    },
};