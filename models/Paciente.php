<?php
/**
 * Modelo de Paciente
 *
 * Tablas involucradas:
 *   paciente, direcciones, codigospostales, municipios, estados,
 *   contactos, tipocontacto, contactosemergencia, tipoparentescos,
 *   historialmedico, tipossangre, antecedentemedico, antecedentemedico
 */
class Paciente
{
    private $conn;

    private const ESTATUS_ACTIVO = 1;
    private const ESTATUS_INACTIVO = 2;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ==================== LECTURA ====================

    /**
     * Obtener listado de pacientes para la tabla principal.
     * Incluye nombre completo, teléfono, email, edad y última cita.
     */
    public function getAll($filtros = [], $pagina = 1, $porPagina = 10)
    {
        $query = "
            SELECT
                p.numero_paciente,
                p.id_paciente_expediente,
                CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', IFNULL(p.apellido_materno,'')) AS nombre_completo,
                p.fecha_nacimiento,
                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE())                            AS edad,
                p.sexo,
                p.id_estatus,
                s.estatus,
                p.fecha_registro,
                -- Primer contacto tipo celular (id 1 = Celular Personal)
                MAX(CASE WHEN c.id_tipo_contacto IN (1,2,3,4,5) THEN c.valor END) AS telefono,
                -- Correo electrónico (id 6 = Correo electrónico)
                MAX(CASE WHEN c.id_tipo_contacto = 6 THEN c.valor END) AS email,
                -- Última cita
                (SELECT MAX(ci.fecha_cita)
                 FROM cita ci
                 WHERE ci.numero_paciente = p.numero_paciente) AS ultima_visita
            FROM paciente p
            INNER JOIN estatus s ON s.id_estatus = p.id_estatus
            LEFT  JOIN contactos c ON c.numero_paciente = p.numero_paciente
        ";

        $conditions = [];
        $params = [];

        if (isset($filtros['id_estatus'])) {
            $conditions[] = "p.id_estatus = :id_estatus";
            $params[':id_estatus'] = (int) $filtros['id_estatus'];
        }

        if (!empty($filtros['buscar'])) {
            $conditions[] = "(
                p.nombre                LIKE :buscar OR
                p.apellido_paterno      LIKE :buscar OR
                p.apellido_materno      LIKE :buscar OR
                p.id_paciente_expediente LIKE :buscar OR
                c.valor                 LIKE :buscar
            )";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " GROUP BY p.numero_paciente ORDER BY p.fecha_registro DESC";

