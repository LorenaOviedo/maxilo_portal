<?php
/**
 * Proveedor.php — Modelo
 *
 * Tablas principales:
 *   Proveedor, Contactos, TipoContacto, TipoProductoProveedor,
 *   Direcciones, CodigosPostales, Municipios, Estados, Estatus
 */
class Proveedor
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
        $where  = $this->_buildWhere($filtros);
        $offset = ($pagina - 1) * $porPagina;
 
        $stmt = $this->db->prepare("
            SELECT
                p.id_proveedor,
                p.rfc,
                p.razon_social,
                p.tipo_persona,
                p.terminos_pago,
                p.dias_credito,
                p.limite_credito,
                p.id_estatus,
                s.estatus,
                tp.tipo_producto_proveedor,
                -- Teléfono
                (
                    SELECT c.valor FROM contactos c
                    JOIN   TipoContacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
                    WHERE  c.id_proveedor = p.id_proveedor
                      AND  (tc.tipo_contacto LIKE '%tel%' OR tc.tipo_contacto LIKE '%cel%')
                    LIMIT 1
                ) AS telefono,
                -- Email
                (
                    SELECT c.valor FROM contactos c
                    JOIN   tipocontacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
                    WHERE  c.id_proveedor = p.id_proveedor
                      AND  (tc.tipo_contacto LIKE '%email%' OR tc.tipo_contacto LIKE '%correo%')
                    LIMIT 1
                ) AS email,
                -- Dirección
                d.calle,
                d.numero_exterior,
                d.numero_interior,
                cp.codigo_postal,
                cp.colonia,
                m.municipio,
                e.estado
            FROM  proveedor              p
            JOIN  estatus                s   ON s.id_estatus                = p.id_estatus
            LEFT JOIN tipoproductoproveedor tp ON tp.id_tipo_producto_proveedor = p.id_tipo_producto_proveedor
            LEFT JOIN direcciones        d   ON d.id_direccion              = p.id_direccion
            LEFT JOIN codigospostales    cp  ON cp.id_cp                    = d.id_cp
            LEFT JOIN municipios         m   ON m.id_municipio              = cp.id_municipio
            LEFT JOIN estados            e   ON e.id_estado                 = m.id_estado
            $where
            ORDER BY p.razon_social ASC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')     $stmt->bindValue(':buscar',     "%$v%");
            if ($k === 'id_estatus') $stmt->bindValue(':id_estatus', (int) $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();
 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public function contarTotal(array $filtros = []): int
    {
        $where = $this->_buildWhere($filtros);
        $stmt  = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id_proveedor)
            FROM  proveedor p
            JOIN  estatus   s ON s.id_estatus = p.id_estatus
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')     $stmt->bindValue(':buscar',     "%$v%");
            if ($k === 'id_estatus') $stmt->bindValue(':id_estatus', (int) $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // DETALLE (para modal)
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                s.estatus,
                tp.tipo_producto_proveedor,
                d.calle, d.numero_exterior, d.numero_interior,
                cp.id_cp, cp.codigo_postal, cp.colonia,
                m.municipio,
                e.estado
            FROM  proveedor              p
            JOIN  estatus                s   ON s.id_estatus                = p.id_estatus
            LEFT JOIN tipoproductoproveedor tp ON tp.id_tipo_producto_proveedor = p.id_tipo_producto_proveedor
            LEFT JOIN direcciones        d   ON d.id_direccion              = p.id_direccion
            LEFT JOIN codigospostales    cp  ON cp.id_cp                    = d.id_cp
            LEFT JOIN municipios         m   ON m.id_municipio              = cp.id_municipio
            LEFT JOIN estados            e   ON e.id_estado                 = m.id_estado
            WHERE p.id_proveedor = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
 
        // Contactos
        $stmt2 = $this->db->prepare("
            SELECT c.valor, tc.tipo_contacto, c.id_tipo_contacto
            FROM   contactos    c
            JOIN   tipocontacto tc ON tc.id_tipo_contacto = c.id_tipo_contacto
            WHERE  c.id_proveedor = :id
        ");
        $stmt2->execute([':id' => $id]);
        $row['contactos'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
 
        return $row;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS para el modal
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'tiposProductoProveedor' => $this->_query(
                "SELECT id_tipo_producto_proveedor, tipo_producto_proveedor
                 FROM tipoproductoproveedor ORDER BY tipo_producto_proveedor"
            ),
            'tiposContacto' => $this->_query(
                "SELECT id_tipo_contacto, tipo_contacto FROM tipocontacto ORDER BY tipo_contacto"
            ),
            'tiposPersona' => [
                ['valor' => 'Moral',   'etiqueta' => 'Moral'],
                ['valor' => 'Física',  'etiqueta' => 'Física'],
            ],
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function create(array $data): int|false
    {
        try {
            $this->db->beginTransaction();
 
            // 1. Dirección (si viene CP válido)
            $idDireccion = null;
            if (!empty($data['id_cp'])) {
                $idDireccion = $this->_upsertDireccion($data);
            }
 
            // 2. Proveedor
            $stmt = $this->db->prepare("
                INSERT INTO proveedor
                    (rfc, tipo_persona, razon_social, id_tipo_producto_proveedor,
                     id_direccion, terminos_pago, dias_credito, limite_credito, id_estatus)
                VALUES
                    (:rfc, :tipo_persona, :razon_social, :id_tipo_prod,
                     :id_dir, :terminos_pago, :dias_credito, :limite_credito, :id_estatus)
            ");
            $stmt->execute([
                ':rfc'          => strtoupper(trim($data['rfc'])),
                ':tipo_persona' => $data['tipo_persona'],
                ':razon_social' => strtoupper(trim($data['razon_social'])),
                ':id_tipo_prod' => $data['id_tipo_producto_proveedor'] ?: null,
                ':id_dir'       => $idDireccion,
                ':terminos_pago'=> $data['terminos_pago'] ?? null,
                ':dias_credito' => (int) ($data['dias_credito'] ?? 0),
                ':limite_credito'=> (float) ($data['limite_credito'] ?? 0),
                ':id_estatus'   => 1,
            ]);
            $idProveedor = (int) $this->db->lastInsertId();
 
            // 3. Contactos
            $this->_upsertContactos($idProveedor, $data);
 
            $this->db->commit();
            return $idProveedor;
 
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Proveedor::create() error: ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ACTUALIZAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function update(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();
 
            // 1. Dirección
            $idDireccion = $data['id_direccion_actual'] ?? null;
            if (!empty($data['id_cp'])) {
                $idDireccion = $this->_upsertDireccion($data, $idDireccion);
            }
 
            // 2. Proveedor
            $stmt = $this->db->prepare("
                UPDATE proveedor SET
                    rfc                        = :rfc,
                    tipo_persona               = :tipo_persona,
                    razon_social               = :razon_social,
                    id_tipo_producto_proveedor = :id_tipo_prod,
                    id_direccion               = :id_dir,
                    terminos_pago              = :terminos_pago,
                    dias_credito               = :dias_credito,
                    limite_credito             = :limite_credito
                WHERE id_proveedor = :id
            ");
            $stmt->execute([
                ':rfc'          => strtoupper(trim($data['rfc'])),
                ':tipo_persona' => $data['tipo_persona'],
                ':razon_social' => strtoupper(trim($data['razon_social'])),
                ':id_tipo_prod' => $data['id_tipo_producto_proveedor'] ?: null,
                ':id_dir'       => $idDireccion,
                ':terminos_pago'=> $data['terminos_pago'] ?? null,
                ':dias_credito' => (int) ($data['dias_credito'] ?? 0),
                ':limite_credito'=> (float) ($data['limite_credito'] ?? 0),
                ':id'           => $id,
            ]);
 
            // 3. Contactos (borrar y reinsertar)
            $this->_upsertContactos($id, $data);
 
            $this->db->commit();
            return true;
 
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Proveedor::update() error: ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CAMBIAR ESTATUS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function cambiarEstatus(int $id, int $idEstatus): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE proveedor SET id_estatus = :id_estatus WHERE id_proveedor = :id"
        );
        return $stmt->execute([':id_estatus' => $idEstatus, ':id' => $id]);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR (backend)
    // ─────────────────────────────────────────────────────────────────────────
 
    public function validar(array $data): ?string
    {
        if (empty(trim($data['rfc'] ?? '')))
            return 'El RFC es obligatorio';
        if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', strtoupper(trim($data['rfc'] ?? ''))))
            return 'El formato del RFC no es válido';
        if (empty(trim($data['razon_social'] ?? '')))
            return 'La razón social es obligatoria';
        if (empty($data['tipo_persona']))
            return 'El tipo de persona es obligatorio';
        if (!in_array($data['tipo_persona'], ['Moral', 'Física']))
            return 'El tipo de persona debe ser "Moral" o "Física"';
        return null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VERIFICAR RFC ÚNICO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function rfcExiste(string $rfc, ?int $excluirId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM Proveedor WHERE rfc = :rfc";
        $params = [':rfc' => strtoupper(trim($rfc))];
        if ($excluirId) {
            $sql .= " AND id_proveedor != :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────
 
    private function _buildWhere(array $filtros): string
    {
        $conds = [];
        if (!empty($filtros['buscar']))
            $conds[] = "(p.rfc LIKE :buscar OR p.razon_social LIKE :buscar)";
        if (!empty($filtros['id_estatus']))
            $conds[] = "p.id_estatus = :id_estatus";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
 
    private function _upsertDireccion(array $data, ?int $idExistente = null): int
    {
        if ($idExistente) {
            $stmt = $this->db->prepare("
                UPDATE direcciones SET
                    calle           = :calle,
                    numero_exterior = :num_ext,
                    numero_interior = :num_int,
                    id_cp           = :id_cp
                WHERE id_direccion = :id
            ");
            $stmt->execute([
                ':calle'   => $data['calle']           ?? '',
                ':num_ext' => $data['numero_exterior']  ?? '',
                ':num_int' => $data['numero_interior']  ?? null,
                ':id_cp'   => (int) $data['id_cp'],
                ':id'      => $idExistente,
            ]);
            return $idExistente;
        }
 
        $stmt = $this->db->prepare("
            INSERT INTO direcciones (calle, numero_exterior, numero_interior, id_cp)
            VALUES (:calle, :num_ext, :num_int, :id_cp)
        ");
        $stmt->execute([
            ':calle'   => $data['calle']           ?? '',
            ':num_ext' => $data['numero_exterior']  ?? '',
            ':num_int' => $data['numero_interior']  ?? null,
            ':id_cp'   => (int) $data['id_cp'],
        ]);
        return (int) $this->db->lastInsertId();
    }
 
    private function _upsertContactos(int $idProveedor, array $data): void
    {
        // Borrar contactos anteriores
        $this->db->prepare("DELETE FROM Contactos WHERE id_proveedor = :id")
                 ->execute([':id' => $idProveedor]);
 
        $insertar = $this->db->prepare("
            INSERT INTO contactos (id_tipo_contacto, id_proveedor, valor)
            VALUES (:tipo, :proveedor, :valor)
        ");
 
        if (!empty($data['telefono']) && !empty($data['id_tipo_contacto_telefono'])) {
            $insertar->execute([
                ':tipo'      => (int) $data['id_tipo_contacto_telefono'],
                ':proveedor' => $idProveedor,
                ':valor'     => trim($data['telefono']),
            ]);
        }
        if (!empty($data['email']) && !empty($data['id_tipo_contacto_email'])) {
            $insertar->execute([
                ':tipo'      => (int) $data['id_tipo_contacto_email'],
                ':proveedor' => $idProveedor,
                ':valor'     => strtolower(trim($data['email'])),
            ]);
        }
    }
 
    private function _query(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}