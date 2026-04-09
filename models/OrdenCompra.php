<?php
/**
 * OrdenCompra.php — Modelo
 *
 * Tablas: ordencompra, detalleordencompra, producto,
 *         proveedor, tiposcompra, monedas, estadosOrdenCompra
 */
class OrdenCompra
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
                oc.id_compra,
                oc.folio_compra,
                oc.fecha_emision,
                oc.fecha_entrega_estimada,
                oc.fecha_entrega,
                oc.subtotal,
                oc.tasa_iva,
                oc.iva,
                oc.total,
                oc.observaciones,
                p.razon_social      AS proveedor,
                p.id_proveedor,
                tc.tipo_compra,
                m.moneda,
                e.estatus_orden_compra,
                e.id_estatus_orden_compra,
                COUNT(DISTINCT d.id_producto) AS num_productos
            FROM  ordencompra           oc
            JOIN  proveedor             p   ON p.id_proveedor             = oc.id_proveedor
            JOIN  tiposcompra           tc  ON tc.id_tipo_compra          = oc.id_tipo_compra
            JOIN  monedas               m   ON m.id_moneda                = oc.id_moneda
            JOIN  estadosordencompra    e   ON e.id_estatus_orden_compra  = oc.id_estatus_orden_compra
            LEFT JOIN detalleordencompra d  ON d.id_compra                = oc.id_compra
            $where
            GROUP BY oc.id_compra
            ORDER BY oc.fecha_emision DESC, oc.id_compra DESC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')     $stmt->bindValue(':buscar',     "%$v%");
            if ($k === 'id_estatus') $stmt->bindValue(':id_estatus', (int) $v, PDO::PARAM_INT);
            if ($k === 'id_proveedor') $stmt->bindValue(':id_proveedor', (int) $v, PDO::PARAM_INT);
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
            SELECT COUNT(DISTINCT oc.id_compra)
            FROM  ordencompra oc
            JOIN  proveedor   p ON p.id_proveedor = oc.id_proveedor
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')       $stmt->bindValue(':buscar',       "%$v%");
            if ($k === 'id_estatus')   $stmt->bindValue(':id_estatus',   (int) $v, PDO::PARAM_INT);
            if ($k === 'id_proveedor') $stmt->bindValue(':id_proveedor', (int) $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // DETALLE
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                oc.*,
                p.razon_social  AS proveedor,
                p.id_proveedor,
                tc.tipo_compra,
                m.moneda,
                e.estatus_orden_compra,
                e.id_estatus_orden_compra
            FROM  ordencompra        oc
            JOIN  proveedor          p   ON p.id_proveedor            = oc.id_proveedor
            JOIN  tiposcompra        tc  ON tc.id_tipo_compra         = oc.id_tipo_compra
            JOIN  monedas            m   ON m.id_moneda               = oc.id_moneda
            JOIN  estadosordencompra e   ON e.id_estatus_orden_compra = oc.id_estatus_orden_compra
            WHERE oc.id_compra = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
 
        // Detalle de productos
        $stmt2 = $this->db->prepare("
            SELECT
                d.id_producto,
                d.cantidad,
                d.precio_unitario,
                d.subtotal_linea,
                pr.nombre_producto,
                pr.codigo_producto,
                pr.marca
            FROM  detalleordencompra d
            JOIN  producto           pr ON pr.id_producto = d.id_producto
            WHERE d.id_compra = :id
        ");
        $stmt2->execute([':id' => $id]);
        $row['detalle'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
 
        return $row;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'proveedores' => $this->_query(
                "SELECT id_proveedor, razon_social FROM proveedor
                 WHERE id_estatus = 1 ORDER BY razon_social"
            ),
            'tiposCompra' => $this->_query(
                "SELECT id_tipo_compra, tipo_compra FROM tiposcompra ORDER BY tipo_compra"
            ),
            'monedas' => $this->_query(
                "SELECT id_moneda, moneda FROM monedas ORDER BY moneda"
            ),
            'estadosOrdenCompra' => $this->_query(
                "SELECT id_estatus_orden_compra, estatus_orden_compra
                 FROM estadosordencompra ORDER BY estatus_orden_compra"
            ),
            'productos' => $this->_query(
                "SELECT id_producto, nombre_producto, codigo_producto,
                        precio_compra, marca
                 FROM producto ORDER BY nombre_producto"
            ),
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function create(array $data): int|false
    {
        try {
            $this->db->beginTransaction();
 
            $stmt = $this->db->prepare("
                INSERT INTO ordencompra
                    (folio_compra, fecha_emision, fecha_entrega_estimada,
                     id_tipo_compra, id_moneda, subtotal, tasa_iva, iva, total,
                     id_estatus_orden_compra, observaciones, id_proveedor)
                VALUES
                    (:folio, :fecha_emision, :fecha_entrega_estimada,
                     :id_tipo_compra, :id_moneda, :subtotal, :tasa_iva, :iva, :total,
                     :id_estatus, :observaciones, :id_proveedor)
            ");
 
            $totales = $this->_calcularTotales($data['detalle'] ?? [], $data['tasa_iva'] ?? '16');
 
            $stmt->execute([
                ':folio'                  => strtoupper(trim($data['folio_compra'])),
                ':fecha_emision'          => $data['fecha_emision'],
                ':fecha_entrega_estimada' => $data['fecha_entrega_estimada'] ?: null,
                ':id_tipo_compra'         => (int) $data['id_tipo_compra'],
                ':id_moneda'              => (int) $data['id_moneda'],
                ':subtotal'               => $totales['subtotal'],
                ':tasa_iva'               => $data['tasa_iva'] ?? '16',
                ':iva'                    => $totales['iva'],
                ':total'                  => $totales['total'],
                ':id_estatus'             => (int) ($data['id_estatus_orden_compra'] ?? 1),
                ':observaciones'          => $data['observaciones'] ?? null,
                ':id_proveedor'           => (int) $data['id_proveedor'],
            ]);
 
            $idCompra = (int) $this->db->lastInsertId();
 
            // Insertar detalle
            $this->_insertarDetalle($idCompra, $data['detalle'] ?? []);
 
            $this->db->commit();
            return $idCompra;
 
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('OrdenCompra::create() error: ' . $e->getMessage());
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
 
            $totales = $this->_calcularTotales($data['detalle'] ?? [], $data['tasa_iva'] ?? '16');
 
            $stmt = $this->db->prepare("
                UPDATE ordencompra SET
                    folio_compra             = :folio,
                    fecha_emision            = :fecha_emision,
                    fecha_entrega_estimada   = :fecha_entrega_estimada,
                    fecha_entrega            = :fecha_entrega,
                    id_tipo_compra           = :id_tipo_compra,
                    id_moneda                = :id_moneda,
                    subtotal                 = :subtotal,
                    tasa_iva                 = :tasa_iva,
                    iva                      = :iva,
                    total                    = :total,
                    id_estatus_orden_compra  = :id_estatus,
                    observaciones            = :observaciones,
                    id_proveedor             = :id_proveedor
                WHERE id_compra = :id
            ");
 
            $stmt->execute([
                ':folio'                  => strtoupper(trim($data['folio_compra'])),
                ':fecha_emision'          => $data['fecha_emision'],
                ':fecha_entrega_estimada' => $data['fecha_entrega_estimada'] ?: null,
                ':fecha_entrega'          => $data['fecha_entrega'] ?: null,
                ':id_tipo_compra'         => (int) $data['id_tipo_compra'],
                ':id_moneda'              => (int) $data['id_moneda'],
                ':subtotal'               => $totales['subtotal'],
                ':tasa_iva'               => $data['tasa_iva'] ?? '16',
                ':iva'                    => $totales['iva'],
                ':total'                  => $totales['total'],
                ':id_estatus'             => (int) $data['id_estatus_orden_compra'],
                ':observaciones'          => $data['observaciones'] ?? null,
                ':id_proveedor'           => (int) $data['id_proveedor'],
                ':id'                     => $id,
            ]);
 
            // Reemplazar detalle completo
            $this->db->prepare("DELETE FROM detalleordencompra WHERE id_compra = :id")
                     ->execute([':id' => $id]);
            $this->_insertarDetalle($id, $data['detalle'] ?? []);
 
            $this->db->commit();
            return true;
 
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('OrdenCompra::update() error: ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CAMBIAR ESTATUS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function cambiarEstatus(int $id, int $idEstatus): bool
    {
        $extra  = $idEstatus === 3 ? ', fecha_entrega = CURDATE()' : ''; // 3 = Recibida
        $stmt   = $this->db->prepare(
            "UPDATE ordencompra SET id_estatus_orden_compra = :id_estatus $extra WHERE id_compra = :id"
        );
        return $stmt->execute([':id_estatus' => $idEstatus, ':id' => $id]);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function validar(array $data): ?string
    {
        if (empty(trim($data['folio_compra'] ?? '')))    return 'El folio de la orden es obligatorio';
        if (empty($data['id_proveedor']))                return 'El proveedor es obligatorio';
        if (empty($data['fecha_emision']))               return 'La fecha de emisión es obligatoria';
        if (empty($data['id_tipo_compra']))              return 'El tipo de compra es obligatorio';
        if (empty($data['id_moneda']))                   return 'La moneda es obligatoria';
        if (empty($data['detalle']) || !is_array($data['detalle']) || count($data['detalle']) === 0)
            return 'Debe agregar al menos un producto al detalle';
        return null;
    }
 
    public function folioExiste(string $folio, ?int $excluirId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM ordencompra WHERE folio_compra = :folio";
        $params = [':folio' => strtoupper(trim($folio))];
        if ($excluirId) { $sql .= ' AND id_compra != :id'; $params[':id'] = $excluirId; }
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
            $conds[] = "(oc.folio_compra LIKE :buscar OR p.razon_social LIKE :buscar)";
        if (!empty($filtros['id_estatus']))
            $conds[] = "oc.id_estatus_orden_compra = :id_estatus";
        if (!empty($filtros['id_proveedor']))
            $conds[] = "oc.id_proveedor = :id_proveedor";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
 
    private function _calcularTotales(array $detalle, string $tasaIva): array
    {
        $subtotal = array_sum(array_map(
            fn($item) => (float)($item['precio_unitario'] ?? 0) * (int)($item['cantidad'] ?? 0),
            $detalle
        ));
        $iva   = round($subtotal * ((float)$tasaIva / 100), 2);
        $total = round($subtotal + $iva, 2);
        return ['subtotal' => round($subtotal, 2), 'iva' => $iva, 'total' => $total];
    }
 
    private function _insertarDetalle(int $idCompra, array $detalle): void
    {
        if (empty($detalle)) return;
        $stmt = $this->db->prepare("
            INSERT INTO detalleordencompra (id_compra, id_producto, cantidad, precio_unitario, subtotal_linea)
            VALUES (:id_compra, :id_producto, :cantidad, :precio_unitario, :subtotal_linea)
        ");
        foreach ($detalle as $item) {
            $subtotalLinea = (float)($item['precio_unitario'] ?? 0) * (int)($item['cantidad'] ?? 0);
            $stmt->execute([
                ':id_compra'       => $idCompra,
                ':id_producto'     => (int) $item['id_producto'],
                ':cantidad'        => (int) $item['cantidad'],
                ':precio_unitario' => (float) $item['precio_unitario'],
                ':subtotal_linea'  => round($subtotalLinea, 2),
            ]);
        }
    }
 
    private function _query(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}