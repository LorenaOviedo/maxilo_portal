<?php
/**
 * Modelo para gestionar especialistas: CRUD, validación y consultas.
 *
 * Tablas principales:
 *   especialista, especialidadespecialista, especialidad,
 *   contactos, tipcontacto, direcciones, codigospostales,
 *   municipios, estados, estatus
 */
class Especialista
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────────────────────────────────────

    public function getAll(array $filtros = [], int $pagina = 1, int $porPagina = 10): array
    {
        $where = $this->_buildWhere($filtros);
        $offset = ($pagina - 1) * $porPagina;

        $stmt = $this->db->prepare("
            SELECT
                e.id_especialista,
                e.nombre,
                e.apellido_paterno,
                e.apellido_materno,
                CONCAT_WS(' ', e.nombre, e.apellido_paterno, e.apellido_materno) AS nombre_completo,
                e.fecha_nacimiento,
                e.fecha_contratacion,
                s.estatus,
                s.id_estatus,
                -- Teléfono del primer contacto de tipo teléfono/celular
                (
                    SELECT c.valor
                    FROM   contactos    c
                    JOIN   tipocontacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
                    WHERE  c.id_especialista = e.id_especialista
                      AND  (tc.tipo_contacto LIKE '%tel%' OR tc.tipo_contacto LIKE '%cel%')
                    LIMIT 1
                ) AS telefono,
                -- Especialidades concatenadas para mostrar en tabla
                GROUP_CONCAT(DISTINCT esp.nombre ORDER BY esp.nombre SEPARATOR ', ') AS especialidades
            FROM  especialista          e
            JOIN  estatus               s   ON s.id_estatus     = e.id_estatus
            LEFT JOIN especialidadespecialista ee  ON ee.id_especialista = e.id_especialista
            LEFT JOIN especialidad      esp ON esp.id_especialidad = ee.id_especialidad
            $where
            GROUP BY e.id_especialista
            ORDER BY e.id_especialista DESC, e.nombre
            LIMIT :limit OFFSET :offset
        ");

        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')
                $stmt->bindValue(':buscar', "%$v%");
            if ($k === 'id_estatus')
                $stmt->bindValue(':id_estatus', (int) $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarTotal(array $filtros = []): int
    {
        $where = $this->_buildWhere($filtros);
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT e.id_especialista)
            FROM  especialista e
            JOIN  estatus      s ON s.id_estatus = e.id_estatus
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')
                $stmt->bindValue(':buscar', "%$v%");
            if ($k === 'id_estatus')
                $stmt->bindValue(':id_estatus', (int) $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETALLE POR ID
    // ─────────────────────────────────────────────────────────────────────────

    public function getById(int $id): ?array
    {
        // Datos personales
        $stmt = $this->db->prepare("
            SELECT
                e.id_especialista,
                e.nombre,
                e.apellido_paterno,
                e.apellido_materno,
                e.fecha_nacimiento,
                e.fecha_contratacion,
                e.id_estatus,
                s.estatus,
                -- Dirección
                d.calle,
                d.numero_exterior,
                d.numero_interior,
                cp.codigo_postal,
                cp.colonia,
                cp.id_cp,
                m.municipio,
                est.estado
            FROM  especialista   e
            JOIN  estatus        s   ON s.id_estatus   = e.id_estatus
            LEFT JOIN direcciones      d   ON d.id_direccion  = e.id_direccion
            LEFT JOIN codigospostales  cp  ON cp.id_cp        = d.id_cp
            LEFT JOIN municipios       m   ON m.id_municipio  = cp.id_municipio
            LEFT JOIN estados          est ON est.id_estado   = m.id_estado
            WHERE e.id_especialista = :id
        ");
        $stmt->execute([':id' => $id]);
        $especialista = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$especialista)
            return null;

        // Contactos (teléfono, email)
        $especialista['contactos'] = $this->_getContactos($id);

        // Especialidades con cédula e institución
        $especialista['especialidades'] = $this->_getEspecialidades($id);

        return $especialista;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────────────────────────────────

    public function create(array $data): int|false
    {
        $this->db->beginTransaction();
        try {
            // 1. Dirección
            $idDireccion = $this->_upsertDireccion(null, $data);

            // 2. Especialista
            $stmt = $this->db->prepare("
                INSERT INTO especialista
                    (nombre, apellido_paterno, apellido_materno,
                     fecha_nacimiento, fecha_contratacion,
                     id_direccion, id_estatus)
                VALUES
                    (:nombre, :ap_pat, :ap_mat,
                     :fecha_nac, :fecha_cont,
                     :id_dir, :id_estatus)
            ");
            $stmt->execute([
                ':nombre' => mb_strtoupper(trim($data['nombre']), 'UTF-8'),
                ':ap_pat' => mb_strtoupper(trim($data['apellido_paterno']), 'UTF-8'),
                ':ap_mat' => mb_strtoupper(trim($data['apellido_materno'] ?? ''), 'UTF-8'),
                ':fecha_nac' => $data['fecha_nacimiento'] ?? null,
                ':fecha_cont' => $data['fecha_contratacion'] ?? null,
                ':id_dir' => $idDireccion,
                ':id_estatus' => (int) ($data['id_estatus'] ?? 1),
            ]);
            $idEspecialista = (int) $this->db->lastInsertId();

            // 3. Contactos
            $this->_syncContactos($idEspecialista, $data);

            // 4. Especialidades
            $this->_syncEspecialidades($idEspecialista, $data['especialidades'] ?? []);

            $this->db->commit();
            return $idEspecialista;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Especialista::create — ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTUALIZAR
    // ─────────────────────────────────────────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            // Obtener id_direccion actual
            $row = $this->db->query(
                "SELECT id_direccion FROM especialista WHERE id_especialista = $id"
            )->fetch(PDO::FETCH_ASSOC);

            $idDireccion = $this->_upsertDireccion($row['id_direccion'] ?? null, $data);

            $stmt = $this->db->prepare("
                UPDATE especialista SET
                    nombre            = :nombre,
                    apellido_paterno  = :ap_pat,
                    apellido_materno  = :ap_mat,
                    fecha_nacimiento  = :fecha_nac,
                    fecha_contratacion = :fecha_cont,
                    id_direccion      = :id_dir
                WHERE id_especialista = :id
            ");
            $stmt->execute([
                ':nombre' => mb_strtoupper(trim($data['nombre']), 'UTF-8'),
                ':ap_pat' => mb_strtoupper(trim($data['apellido_paterno']), 'UTF-8'),
                ':ap_mat' => mb_strtoupper(trim($data['apellido_materno'] ?? ''), 'UTF-8'),
                ':fecha_nac' => $data['fecha_nacimiento'] ?? null,
                ':fecha_cont' => $data['fecha_contratacion'] ?? null,
                ':id_dir' => $idDireccion,
                ':id' => $id,
            ]);

            $this->_syncContactos($id, $data);
            $this->_syncEspecialidades($id, $data['especialidades'] ?? []);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Especialista::update — ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CAMBIAR ESTATUS
    // ─────────────────────────────────────────────────────────────────────────

    public function cambiarEstatus(int $id, int $nuevoEstatus): bool
    {
        $stmt = $this->db->prepare("
            UPDATE especialista SET id_estatus = :estatus
            WHERE  id_especialista = :id
        ");
        return $stmt->execute([':estatus' => $nuevoEstatus, ':id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS para el formulario
    // ─────────────────────────────────────────────────────────────────────────

    public function getCatalogos(): array
    {
        return [
            'especialidades' => $this->_fetchAll("
                SELECT id_especialidad, nombre
                FROM   especialidad
                WHERE  id_estatus = (SELECT id_estatus FROM estatus WHERE estatus = 'Activo' LIMIT 1)
                ORDER BY nombre
            "),
            'tiposContacto' => $this->_fetchAll("
                SELECT id_tipo_contacto, tipo_contacto
                FROM   tipocontacto
                ORDER BY tipo_contacto
            "),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDACIÓN
    // ─────────────────────────────────────────────────────────────────────────

    public function validar(array $data): ?string
    {
        $soloLetras = '/^[a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]+$/u';

        // Nombre
        if (empty(trim($data['nombre'] ?? '')))
            return 'El campo "Nombre" es obligatorio';
        if (!preg_match($soloLetras, trim($data['nombre'])))
            return 'El campo "Nombre" solo debe contener letras';

        // Apellido paterno
        if (empty(trim($data['apellido_paterno'] ?? '')))
            return 'El campo "Apellido paterno" es obligatorio';
        if (!preg_match($soloLetras, trim($data['apellido_paterno'])))
            return 'El campo "Apellido paterno" solo debe contener letras';

        // Apellido materno (opcional)
        if (
            !empty($data['apellido_materno']) &&
            !preg_match($soloLetras, trim($data['apellido_materno']))
        )
            return 'El campo "Apellido materno" solo debe contener letras';

        // Fecha de nacimiento (opcional pero valida si se proporciona)
        if (!empty($data['fecha_nacimiento'])) {
            try {
                $fecha = new DateTime($data['fecha_nacimiento']);
                $hoy = new DateTime('today');
                $minima = (new DateTime())->modify('-120 years');
                if ($fecha >= $hoy)
                    return 'La fecha de nacimiento no puede ser igual o futura a hoy';
                if ($fecha < $minima)
                    return 'La fecha de nacimiento no parece valida';
            } catch (\Exception $e) {
                return 'El formato de la fecha de nacimiento no es valido';
            }
        }

        // Fecha de contratacion (opcional pero no futura)
        if (!empty($data['fecha_contratacion'])) {
            try {
                $fechaCont = new DateTime($data['fecha_contratacion']);
                $hoy = new DateTime('today');
                $minima = (new DateTime())->modify('-80 years');
                if ($fechaCont > $hoy)
                    return 'La fecha de contratacion no puede ser futura';
                if ($fechaCont < $minima)
                    return 'La fecha de contratacion no parece valida';
            } catch (\Exception $e) {
                return 'El formato de la fecha de contratacion no es valido';
            }
        }

        // Telefono (10 digitos si se proporciona)
        if (
            !empty($data['telefono']) &&
            !preg_match('/^\d{10}$/', trim($data['telefono']))
        )
            return 'El Telefono debe tener exactamente 10 digitos';

        // Email
        if (
            !empty($data['email']) &&
            !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)
        )
            return 'El formato del correo electronico no es valido';

        // Codigo postal (5 digitos si se proporciona)
        if (
            !empty($data['codigo_postal']) &&
            !preg_match('/^\d{5}$/', trim($data['codigo_postal']))
        )
            return 'El Codigo postal debe tener exactamente 5 digitos';

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    private function _getContactos(int $idEspecialista): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id_contacto, c.valor, tc.tipo_contacto, tc.id_tipo_contacto
            FROM   contactos    c
            JOIN   tipocontacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
            WHERE  c.id_especialista = :id
            ORDER BY tc.id_tipo_contacto
        ");
        $stmt->execute([':id' => $idEspecialista]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function _getEspecialidades(int $idEspecialista): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ee.id_especialidad,
                ee.cedula_profesional,
                ee.institucion,
                esp.nombre AS nombre_especialidad
            FROM  especialidadespecialista ee
            JOIN  especialidad             esp ON esp.id_especialidad = ee.id_especialidad
            WHERE ee.id_especialista = :id
            ORDER BY esp.nombre
        ");
        $stmt->execute([':id' => $idEspecialista]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea o actualiza la dirección del especialista.
     * Reutiliza la fila existente si ya tiene id_direccion.
     */
    private function _upsertDireccion(?int $idDireccion, array $data): ?int
    {
        $idCp = !empty($data['id_cp']) ? (int) $data['id_cp'] : null;
        $calle = trim($data['calle'] ?? '');
        $numExt = trim($data['numero_exterior'] ?? '');
        $numInt = trim($data['numero_interior'] ?? '');

        if (!$idCp && !$calle)
            return $idDireccion; // sin datos de dirección

        if ($idDireccion) {
            $stmt = $this->db->prepare("
                UPDATE direcciones SET
                    calle            = :calle,
                    numero_exterior  = :num_ext,
                    numero_interior  = :num_int,
                    id_cp            = :id_cp
                WHERE id_direccion = :id
            ");
            $stmt->execute([
                ':calle' => $calle,
                ':num_ext' => $numExt ?: null,
                ':num_int' => $numInt ?: null,
                ':id_cp' => $idCp,
                ':id' => $idDireccion,
            ]);
            return $idDireccion;
        }

        $stmt = $this->db->prepare("
            INSERT INTO direcciones (calle, numero_exterior, numero_interior, id_cp)
            VALUES (:calle, :num_ext, :num_int, :id_cp)
        ");
        $stmt->execute([
            ':calle' => $calle,
            ':num_ext' => $numExt ?: null,
            ':num_int' => $numInt ?: null,
            ':id_cp' => $idCp,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Sincroniza contactos del especialista (teléfono y email).
     * Elimina los existentes y vuelve a insertar solo los que tengan valor.
     */
    private function _syncContactos(int $idEspecialista, array $data): void
    {
        $this->db->prepare(
            "DELETE FROM contactos WHERE id_especialista = :id"
        )->execute([':id' => $idEspecialista]);

        $stmt = $this->db->prepare("
            INSERT INTO contactos (id_tipo_contacto, id_especialista, valor)
            VALUES (:tipo, :especialista, :valor)
        ");

        // Teléfono
        if (!empty(trim($data['telefono'] ?? ''))) {
            $stmt->execute([
                ':tipo' => (int) $data['id_tipo_contacto_telefono'],
                ':especialista' => $idEspecialista,
                ':valor' => trim($data['telefono']),
            ]);
        }

        // Email
        if (!empty(trim($data['email'] ?? ''))) {
            $stmt->execute([
                ':tipo' => (int) $data['id_tipo_contacto_email'],
                ':especialista' => $idEspecialista,
                ':valor' => trim($data['email']),
            ]);
        }
    }

    /**
     * Sincroniza especialidades del especialista.
     * Recibe array de: [{ id_especialidad, cedula_profesional, institucion }, ...]
     */
    private function _syncEspecialidades(int $idEspecialista, array $especialidades): void
    {
        $this->db->prepare(
            "DELETE FROM especialidadespecialista WHERE id_especialista = :id"
        )->execute([':id' => $idEspecialista]);

        if (empty($especialidades))
            return;

        $stmt = $this->db->prepare("
            INSERT INTO especialidadespecialista
                (id_especialidad, id_especialista, cedula_profesional, institucion)
            VALUES
                (:id_esp, :id_especialista, :cedula, :institucion)
        ");

        foreach ($especialidades as $esp) {
            if (empty($esp['id_especialidad']))
                continue;
            $stmt->execute([
                ':id_esp' => (int) $esp['id_especialidad'],
                ':id_especialista' => $idEspecialista,
                ':cedula' => trim($esp['cedula_profesional'] ?? '') ?: null,
                ':institucion' => trim($esp['institucion'] ?? '') ?: null,
            ]);
        }
    }

    private function _buildWhere(array $filtros): string
    {
        $conds = [];
        if (!empty($filtros['buscar']))
            $conds[] = "(e.nombre LIKE :buscar OR e.apellido_paterno LIKE :buscar
                         OR e.apellido_materno LIKE :buscar)";
        if (!empty($filtros['id_estatus']))
            $conds[] = "e.id_estatus = :id_estatus";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }

    private function _fetchAll(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
