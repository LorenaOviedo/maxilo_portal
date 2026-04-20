<?php
/**
 * Modelo para gestionar productos.
 *
 * Tablas: producto, tipoproducto, inventario
 *
 * Nota: al crear un producto se genera automáticamente
 *       su entrada en inventario (stock = 0).
 */
class Producto
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
                p.id_producto,
                p.codigo_producto,
                p.nombre_producto,
                p.marca,
                p.precio_compra,
                p.registro_sanitario,
                p.descripcion,
                tp.nombre_tipo_producto,
                tp.id_tipo_producto,
                COALESCE(i.stock, 0)         AS stock,
                COALESCE(i.stock_minimo, 0)  AS stock_minimo,
                i.fecha_caducidad
            FROM  producto      p
            JOIN  tipoproducto  tp ON tp.id_tipo_producto = p.id_tipo_producto
            LEFT JOIN inventario i  ON i.codigo_producto  = p.codigo_producto
            $where
            ORDER BY p.id_producto DESC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')          $stmt->bindValue(':buscar',          "%$v%");
            if ($k === 'id_tipo_producto') $stmt->bindValue(':id_tipo_producto', (int) $v, PDO::PARAM_INT);
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
            FROM  producto     p
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
                p.id_producto,
                p.codigo_producto,
                p.nombre_producto,
                p.descripcion,
                p.precio_compra,
                p.marca,
                p.registro_sanitario,
                p.id_tipo_producto,
                tp.nombre_tipo_producto,
                COALESCE(i.stock, 0)        AS stock,
                COALESCE(i.stock_minimo, 0) AS stock_minimo,
                i.lote,
                i.fecha_fabricacion,
                i.fecha_caducidad,
                i.id_inventario
            FROM  producto     p
            JOIN  tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            LEFT JOIN inventario i  ON i.codigo_producto = p.codigo_producto
            WHERE p.id_producto = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'tiposProducto' => $this->_query(
                "SELECT id_tipo_producto, nombre_tipo_producto
                 FROM tipoproducto ORDER BY nombre_tipo_producto"
            ),
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function create(array $data): int|false
    {
        $this->db->beginTransaction();
        try {
            $codigo = strtoupper(trim($data['codigo_producto']));
 
            // 1. Producto
            $stmt = $this->db->prepare("
                INSERT INTO producto
                    (codigo_producto, nombre_producto, descripcion,
                     precio_compra, marca, registro_sanitario, id_tipo_producto)
                VALUES
                    (:codigo, :nombre, :descripcion,
                     :precio, :marca, :registro, :id_tipo)
            ");
            $stmt->execute([
                ':codigo'      => $codigo,
                ':nombre'      => mb_strtoupper(trim($data['nombre_producto']), 'UTF-8'),
                ':descripcion' => trim($data['descripcion'] ?? '') ?: null,
                ':precio'      => (float) $data['precio_compra'],
                ':marca'       => mb_strtoupper(trim($data['marca'] ?? ''), 'UTF-8') ?: null,
                ':registro'    => strtoupper(trim($data['registro_sanitario'] ?? '')) ?: null,
                ':id_tipo'     => (int) $data['id_tipo_producto'],
            ]);
            $idProducto = (int) $this->db->lastInsertId();
 
            // 2. Entrada en inventario (siempre al crear un producto)
            $stmt2 = $this->db->prepare("
                INSERT INTO inventario
                    (codigo_producto, lote, stock, stock_minimo,
                     fecha_fabricacion, fecha_caducidad)
                VALUES
                    (:codigo, :lote, :stock, :stock_minimo,
                     :fecha_fab, :fecha_cad)
            ");
            $stmt2->execute([
                ':codigo'       => $codigo,
                ':lote'         => trim($data['lote'] ?? '') ?: null,
                ':stock'        => (int) ($data['stock'] ?? 0),
                ':stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                ':fecha_fab'    => $data['fecha_fabricacion'] ?: null,
                ':fecha_cad'    => $data['fecha_caducidad']   ?: null,
            ]);
 
            $this->db->commit();
            return $idProducto;
 
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Producto::create — ' . $e->getMessage());
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
            $codigo = strtoupper(trim($data['codigo_producto']));
 
            // 1. Producto
            $stmt = $this->db->prepare("
                UPDATE producto SET
                    codigo_producto    = :codigo,
                    nombre_producto    = :nombre,
                    descripcion        = :descripcion,
                    precio_compra      = :precio,
                    marca              = :marca,
                    registro_sanitario = :registro,
                    id_tipo_producto   = :id_tipo
                WHERE id_producto = :id
            ");
            $stmt->execute([
                ':codigo'      => $codigo,
                ':nombre'      => mb_strtoupper(trim($data['nombre_producto']), 'UTF-8'),
                ':descripcion' => trim($data['descripcion'] ?? '') ?: null,
                ':precio'      => (float) $data['precio_compra'],
                ':marca'       => mb_strtoupper(trim($data['marca'] ?? ''), 'UTF-8') ?: null,
                ':registro'    => strtoupper(trim($data['registro_sanitario'] ?? '')) ?: null,
                ':id_tipo'     => (int) $data['id_tipo_producto'],
                ':id'          => $id,
            ]);
 
            // 2. Inventario (upsert)
            $existe = $this->db->prepare(
                "SELECT COUNT(*) FROM inventario WHERE codigo_producto = :codigo"
            );
            $existe->execute([':codigo' => $codigo]);
 
            if ((int) $existe->fetchColumn() > 0) {
                $stmt2 = $this->db->prepare("
                    UPDATE inventario SET
                        lote              = :lote,
                        stock_minimo      = :stock_minimo,
                        fecha_fabricacion = :fecha_fab,
                        fecha_caducidad   = :fecha_cad
                    WHERE codigo_producto = :codigo
                ");
            } else {
                $stmt2 = $this->db->prepare("
                    INSERT INTO inventario
                        (codigo_producto, lote, stock, stock_minimo,
                         fecha_fabricacion, fecha_caducidad)
                    VALUES
                        (:codigo, :lote, 0, :stock_minimo,
                         :fecha_fab, :fecha_cad)
                ");
            }
            $stmt2->execute([
                ':codigo'       => $codigo,
                ':lote'         => trim($data['lote'] ?? '') ?: null,
                ':stock_minimo' => (int) ($data['stock_minimo'] ?? 0),
                ':fecha_fab'    => $data['fecha_fabricacion'] ?: null,
                ':fecha_cad'    => $data['fecha_caducidad']   ?: null,
            ]);
 
            $this->db->commit();
            return true;
 
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Producto::update — ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ELIMINAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function delete(int $id): bool
    {
        // inventario tiene ON DELETE RESTRICT, hay que borrar primero
        $this->db->beginTransaction();
        try {
            $codigo = $this->db->prepare(
                "SELECT codigo_producto FROM producto WHERE id_producto = :id"
            );
            $codigo->execute([':id' => $id]);
            $cod = $codigo->fetchColumn();
 
            if ($cod) {
                $this->db->prepare("DELETE FROM inventario WHERE codigo_producto = :c")
                         ->execute([':c' => $cod]);
            }
            $this->db->prepare("DELETE FROM producto WHERE id_producto = :id")
                     ->execute([':id' => $id]);
 
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Producto::delete — ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function validar(array $data): ?string
    {
        // Código — obligatorio, alfanumérico con guiones
        $codigo = strtoupper(trim($data['codigo_producto'] ?? ''));
        if (empty($codigo))
            return 'El código del producto es obligatorio';
        if (!preg_match('/^[A-Z0-9\-]+$/', $codigo))
            return 'El código solo puede contener letras, números y guiones';
 
        // Nombre — obligatorio
        if (empty(trim($data['nombre_producto'] ?? '')))
            return 'El nombre del producto es obligatorio';
 
        // Tipo — obligatorio
        if (empty($data['id_tipo_producto']))
            return 'El tipo de producto es obligatorio';
 
        // Precio — obligatorio y positivo
        if (!isset($data['precio_compra']) || $data['precio_compra'] === '')
            return 'El precio de compra es obligatorio';
        if (!is_numeric($data['precio_compra']) || (float) $data['precio_compra'] < 0)
            return 'El precio de compra debe ser un número positivo';
 
        // Stock mínimo — positivo si se proporciona
        if (!empty($data['stock_minimo']) && ((int) $data['stock_minimo'] < 0))
            return 'El stock mínimo no puede ser negativo';
 
        // Fechas de inventario — validar si se proporcionan
        if (!empty($data['fecha_fabricacion']) && !empty($data['fecha_caducidad'])) {
            if ($data['fecha_fabricacion'] >= $data['fecha_caducidad'])
                return 'La fecha de fabricación debe ser anterior a la fecha de caducidad';
        }
        if (!empty($data['fecha_caducidad'])) {
            if ($data['fecha_caducidad'] < date('Y-m-d'))
                return 'La fecha de caducidad no puede ser una fecha pasada';
        }
 
        return null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VERIFICAR CÓDIGO ÚNICO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function codigoExiste(string $codigo, ?int $excluirId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM producto WHERE codigo_producto = :codigo";
        $params = [':codigo' => strtoupper(trim($codigo))];
        if ($excluirId) {
            $sql             .= ' AND id_producto != :id';
            $params[':id']    = $excluirId;
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
            $conds[] = "(p.nombre_producto LIKE :buscar
                         OR p.codigo_producto LIKE :buscar
                         OR p.marca LIKE :buscar)";
        if (!empty($filtros['id_tipo_producto']))
            $conds[] = "p.id_tipo_producto = :id_tipo_producto";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
 
    private function _query(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}