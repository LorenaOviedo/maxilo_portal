<?php
/**
 * Modelo de inventario para gestión de stock.
 *
 * Vista de solo lectura del inventario actual.
 * Tablas: inventario, producto, tipoproducto
 */
class Inventario
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
                i.id_inventario,
                i.codigo_producto,
                i.lote,
                i.stock,
                i.stock_minimo,
                i.fecha_fabricacion,
                i.fecha_caducidad,
                p.id_producto,
                p.nombre_producto,
                p.marca,
                p.descripcion,
                p.precio_compra,
                p.registro_sanitario,
                tp.nombre_tipo_producto,
                tp.id_tipo_producto
            FROM  inventario   i
            JOIN  producto     p  ON p.codigo_producto  = i.codigo_producto
            JOIN  tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            $where
            ORDER BY p.nombre_producto ASC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')           $stmt->bindValue(':buscar',           "%$v%");
            if ($k === 'id_tipo_producto') $stmt->bindValue(':id_tipo_producto', (int) $v, PDO::PARAM_INT);
            if ($k === 'stock_bajo')       ; // manejado en WHERE con literal
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
            FROM  inventario   i
            JOIN  producto     p  ON p.codigo_producto   = i.codigo_producto
            JOIN  tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')           $stmt->bindValue(':buscar',           "%$v%");
            if ($k === 'id_tipo_producto') $stmt->bindValue(':id_tipo_producto', (int) $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // DETALLE POR ID
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                i.id_inventario,
                i.codigo_producto,
                i.lote,
                i.stock,
                i.stock_minimo,
                i.fecha_fabricacion,
                i.fecha_caducidad,
                p.id_producto,
                p.nombre_producto,
                p.descripcion,
                p.marca,
                p.precio_compra,
                p.registro_sanitario,
                tp.nombre_tipo_producto
            FROM  inventario   i
            JOIN  producto     p  ON p.codigo_producto   = i.codigo_producto
            JOIN  tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            WHERE i.id_inventario = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // RESUMEN para tarjetas del dashboard
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getResumen(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*)                                         AS total_productos,
                SUM(i.stock = 0)                                AS sin_stock,
                SUM(i.stock > 0 AND i.stock <= i.stock_minimo)  AS stock_bajo,
                SUM(i.fecha_caducidad IS NOT NULL
                    AND i.fecha_caducidad < CURDATE())           AS caducados,
                SUM(i.fecha_caducidad IS NOT NULL
                    AND i.fecha_caducidad BETWEEN CURDATE()
                    AND DATE_ADD(CURDATE(), INTERVAL 30 DAY))    AS por_caducar
            FROM inventario i
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────
 
    private function _buildWhere(array $filtros): string
    {
        $conds = [];
 
        if (!empty($filtros['buscar']))
            $conds[] = "(p.nombre_producto LIKE :buscar
                         OR i.codigo_producto LIKE :buscar
                         OR p.marca LIKE :buscar
                         OR i.lote LIKE :buscar)";
 
        if (!empty($filtros['id_tipo_producto']))
            $conds[] = "p.id_tipo_producto = :id_tipo_producto";
 
        // Filtro de alerta: stock en 0 o por debajo del mínimo
        if (!empty($filtros['stock_bajo']))
            $conds[] = "i.stock <= i.stock_minimo";
 
        // Filtro caducados o por caducar en 30 días
        if (!empty($filtros['por_caducar']))
            $conds[] = "(i.fecha_caducidad IS NOT NULL
                         AND i.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
 
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
}
 