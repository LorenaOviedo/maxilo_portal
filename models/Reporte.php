<?php
/**
 * Reporte.php — Modelo
 *
 * Genera datos para los 5 tipos de reporte:
 *   - pacientes
 *   - citas
 *   - pagos
 *   - inventario
 *   - facturas
 */
class Reporte
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DISPATCHER
    // ─────────────────────────────────────────────────────────────────────────

    public function generar(string $tipo, string $desde, string $hasta, array $extra = []): array
    {
        switch ($tipo) {
            case 'pacientes':
                return $this->_pacientes($desde, $hasta);
            case 'citas':
                return $this->_citas($desde, $hasta, $extra);
            case 'pagos':
                return $this->_pagos($desde, $hasta, $extra);
            case 'inventario':
                return $this->_inventario($desde, $hasta);
            case 'facturas':
                return $this->_facturas($desde, $hasta, $extra);
            default:
                return ['error' => 'Tipo de reporte no reconocido'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: PACIENTES
    // ─────────────────────────────────────────────────────────────────────────

    private function _pacientes(string $desde, string $hasta): array
    {
        // Resumen
        $resumen = $this->_row("
            SELECT
                COUNT(*)                                                AS total,
                SUM(fecha_registro BETWEEN :d1 AND :h1)                 AS nuevos_periodo,
                SUM(sexo = 'M')                                         AS masculino,
                SUM(sexo = 'F')                                         AS femenino,
                SUM(EXISTS(
                    SELECT 1 FROM cita c WHERE c.numero_paciente = p.numero_paciente
                ))                                                      AS con_citas,
                SUM(NOT EXISTS(
                    SELECT 1 FROM cita c WHERE c.numero_paciente = p.numero_paciente
                ))                                                      AS sin_citas
            FROM paciente p
        ", [':d1' => $desde, ':h1' => $hasta]);

        // Detalle
        $filas = $this->_query("
            SELECT
                p.numero_paciente,
                TRIM(CONCAT(p.nombre,' ',p.apellido_paterno,' ',
                    COALESCE(p.apellido_materno,'')))    AS nombre_completo,
                p.fecha_nacimiento,
                p.sexo,
                p.fecha_registro,
                COUNT(DISTINCT c.id_cita)               AS total_citas,
                SUM(pg.id_pago IS NOT NULL)             AS total_pagos,
                COALESCE(SUM(pg.monto_neto), 0)         AS monto_total
            FROM paciente p
            LEFT JOIN cita   c  ON c.numero_paciente = p.numero_paciente
            LEFT JOIN pago   pg ON pg.id_cita        = c.id_cita
            GROUP BY p.numero_paciente
            ORDER BY p.apellido_paterno, p.nombre
        ", []);

        return [
            'tipo' => 'pacientes',
            'titulo' => 'Reporte de Pacientes',
            'resumen' => [
                ['label' => 'Total pacientes', 'valor' => $resumen['total'] ?? 0],
                ['label' => 'Nuevos en período', 'valor' => $resumen['nuevos_periodo'] ?? 0],
                ['label' => 'Masculino', 'valor' => $resumen['masculino'] ?? 0],
                ['label' => 'Femenino', 'valor' => $resumen['femenino'] ?? 0],
                ['label' => 'Con citas', 'valor' => $resumen['con_citas'] ?? 0],
                ['label' => 'Sin citas', 'valor' => $resumen['sin_citas'] ?? 0],
            ],
            'columnas' => [
                'No. Paciente',
                'Nombre completo',
                'F. Nacimiento',
                'Sexo',
                'F. Registro',
                'Citas',
                'Pagos',
                'Monto pagado',
            ],
            'filas' => array_map(fn($r) => [
                $r['numero_paciente'],
                $r['nombre_completo'],
                $r['fecha_nacimiento'] ? date('d/m/Y', strtotime($r['fecha_nacimiento'])) : '—',
                $r['sexo'] === 'M' ? 'Masculino' : ($r['sexo'] === 'F' ? 'Femenino' : '—'),
                $r['fecha_registro'] ? date('d/m/Y', strtotime($r['fecha_registro'])) : '—',
                $r['total_citas'],
                $r['total_pagos'],
                '$' . number_format((float) $r['monto_total'], 2),
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: CITAS
    // ─────────────────────────────────────────────────────────────────────────

    private function _citas(string $desde, string $hasta, array $extra = []): array
    {
        $condExtra = '';
        $paramsBase = [':d' => $desde, ':h' => $hasta];

        if (!empty($extra['numero_paciente'])) {
            $condExtra .= ' AND c.numero_paciente = :num_pac';
            $paramsBase[':num_pac'] = (int) $extra['numero_paciente'];
        }
        if (!empty($extra['id_especialista'])) {
            $condExtra .= ' AND c.id_especialista = :id_esp';
            $paramsBase[':id_esp'] = (int) $extra['id_especialista'];
        }
        if (!empty($extra['id_estatus_cita'])) {
            $condExtra .= ' AND c.id_estatus_cita = :id_est';
            $paramsBase[':id_est'] = (int) $extra['id_estatus_cita'];
        }

        $resumen = $this->_row("
            SELECT
                COUNT(*)                                    AS total,
                SUM(ec.estatus_cita = 'Atendida'
                    OR ec.estatus_cita = 'Pagada')          AS atendidas,
                SUM(ec.estatus_cita = 'Cancelada')          AS canceladas,
                SUM(ec.estatus_cita = 'No asistió')         AS no_asistio,
                SUM(ec.estatus_cita = 'Pendiente')          AS pendientes,
                COALESCE(SUM(c.costo_total), 0)             AS monto_total
            FROM cita         c
            JOIN estadoscita  ec ON ec.id_estatus_cita = c.id_estatus_cita
            WHERE c.fecha_cita BETWEEN :d AND :h
            $condExtra
        ", $paramsBase);

        $filas = $this->_query("
            SELECT
                c.id_cita,
                c.fecha_cita,
                c.hora_inicio,
                TRIM(CONCAT(p.nombre,' ',p.apellido_paterno,' ',
                    COALESCE(p.apellido_materno,'')))        AS paciente,
                TRIM(CONCAT(e.nombre,' ',e.apellido_paterno)) AS especialista,
                mc.motivo_consulta,
                ec.estatus_cita,
                COALESCE(c.costo_total, 0)                  AS costo_total
            FROM cita            c
            JOIN paciente        p  ON p.numero_paciente  = c.numero_paciente
            JOIN especialista    e  ON e.id_especialista  = c.id_especialista
            JOIN estadoscita     ec ON ec.id_estatus_cita = c.id_estatus_cita
            LEFT JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            WHERE c.fecha_cita BETWEEN :d AND :h
            $condExtra
            ORDER BY c.fecha_cita DESC, c.hora_inicio DESC
        ", $paramsBase);

        return [
            'tipo' => 'citas',
            'titulo' => 'Reporte de Citas',
            'resumen' => [
                ['label' => 'Total citas', 'valor' => $resumen['total'] ?? 0],
                ['label' => 'Atendidas', 'valor' => $resumen['atendidas'] ?? 0],
                ['label' => 'Canceladas', 'valor' => $resumen['canceladas'] ?? 0],
                ['label' => 'No asistió', 'valor' => $resumen['no_asistio'] ?? 0],
                ['label' => 'Pendientes', 'valor' => $resumen['pendientes'] ?? 0],
                ['label' => 'Monto generado', 'valor' => '$' . number_format((float) ($resumen['monto_total'] ?? 0), 2)],
            ],
            'columnas' => [
                'ID',
                'Fecha',
                'Hora',
                'Paciente',
                'Especialista',
                'Motivo',
                'Estatus',
                'Costo',
            ],
            'filas' => array_map(fn($r) => [
                $r['id_cita'],
                date('d/m/Y', strtotime($r['fecha_cita'])),
                substr($r['hora_inicio'], 0, 5),
                $r['paciente'],
                $r['especialista'],
                $r['motivo_consulta'] ?? '—',
                $r['estatus_cita'],
                '$' . number_format((float) $r['costo_total'], 2),
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: PAGOS
    // ─────────────────────────────────────────────────────────────────────────

    private function _pagos(string $desde, string $hasta, array $extra = []): array
    {
        $condExtra = '';
        $paramsBase = [':d' => $desde, ':h' => $hasta];

        if (!empty($extra['id_metodo_pago'])) {
            $condExtra .= ' AND pg.id_metodo_pago = :id_met';
            $paramsBase[':id_met'] = (int) $extra['id_metodo_pago'];
        }
        if (!empty($extra['estatus'])) {
            $condExtra .= ' AND pg.estatus = :estatus';
            $paramsBase[':estatus'] = $extra['estatus'];
        }

        $resumen = $this->_row("
            SELECT
                COUNT(*)                        AS total_pagos,
                SUM(pg.monto_neto)              AS total_recaudado,
                SUM(pg.estatus = 'Pagado')      AS pagados,
                SUM(pg.estatus = 'Pendiente')   AS pendientes,
                AVG(pg.monto_neto)              AS promedio
            FROM pago pg
            WHERE pg.fecha_pago BETWEEN :d AND :h
            $condExtra
        ", $paramsBase);

        $filas = $this->_query("
            SELECT
                pg.numero_recibo,
                pg.fecha_pago,
                TRIM(CONCAT(p.nombre,' ',p.apellido_paterno,' ',
                    COALESCE(p.apellido_materno,'')))    AS paciente,
                mp.metodo_pago,
                pg.referencia_pago,
                pg.monto_total,
                pg.monto_neto,
                pg.estatus
            FROM pago          pg
            JOIN cita          c  ON c.id_cita          = pg.id_cita
            JOIN paciente      p  ON p.numero_paciente  = c.numero_paciente
            JOIN metodopago    mp ON mp.id_metodo_pago  = pg.id_metodo_pago
            WHERE pg.fecha_pago BETWEEN :d AND :h
            $condExtra
            ORDER BY pg.fecha_pago DESC
        ", $paramsBase);

        return [
            'tipo' => 'pagos',
            'titulo' => 'Reporte de Pagos',
            'resumen' => [
                ['label' => 'Total pagos', 'valor' => $resumen['total_pagos'] ?? 0],
                ['label' => 'Pagados', 'valor' => $resumen['pagados'] ?? 0],
                ['label' => 'Pendientes', 'valor' => $resumen['pendientes'] ?? 0],
                ['label' => 'Total recaudado', 'valor' => '$' . number_format((float) ($resumen['total_recaudado'] ?? 0), 2)],
                ['label' => 'Promedio por pago', 'valor' => '$' . number_format((float) ($resumen['promedio'] ?? 0), 2)],
            ],
            'columnas' => [
                'Recibo',
                'Fecha',
                'Paciente',
                'Método',
                'Referencia',
                'Monto total',
                'Monto neto',
                'Estatus',
            ],
            'filas' => array_map(fn($r) => [
                $r['numero_recibo'],
                date('d/m/Y', strtotime($r['fecha_pago'])),
                $r['paciente'],
                $r['metodo_pago'],
                $r['referencia_pago'] ?? '—',
                '$' . number_format((float) $r['monto_total'], 2),
                '$' . number_format((float) $r['monto_neto'], 2),
                $r['estatus'],
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: INVENTARIO
    // ─────────────────────────────────────────────────────────────────────────

    private function _inventario(string $desde, string $hasta): array
    {
        // Movimientos del período en query separada
        $movPeriodo = $this->_row("
            SELECT COUNT(*) AS total
            FROM movimientoinventario
            WHERE fecha_movimiento BETWEEN :d AND :h
        ", [':d' => $desde, ':h' => $hasta]);

        $resumen = $this->_row("
            SELECT
                COUNT(*)                                        AS total_productos,
                SUM(i.stock = 0)                                AS sin_stock,
                SUM(i.stock > 0 AND i.stock <= i.stock_minimo)  AS stock_bajo,
                SUM(i.fecha_caducidad < CURDATE()
                    AND i.fecha_caducidad IS NOT NULL)          AS caducados
            FROM inventario i
        ", []);
        $resumen['movimientos_periodo'] = $movPeriodo['total'] ?? 0;

        // Movimientos por producto en el período
        $movsPorInv = [];
        $stmtMovs = $this->db->prepare("
            SELECT id_inventario, COUNT(*) AS total
            FROM movimientoinventario
            WHERE fecha_movimiento BETWEEN :d AND :h
            GROUP BY id_inventario
        ");
        $stmtMovs->execute([':d' => $desde, ':h' => $hasta]);
        foreach ($stmtMovs->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $movsPorInv[$row['id_inventario']] = $row['total'];
        }

        $filas = $this->_query("
            SELECT
                i.id_inventario,
                i.codigo_producto,
                p.nombre_producto,
                tp.nombre_tipo_producto,
                p.marca,
                i.lote,
                i.stock,
                i.stock_minimo,
                i.fecha_caducidad
            FROM inventario   i
            JOIN producto     p  ON p.codigo_producto   = i.codigo_producto
            JOIN tipoproducto tp ON tp.id_tipo_producto = p.id_tipo_producto
            ORDER BY p.nombre_producto
        ", []);

        return [
            'tipo' => 'inventario',
            'titulo' => 'Reporte de Inventario',
            'resumen' => [
                ['label' => 'Total productos', 'valor' => $resumen['total_productos'] ?? 0],
                ['label' => 'Sin stock', 'valor' => $resumen['sin_stock'] ?? 0],
                ['label' => 'Stock bajo mínimo', 'valor' => $resumen['stock_bajo'] ?? 0],
                ['label' => 'Caducados', 'valor' => $resumen['caducados'] ?? 0],
                ['label' => 'Movimientos en período', 'valor' => $resumen['movimientos_periodo'] ?? 0],
            ],
            'columnas' => [
                'Código',
                'Producto',
                'Tipo',
                'Marca',
                'Lote',
                'Stock',
                'Mínimo',
                'F. Caducidad',
                'Movimientos',
            ],
            'filas' => array_map(function ($r) use ($movsPorInv) {
                return [
                    $r['codigo_producto'],
                    $r['nombre_producto'],
                    $r['nombre_tipo_producto'],
                    $r['marca'] ?? '—',
                    $r['lote'] ?? '—',
                    $r['stock'],
                    $r['stock_minimo'],
                    $r['fecha_caducidad']
                    ? date('d/m/Y', strtotime($r['fecha_caducidad'])) : '—',
                    $movsPorInv[$r['id_inventario']] ?? 0,
                ];
            }, $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE: FACTURAS
    // ─────────────────────────────────────────────────────────────────────────

    private function _facturas(string $desde, string $hasta, array $extra = []): array
    {
        // Pendientes: TODAS sin importar fecha
        // Timbradas: solo las del período seleccionado
        $resumen = $this->_row("
            SELECT
                COUNT(*)                                                AS total,
                SUM(ef.estatus_factura LIKE '%timb%'
                    OR ef.estatus_factura LIKE '%complet%'
                    OR ef.estatus_factura LIKE '%emitid%')              AS timbradas,
                SUM(ef.estatus_factura LIKE '%pendiente%')              AS pendientes,
                COALESCE(SUM(pg.monto_neto), 0)                        AS monto_total
            FROM  solicitudfactura  sf
            JOIN  estadosfactura    ef  ON ef.id_estatus_factura   = sf.id_estatus_factura
            JOIN  pago              pg  ON pg.id_pago              = sf.id_pago
            JOIN  datosfacturacion  df  ON df.id_datos_facturacion = sf.id_datos_facturacion
            WHERE (
                ef.estatus_factura LIKE '%pendiente%'
                OR DATE(sf.fecha_solicitud) BETWEEN :d AND :h
            )
        ", [':d' => $desde, ':h' => $hasta]);

        $condExtra = '';
        $paramsFact = [':d2' => $desde, ':h2' => $hasta];

        if (!empty($extra['numero_paciente'])) {
            $condExtra .= ' AND pac.numero_paciente = :num_pac';
            $paramsFact[':num_pac'] = (int) $extra['numero_paciente'];
        }
        if (!empty($extra['id_estatus_factura'])) {
            $condExtra .= ' AND sf.id_estatus_factura = :id_est';
            $paramsFact[':id_est'] = (int) $extra['id_estatus_factura'];
        }

        $filas = $this->_query("
            SELECT
                sf.id_solicitud_factura,
                DATE(sf.fecha_solicitud)        AS fecha_solicitud,
                sf.cfdi,
                sf.folio_fiscal,
                sf.fecha_facturacion,
                ef.estatus_factura,
                df.rfc,
                df.razon_social,
                pg.numero_recibo,
                pg.monto_neto,
                pg.fecha_pago,
                TRIM(CONCAT(pac.nombre,' ',pac.apellido_paterno,' ',
                    COALESCE(pac.apellido_materno,'')))                 AS paciente
            FROM  solicitudfactura  sf
            JOIN  estadosfactura    ef  ON ef.id_estatus_factura   = sf.id_estatus_factura
            JOIN  datosfacturacion  df  ON df.id_datos_facturacion = sf.id_datos_facturacion
            JOIN  pago              pg  ON pg.id_pago              = sf.id_pago
            JOIN  cita              c   ON c.id_cita               = pg.id_cita
            JOIN  paciente          pac ON pac.numero_paciente      = c.numero_paciente
            WHERE (
                ef.estatus_factura LIKE '%pendiente%'
                OR DATE(sf.fecha_solicitud) BETWEEN :d2 AND :h2
            )
            $condExtra
            ORDER BY
                ef.estatus_factura LIKE '%pendiente%' DESC,
                sf.fecha_solicitud DESC
        ", $paramsFact);

        return [
            'tipo' => 'facturas',
            'titulo' => 'Reporte de Facturas',
            'resumen' => [
                ['label' => 'Total solicitudes', 'valor' => $resumen['total'] ?? 0],
                ['label' => 'Timbradas', 'valor' => $resumen['timbradas'] ?? 0],
                ['label' => 'Pendientes', 'valor' => $resumen['pendientes'] ?? 0],
                ['label' => 'Monto total', 'valor' => '$' . number_format((float) ($resumen['monto_total'] ?? 0), 2)],
            ],
            'columnas' => [
                'Folio solicitud',
                'Fecha solicitud',
                'Paciente',
                'RFC',
                'Razón social',
                'CFDI',
                'Recibo',
                'Monto',
                'Fecha pago',
                'Folio fiscal',
                'Fecha timbrado',
                'Estatus',
            ],
            'filas' => array_map(fn($r) => [
                $r['id_solicitud_factura'],
                $r['fecha_solicitud'] ? date('d/m/Y', strtotime($r['fecha_solicitud'])) : '—',
                $r['paciente'],
                $r['rfc'],
                $r['razon_social'],
                $r['cfdi'] ?? '—',
                $r['numero_recibo'],
                '$' . number_format((float) $r['monto_neto'], 2),
                $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '—',
                $r['folio_fiscal'] ?? '—',
                $r['fecha_facturacion'] ? date('d/m/Y H:i', strtotime($r['fecha_facturacion'])) : '—',
                $r['estatus_factura'],
            ], $filas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS PARA FILTROS
    // ─────────────────────────────────────────────────────────────────────────

    public function getCatalogos(): array
    {
        return [
            'especialistas' => $this->_query(
                "SELECT id_especialista, TRIM(CONCAT(nombre,' ',apellido_paterno)) AS nombre_completo
                FROM especialista
                ORDER BY nombre ASC"
            ),
            'metodosPago' => $this->_query(
                "SELECT id_metodo_pago, metodo_pago FROM metodopago ORDER BY metodo_pago"
            ),
            'estatusCita' => $this->_query(
                "SELECT id_estatus_cita, estatus_cita FROM estadoscita ORDER BY estatus_cita"
            ),
            'estatusFactura' => $this->_query(
                "SELECT id_estatus_factura, estatus_factura FROM estadosfactura ORDER BY estatus_factura"
            ),
            'pacientes' => $this->_query(
                "SELECT numero_paciente, TRIM(CONCAT(nombre,' ',apellido_paterno,' ', COALESCE(apellido_materno,''))) AS nombre_completo
                FROM paciente
                ORDER BY apellido_paterno, nombre ASC"
            ),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function _query(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function _row(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}