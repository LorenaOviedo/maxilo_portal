<?php
/**
 * MovimientoInventario.php — Modelo
 *
 * Tablas: movimientoinventario, inventario, producto,
 *         tipomovimiento, usuario
 *
 * Lógica de stock:
 *   Entrada (1) → stock + cantidad
 *   Salida  (2) → stock - cantidad  (valida que no quede negativo)
 *   Ajuste  (3) → stock = cantidad  (corrección física)
 */
class MovimientoInventario
{
    private PDO $db;
 
    const TIPO_ENTRADA = 1;
    const TIPO_SALIDA  = 2;
    const TIPO_AJUSTE  = 3;
 
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getAll(array $filtros = [], int $pagina = 1, int $porPagina = 15): array
    {
        $where  = $this->_buildWhere($filtros);
        $offset = ($pagina - 1) * $porPagina;
 
        $stmt = $this->db->prepare("
            SELECT
                m.id_movimiento,
                m.cantidad,
                m.fecha_movimiento,
                tm.id_tipo_movimiento,
                tm.tipo_movimiento,
                i.id_inventario,
                i.codigo_producto,
                i.lote,
                i.stock          AS stock_actual,
                p.nombre_producto,
                p.marca,
                u.nombre_usuario AS usuario
            FROM  movimientoinventario m
            JOIN  tipomovimiento       tm ON tm.id_tipo_movimiento = m.id_tipo_movimiento
            JOIN  inventario           i  ON i.id_inventario       = m.id_inventario
            JOIN  producto             p  ON p.codigo_producto     = i.codigo_producto
            JOIN  usuario              u  ON u.id_usuario          = m.id_usuario
            $where
            ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')             $stmt->bindValue(':buscar',            "%$v%");
            if ($k === 'id_tipo_movimiento') $stmt->bindValue(':id_tipo_movimiento',(int)$v, PDO::PARAM_INT);
            if ($k === 'fecha_desde')        $stmt->bindValue(':fecha_desde',       $v);
            if ($k === 'fecha_hasta')        $stmt->bindValue(':fecha_hasta',       $v);
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
            SELECT COUNT(*)
            FROM  movimientoinventario m
            JOIN  tipomovimiento       tm ON tm.id_tipo_movimiento = m.id_tipo_movimiento
            JOIN  inventario           i  ON i.id_inventario       = m.id_inventario
            JOIN  producto             p  ON p.codigo_producto     = i.codigo_producto
            JOIN  usuario              u  ON u.id_usuario          = m.id_usuario
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')             $stmt->bindValue(':buscar',            "%$v%");
            if ($k === 'id_tipo_movimiento') $stmt->bindValue(':id_tipo_movimiento',(int)$v, PDO::PARAM_INT);
            if ($k === 'fecha_desde')        $stmt->bindValue(':fecha_desde',       $v);
            if ($k === 'fecha_hasta')        $stmt->bindValue(':fecha_hasta',       $v);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // BUSCAR INVENTARIO POR CÓDIGO + LOTE
    // Devuelve el registro de inventario para pre-llenar el modal
    // ─────────────────────────────────────────────────────────────────────────
 
    public function buscarInventario(string $codigo, string $lote): ?array
    {
        // Código y lote son obligatorios para identificar unívocamente el inventario
        if (empty(trim($codigo)) || empty(trim($lote))) return null;
 
        $stmt = $this->db->prepare("
            SELECT
                i.id_inventario,
                i.codigo_producto,
                i.lote,
                i.stock,
                i.stock_minimo,
                p.nombre_producto,
                p.marca,
                tp.nombre_tipo_producto
            FROM  inventario   i
            JOIN  producto     p  ON p.codigo_producto   = i.codigo_producto
            JOIN  tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            WHERE i.codigo_producto = :codigo
              AND i.lote            = :lote
            LIMIT 1
        ");
        $stmt->execute([
            ':codigo' => strtoupper(trim($codigo)),
            ':lote'   => trim($lote),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRAR MOVIMIENTO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function registrar(array $data, int $idUsuario): array
    {
        $idInventario    = (int) $data['id_inventario'];
        $idTipo          = (int) $data['id_tipo_movimiento'];
        $cantidad        = (int) $data['cantidad'];
        $fechaMovimiento = $data['fecha_movimiento'] ?? date('Y-m-d');
 
        // Obtener stock actual
        $stmt = $this->db->prepare(
            "SELECT stock FROM inventario WHERE id_inventario = :id FOR UPDATE"
        );
 
        $this->db->beginTransaction();
        try {
            $stmt->execute([':id' => $idInventario]);
            $stockActual = (int) $stmt->fetchColumn();
 
            // Calcular nuevo stock según tipo
            switch ($idTipo) {
                case self::TIPO_ENTRADA:
                    $nuevoStock = $stockActual + $cantidad;
                    break;
                case self::TIPO_SALIDA:
                    $nuevoStock = $stockActual - $cantidad;
                    if ($nuevoStock < 0) {
                        $this->db->rollBack();
                        return [
                            'success' => false,
                            'message' => "Stock insuficiente. Stock actual: {$stockActual}, cantidad solicitada: {$cantidad}",
                        ];
                    }
                    break;
                case self::TIPO_AJUSTE:
                    $nuevoStock = $cantidad; // reemplaza el stock
                    break;
                default:
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Tipo de movimiento no reconocido'];
            }
 
            // 1. Actualizar stock en inventario
            $this->db->prepare(
                "UPDATE inventario SET stock = :stock WHERE id_inventario = :id"
            )->execute([':stock' => $nuevoStock, ':id' => $idInventario]);
 
            // 2. Registrar movimiento
            $this->db->prepare("
                INSERT INTO movimientoinventario
                    (id_tipo_movimiento, id_inventario, cantidad, id_usuario, fecha_movimiento)
                VALUES
                    (:id_tipo, :id_inventario, :cantidad, :id_usuario, :fecha)
            ")->execute([
                ':id_tipo'      => $idTipo,
                ':id_inventario'=> $idInventario,
                ':cantidad'     => $cantidad,
                ':id_usuario'   => $idUsuario,
                ':fecha'        => $fechaMovimiento,
            ]);
 
            $this->db->commit();
 
            return [
                'success'      => true,
                'message'      => 'Movimiento registrado correctamente',
                'stock_previo' => $stockActual,
                'stock_nuevo'  => $nuevoStock,
            ];
 
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('MovimientoInventario::registrar — ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el movimiento'];
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'tiposMovimiento' => $this->_query(
                "SELECT id_tipo_movimiento, tipo_movimiento
                 FROM tipomovimiento ORDER BY id_tipo_movimiento"
            ),
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function validar(array $data): ?string
    {
        if (empty($data['id_inventario']))
            return 'No se encontró el producto en inventario';
        if (empty($data['id_tipo_movimiento']))
            return 'El tipo de movimiento es obligatorio';
        if (!isset($data['cantidad']) || $data['cantidad'] === '')
            return 'La cantidad es obligatoria';
        if (!is_numeric($data['cantidad']) || (int)$data['cantidad'] <= 0)
            return 'La cantidad debe ser un número entero mayor a 0';
        if (empty($data['fecha_movimiento']))
            return 'La fecha del movimiento es obligatoria';
        if ($data['fecha_movimiento'] > date('Y-m-d'))
            return 'La fecha del movimiento no puede ser futura';
        return null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────
 
    private function _buildWhere(array $filtros): string
    {
        $conds = [];
        if (!empty($filtros['buscar']))
            $conds[] = "(i.codigo_producto LIKE :buscar
                         OR p.nombre_producto LIKE :buscar
                         OR i.lote LIKE :buscar)";
        if (!empty($filtros['id_tipo_movimiento']))
            $conds[] = "m.id_tipo_movimiento = :id_tipo_movimiento";
        if (!empty($filtros['fecha_desde']))
            $conds[] = "m.fecha_movimiento >= :fecha_desde";
        if (!empty($filtros['fecha_hasta']))
            $conds[] = "m.fecha_movimiento <= :fecha_hasta";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
 
    private function _query(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}