        $pagina = max(1, (int) $pagina);
        $porPagina = max(1, (int) $porPagina);
        $offset = ($pagina - 1) * $porPagina;

        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $val) {
            $tipo = ($key === ':buscar') ? PDO::PARAM_STR : PDO::PARAM_INT;
            $stmt->bindValue($key, $val, $tipo);
        }

        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Paciente::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de pacientes (para paginación).
     */
    public function contarTotal($filtros = [])
    {
        $query = "
            SELECT COUNT(DISTINCT p.numero_paciente) AS total
            FROM paciente p
            INNER JOIN estatus s ON s.id_estatus = p.id_estatus
            LEFT  JOIN contactos c ON c.numero_paciente = p.numero_paciente
        ";

        $conditions = [];
        $params = [];

        if (isset($filtros['id_estatus'])) {
            $conditions[] = "p.id_estatus = :id_estatus";
            $params[':id_estatus'] = (int) $filtros['id_estatus'];
        }

        if (!empty($filtros['buscar'])) {
            $conditions[] = "(
                p.nombre                LIKE :buscar OR
                p.apellido_paterno      LIKE :buscar OR
                p.apellido_materno      LIKE :buscar OR
                p.id_paciente_expediente LIKE :buscar OR
                c.valor                 LIKE :buscar
            )";
            $params[':buscar'] = '%' . $filtros['buscar'] . '%';
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute($params);
            return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Error en Paciente::contarTotal: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener datos completos de un paciente por numero_paciente.
     * Incluye dirección, contactos y contacto de emergencia.
     */
    public function getById($id)
    {
        // Datos principales + dirección
        $query = "
            SELECT
                p.numero_paciente,
                p.id_paciente_expediente,
                p.nombre,
                p.apellido_paterno,
                p.apellido_materno,
                p.fecha_nacimiento,
                p.sexo,
                p.id_estatus,
                p.id_ocupacion,
                p.id_direccion,
                p.fecha_registro,
                s.estatus,
                -- Dirección
                d.calle,
                d.numero_exterior,
                d.numero_interior,
                cp.codigo_postal,
                cp.colonia,
                m.municipio,
                e.estado
            FROM paciente p
            INNER JOIN estatus          s  ON s.id_estatus   = p.id_estatus
            LEFT  JOIN direcciones      d  ON d.id_direccion = p.id_direccion
            LEFT  JOIN codigospostales  cp ON cp.id_cp       = d.id_cp
            LEFT  JOIN municipios       m  ON m.id_municipio = cp.id_municipio
            LEFT  JOIN estados          e  ON e.id_estado    = m.id_estado
            WHERE p.numero_paciente = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$paciente)
                return false;

            // Contactos (teléfono, email, etc.)
            $paciente['contactos'] = $this->getContactos($id);

            // Contacto de emergencia
            $paciente['contacto_emergencia'] = $this->getContactoEmergencia($id);

            // Historial médico
            $paciente['historial'] = $this->getHistorial($id);

            return $paciente;

        } catch (PDOException $e) {
            error_log("Error en Paciente::getById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener contactos de un paciente.
     */
    private function getContactos($numeroPaciente)
    {
        $query = "
            SELECT c.id_contacto, c.id_tipo_contacto, tc.tipo_contacto, c.valor
            FROM contactos c
            INNER JOIN tipocontacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
            WHERE c.numero_paciente = :id
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $numeroPaciente, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Paciente::getContactos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener contacto de emergencia de un paciente.
     */
    private function getContactoEmergencia($numeroPaciente)
    {
        $query = "
            SELECT
                ce.id_contacto_emergencia,
                ce.nombres_contacto_emergencia,
                ce.apellido_contacto_emergencia,
                ce.telefono_contacto_emergencia,
                tp.parentesco
            FROM pacientecontactosemergencia pce
            INNER JOIN contactosemergencia ce ON ce.id_contacto_emergencia = pce.id_contacto_emergencia
            INNER JOIN tipoparentescos     tp ON tp.id_tipo_parentesco     = pce.id_tipo_parentesco
            WHERE pce.numero_paciente = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $numeroPaciente, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error en Paciente::getContactoEmergencia: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener historial médico de un paciente.
     */
    private function getHistorial($numeroPaciente)
    {
        $query = "
            SELECT
                hm.id_historial,
                hm.notas,
                ts.tipo_sangre,
                GROUP_CONCAT(am.nombre_antecedente SEPARATOR ', ') AS antecedentes
            FROM historialmedico hm
            LEFT JOIN tipossangre        ts ON ts.id_tipo_sangre = hm.id_tipo_sangre
            LEFT JOIN antecedentemedico am ON am.id_antecedente IN (
                SELECT ap.id_antecedente
                FROM antecedentemedico ap
                INNER JOIN antecedentemedico am2 ON am2.id_antecedente = ap.id_antecedente
            )
            WHERE hm.numero_paciente = :id
            GROUP BY hm.id_historial
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', (int) $numeroPaciente, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error en Paciente::getHistorial: " . $e->getMessage());
            return null;
        }
    }

    // ==================== ESCRITURA ====================

    /**
     * Crear paciente nuevo.
     * Maneja transacción: paciente + dirección + contactos + contacto emergencia + historial
     */
    public function create($data)
    {
        try {
            $validationError = null;
            if (!$this->validarPaciente($data, $validationError)) {
                throw new Exception($validationError);
            }

            $this->conn->beginTransaction();

            // 1. Crear dirección si se proporcionó
            $idDireccion = null;
            if (!empty($data['calle'])) {
                $idDireccion = $this->crearDireccion($data);
            }

            // 2. Crear paciente
            $query = "
                INSERT INTO paciente
                    (id_paciente_expediente, apellido_paterno, apellido_materno,
                     nombre, fecha_nacimiento, sexo, id_ocupacion, id_direccion, id_estatus)
                VALUES
                    (:expediente, :apellido_paterno, :apellido_materno,
                     :nombre, :fecha_nacimiento, :sexo, :id_ocupacion, :id_direccion, :id_estatus)
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':expediente', (int) $data['id_paciente_expediente'], PDO::PARAM_INT);
            $stmt->bindValue(':apellido_paterno', $this->normalizar($data['apellido_paterno']));
            $stmt->bindValue(':apellido_materno', $this->normalizar($data['apellido_materno'] ?? ''));
            $stmt->bindValue(':nombre', $this->normalizar($data['nombre']));
            $stmt->bindValue(':fecha_nacimiento', $data['fecha_nacimiento']);
            $stmt->bindValue(':sexo', strtoupper($data['sexo']));
            $stmt->bindValue(':id_ocupacion', !empty($data['id_ocupacion']) ? (int) $data['id_ocupacion'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
            $stmt->bindValue(':id_estatus', (int) ($data['id_estatus'] ?? self::ESTATUS_ACTIVO), PDO::PARAM_INT);
            $stmt->execute();

            $numeroPaciente = $this->conn->lastInsertId();

            // 3. Guardar contactos
            // id 1 = Celular Personal, id 6 = Correo electrónico
            if (!empty($data['telefono'])) {
                $this->guardarContacto($numeroPaciente, 1, $data['telefono']);
            }
            if (!empty($data['email'])) {
                $this->guardarContacto($numeroPaciente, 6, $data['email']);
            }

            // 4. Contacto de emergencia
            if (!empty($data['contacto_nombre'])) {
                $this->guardarContactoEmergencia($numeroPaciente, $data);
            }

            // 5. Historial médico inicial
            $this->crearHistorial($numeroPaciente, $data);

            $this->conn->commit();
            return $numeroPaciente;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en Paciente::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar paciente.
     * Maneja transacción: paciente + dirección + contactos
     */
    public function update($id, $data)
    {
        try {
            $validationError = null;
            if (!$this->validarPaciente($data, $validationError)) {
                throw new Exception($validationError);
            }

            $this->conn->beginTransaction();

            // 1. Actualizar o crear dirección
            $pacienteActual = $this->getById($id);
            $idDireccion = null;

            if (!empty($data['calle'])) {
                if ($pacienteActual && $pacienteActual['id_direccion'] ?? false) {
                    $this->actualizarDireccion($pacienteActual['id_direccion'], $data);
                    $idDireccion = $pacienteActual['id_direccion'];
                } else {
                    $idDireccion = $this->crearDireccion($data);
                }
            }

            // 2. Actualizar paciente
            $query = "
                UPDATE paciente SET
                    id_paciente_expediente = :expediente,
                    apellido_paterno  = :apellido_paterno,
                    apellido_materno  = :apellido_materno,
                    nombre            = :nombre,
                    fecha_nacimiento  = :fecha_nacimiento,
                    sexo              = :sexo,
                    id_ocupacion      = :id_ocupacion,
                    id_direccion      = :id_direccion,
                    id_estatus        = :id_estatus
                WHERE numero_paciente = :id
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':expediente', (int)$data['id_paciente_expediente'], PDO::PARAM_INT);
            $stmt->bindValue(':apellido_paterno', $this->normalizar($data['apellido_paterno']));
            $stmt->bindValue(':apellido_materno', $this->normalizar($data['apellido_materno'] ?? ''));
            $stmt->bindValue(':nombre', $this->normalizar($data['nombre']));
            $stmt->bindValue(':fecha_nacimiento', $data['fecha_nacimiento']);
            $stmt->bindValue(':sexo', strtoupper($data['sexo']));
            $stmt->bindValue(':id_ocupacion', !empty($data['id_ocupacion']) ? (int) $data['id_ocupacion'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':id_direccion', $idDireccion, PDO::PARAM_INT);
            $stmt->bindValue(':id_estatus', (int) ($data['id_estatus'] ?? self::ESTATUS_ACTIVO), PDO::PARAM_INT);
            $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
            $stmt->execute();

            // 3. Actualizar contactos
            // id 1 = Celular Personal, id 6 = Correo electrónico
            if (isset($data['telefono'])) {
                $this->actualizarContacto($id, 1, $data['telefono']);
            }
            if (isset($data['email'])) {
                $this->actualizarContacto($id, 6, $data['email']);
            }

            // 4. Actualizar historial
            if (isset($data['notas']) || isset($data['tipo_sangre'])) {
                $this->actualizarHistorial($id, $data);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en Paciente::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estatus del paciente.
     */
    public function cambiarEstatus($id, $nuevoEstatus)
    {
        $query = "UPDATE paciente SET id_estatus = :estatus WHERE numero_paciente = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':estatus', (int) $nuevoEstatus, PDO::PARAM_INT);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en Paciente::cambiarEstatus: " . $e->getMessage());
            return false;
        }
    }

    // ==================== HELPERS PRIVADOS ====================

    private function crearDireccion($data)
    {
        // Buscar o crear código postal
        $idCp = $this->buscarOCrearCP($data);

        $query = "INSERT INTO direcciones (calle, numero_exterior, numero_interior, id_cp)
                  VALUES (:calle, :num_ext, :num_int, :id_cp)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':calle', $this->normalizar($data['calle']));
        $stmt->bindValue(':num_ext', trim($data['numero_exterior'] ?? ''));
        $stmt->bindValue(':num_int', trim($data['numero_interior'] ?? ''));
        $stmt->bindValue(':id_cp', $idCp, PDO::PARAM_INT);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    private function actualizarDireccion($idDireccion, $data)
    {
        $idCp = $this->buscarOCrearCP($data);

        $query = "UPDATE direcciones SET calle = :calle, numero_exterior = :num_ext,
                  numero_interior = :num_int, id_cp = :id_cp
                  WHERE id_direccion = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':calle', $this->normalizar($data['calle']));
        $stmt->bindValue(':num_ext', trim($data['numero_exterior'] ?? ''));
        $stmt->bindValue(':num_int', trim($data['numero_interior'] ?? ''));
        $stmt->bindValue(':id_cp', $idCp, PDO::PARAM_INT);
        $stmt->bindValue(':id', $idDireccion, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function buscarOCrearCP($data)
    {
        // Buscar CP existente
        $stmt = $this->conn->prepare(
            "SELECT id_cp FROM codigospostales WHERE codigo_postal = :cp AND colonia = :colonia LIMIT 1"
        );
        $stmt->bindValue(':cp', $data['codigo_postal']);
        $stmt->bindValue(':colonia', trim($data['colonia'] ?? ''));
        $stmt->execute();
        $cp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cp)
            return $cp['id_cp'];

        // Si no existe, buscar municipio por nombre o usar id_municipio directamente
        $idMunicipio = !empty($data['id_municipio']) ? (int) $data['id_municipio'] : 1;

        $stmt = $this->conn->prepare(
            "INSERT INTO codigospostales (codigo_postal, colonia, id_municipio) VALUES (:cp, :colonia, :id_municipio)"
        );
        $stmt->bindValue(':cp', $data['codigo_postal']);
        $stmt->bindValue(':colonia', $this->normalizar($data['colonia'] ?? ''));
        $stmt->bindValue(':id_municipio', $idMunicipio, PDO::PARAM_INT);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    private function guardarContacto($numeroPaciente, $idTipoContacto, $valor)
    {
        $query = "INSERT INTO contactos (id_tipo_contacto, numero_paciente, valor)
                  VALUES (:tipo, :paciente, :valor)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':tipo', $idTipoContacto, PDO::PARAM_INT);
        $stmt->bindValue(':paciente', $numeroPaciente, PDO::PARAM_INT);
        $stmt->bindValue(':valor', trim($valor));
        $stmt->execute();
    }

    private function actualizarContacto($numeroPaciente, $idTipoContacto, $valor)
    {
        // Verificar si existe
        $stmt = $this->conn->prepare(
            "SELECT id_contacto FROM contactos WHERE numero_paciente = :pac AND id_tipo_contacto = :tipo LIMIT 1"
        );
        $stmt->bindValue(':pac', $numeroPaciente, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $idTipoContacto, PDO::PARAM_INT);
        $stmt->execute();
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            $stmt = $this->conn->prepare(
                "UPDATE contactos SET valor = :valor WHERE id_contacto = :id"
            );
            $stmt->bindValue(':valor', trim($valor));
            $stmt->bindValue(':id', $existe['id_contacto'], PDO::PARAM_INT);
        } else {
            $this->guardarContacto($numeroPaciente, $idTipoContacto, $valor);
            return;
        }
        $stmt->execute();
    }

    private function guardarContactoEmergencia($numeroPaciente, $data)
    {
        // Crear contacto de emergencia
        $stmt = $this->conn->prepare("
            INSERT INTO contactosemergencia
                (nombres_contacto_emergencia, apellido_contacto_emergencia,
                 telefono_contacto_emergencia, id_tipo_parentesco)
            VALUES (:nombres, :apellido, :telefono, :parentesco)
        ");
        $stmt->bindValue(':nombres', $this->normalizar($data['contacto_nombre']));
        $stmt->bindValue(':apellido', $this->normalizar($data['contacto_apellido'] ?? ''));
        $stmt->bindValue(':telefono', trim($data['telefono_emergencia'] ?? ''));
        $stmt->bindValue(':parentesco', (int) ($data['id_tipo_parentesco'] ?? 1), PDO::PARAM_INT);
        $stmt->execute();
        $idContacto = $this->conn->lastInsertId();

        // Vincular con paciente
        $stmt = $this->conn->prepare("
            INSERT INTO pacientecontactosemergencia
                (numero_paciente, id_contacto_emergencia, id_tipo_parentesco)
            VALUES (:paciente, :contacto, :parentesco)
        ");
        $stmt->bindValue(':paciente', $numeroPaciente, PDO::PARAM_INT);
        $stmt->bindValue(':contacto', $idContacto, PDO::PARAM_INT);
        $stmt->bindValue(':parentesco', (int) ($data['id_tipo_parentesco'] ?? 1), PDO::PARAM_INT);
        $stmt->execute();
    }

    private function crearHistorial($numeroPaciente, $data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO historialmedico (numero_paciente, id_tipo_sangre, notas)
            VALUES (:paciente, :sangre, :notas)
        ");
        $stmt->bindValue(':paciente', $numeroPaciente, PDO::PARAM_INT);
        $stmt->bindValue(':sangre', !empty($data['id_tipo_sangre']) ? (int) $data['id_tipo_sangre'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':notas', trim($data['notas'] ?? ''));
        $stmt->execute();
    }

    private function actualizarHistorial($numeroPaciente, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE historialmedico SET
                id_tipo_sangre = :sangre,
                notas          = :notas
            WHERE numero_paciente = :paciente
        ");
        $stmt->bindValue(':sangre', !empty($data['id_tipo_sangre']) ? (int) $data['id_tipo_sangre'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':notas', $this->normalizar($data['notas'] ?? ''));
        $stmt->bindValue(':paciente', (int) $numeroPaciente, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function normalizar(string $valor): string
    {
        // Convierte a mayúsculas sin acentos
        $valor = mb_strtoupper(trim($valor), 'UTF-8');
        $valor = strtr($valor, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'À' => 'A',
            'È' => 'E',
            'Ì' => 'I',
            'Ò' => 'O',
            'Ù' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N'
        ]);
        return $valor;
    }

    public function validarPaciente(array $data, ?string &$error = null): bool
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellidoPaterno = trim($data['apellido_paterno'] ?? '');
        $apellidoMaterno = trim($data['apellido_materno'] ?? '');

        if ($nombre === '') {
            $error = 'El campo nombre es obligatorio';
            return false;
        }

        if ($apellidoPaterno === '') {
            $error = 'El campo apellido paterno es obligatorio';
            return false;
        }

        if ($apellidoMaterno === '') {
            $error = 'El campo apellido materno es obligatorio';
            return false;
        }

        $patron = '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/u';

        if (!preg_match($patron, $nombre)) {
            $error = 'El nombre solo puede contener letras y espacios';
            return false;
        }

        if (!preg_match($patron, $apellidoPaterno)) {
            $error = 'El apellido paterno solo puede contener letras y espacios';
            return false;
        }

        if (!preg_match($patron, $apellidoMaterno)) {
            $error = 'El apellido materno solo puede contener letras y espacios';
            return false;
        }

        return true;
    }

    // ==================== CATÁLOGOS ====================

    public function getTiposSangre()
    {
        try {
            $stmt = $this->conn->query("SELECT id_tipo_sangre, tipo_sangre FROM tipossangre ORDER BY tipo_sangre");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Paciente::getTiposSangre: " . $e->getMessage());
            return [];
        }
    }

    public function getTiposParentesco()
    {
        try {
            $stmt = $this->conn->query("SELECT id_tipo_parentesco, parentesco FROM tipoparentescos ORDER BY parentesco");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Paciente::getTiposParentesco: " . $e->getMessage());
            return [];
        }
    }

    public function getOcupaciones()
    {
        try {
            $stmt = $this->conn->query("SELECT id_ocupacion, ocupacion FROM ocupaciones ORDER BY ocupacion");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Paciente::getOcupaciones: " . $e->getMessage());
            return [];
        }
    }
}
?>