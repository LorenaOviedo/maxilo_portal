<?php
/**
 * Pago.php — Modelo
 *
 * Tablas: pago, cita, paciente, especialista,
 *         metodopago, estadoscita
 *
 * Reglas:
 *   - Solo citas con estatus "Atendida" pueden recibir pago
 *   - Una cita solo puede tener un pago
 *   - El número de recibo se genera automáticamente: REC-YYYY-NNNNNN
 *   - Al registrar el pago, la cita cambia a estatus "Pagada"
 *   - Estatus del pago: 'Pagado' y 'Pendiente'
 */
class Pago
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
                p.id_pago,
                p.numero_recibo,
                p.fecha_pago,
                p.monto_total,
                p.monto_neto,
                p.referencia_pago,
                p.estatus,
                p.observaciones,
                p.id_cita,
                mp.metodo_pago,
                mp.requiere_referencia,
                mp.es_digital,
                -- Datos de la cita
                c.fecha_cita,
                c.hora_inicio,
                c.costo_total,
                -- Paciente
                TRIM(CONCAT(pac.nombre, ' ', pac.apellido_paterno, ' ',
                     COALESCE(pac.apellido_materno, ''))) AS nombre_paciente,
                -- Especialista
                TRIM(CONCAT(e.nombre, ' ', e.apellido_paterno))  AS nombre_especialista
            FROM  pago          p
            JOIN  metodopago    mp  ON mp.id_metodo_pago  = p.id_metodo_pago
            JOIN  cita          c   ON c.id_cita          = p.id_cita
            JOIN  paciente      pac ON pac.numero_paciente = c.numero_paciente
            JOIN  especialista  e   ON e.id_especialista  = c.id_especialista
            $where
            ORDER BY p.fecha_pago DESC, p.id_pago DESC
            LIMIT :limit OFFSET :offset
        ");
 
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')  $stmt->bindValue(':buscar',  "%$v%");
            if ($k === 'estatus') $stmt->bindValue(':estatus', $v);
            if ($k === 'fecha_desde') $stmt->bindValue(':fecha_desde', $v);
            if ($k === 'fecha_hasta') $stmt->bindValue(':fecha_hasta', $v);
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
            FROM  pago         p
            JOIN  metodopago   mp  ON mp.id_metodo_pago  = p.id_metodo_pago
            JOIN  cita         c   ON c.id_cita          = p.id_cita
            JOIN  paciente     pac ON pac.numero_paciente = c.numero_paciente
            JOIN  especialista e   ON e.id_especialista  = c.id_especialista
            $where
        ");
        foreach ($filtros as $k => $v) {
            if ($k === 'buscar')      $stmt->bindValue(':buscar',      "%$v%");
            if ($k === 'estatus')     $stmt->bindValue(':estatus',     $v);
            if ($k === 'fecha_desde') $stmt->bindValue(':fecha_desde', $v);
            if ($k === 'fecha_hasta') $stmt->bindValue(':fecha_hasta', $v);
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
                p.*,
                mp.metodo_pago,
                mp.requiere_referencia,
                mp.es_digital,
                c.fecha_cita,
                c.hora_inicio,
                c.costo_total,
                mc.motivo_consulta,
                pac.numero_paciente,
                TRIM(CONCAT(pac.nombre, ' ', pac.apellido_paterno, ' ',
                     COALESCE(pac.apellido_materno, ''))) AS nombre_paciente,
                TRIM(CONCAT(e.nombre, ' ', e.apellido_paterno))  AS nombre_especialista
            FROM  pago          p
            JOIN  metodopago    mp  ON mp.id_metodo_pago   = p.id_metodo_pago
            JOIN  cita          c   ON c.id_cita           = p.id_cita
            JOIN  paciente      pac ON pac.numero_paciente = c.numero_paciente
            JOIN  especialista  e   ON e.id_especialista   = c.id_especialista
            LEFT JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            WHERE p.id_pago = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CITAS ATENDIDAS SIN PAGO (para el select del modal)
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCitasAtendidas(): array
    {
        $stmt = $this->db->query("
            SELECT
                c.id_cita,
                c.fecha_cita,
                c.hora_inicio,
                c.costo_total,
                TRIM(CONCAT(pac.nombre, ' ', pac.apellido_paterno, ' ',
                     COALESCE(pac.apellido_materno, ''))) AS nombre_paciente,
                TRIM(CONCAT(e.nombre, ' ', e.apellido_paterno))  AS nombre_especialista,
                mc.motivo_consulta
            FROM  cita          c
            JOIN  estadoscita   ec  ON ec.id_estatus_cita  = c.id_estatus_cita
            JOIN  paciente      pac ON pac.numero_paciente = c.numero_paciente
            JOIN  especialista  e   ON e.id_especialista   = c.id_especialista
            LEFT JOIN motivoconsulta mc ON mc.id_motivo_consulta = c.id_motivo_consulta
            WHERE LOWER(ec.estatus_cita) IN ('atendida', 'atendido')
              AND NOT EXISTS (
                  SELECT 1 FROM pago pg WHERE pg.id_cita = c.id_cita
              )
            ORDER BY c.fecha_cita DESC, c.hora_inicio DESC
            LIMIT 200
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CATÁLOGOS
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getCatalogos(): array
    {
        return [
            'metodosPago'    => $this->_query(
                "SELECT id_metodo_pago, metodo_pago, requiere_referencia, es_digital
                 FROM metodopago ORDER BY metodo_pago"
            ),
            'citasAtendidas' => $this->getCitasAtendidas(),
        ];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // CREAR PAGO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function create(array $data)
    {
        $this->db->beginTransaction();
        try {
            $numeroRecibo = $this->_generarNumeroRecibo();
 
            $stmt = $this->db->prepare("
                INSERT INTO pago
                    (fecha_pago, numero_recibo, monto_total, monto_neto,
                     referencia_pago, estatus, observaciones,
                     id_cita, id_metodo_pago)
                VALUES
                    (:fecha_pago, :numero_recibo, :monto_total, :monto_neto,
                     :referencia_pago, 'Pagado', :observaciones,
                     :id_cita, :id_metodo_pago)
            ");
            $stmt->execute([
                ':fecha_pago'      => $data['fecha_pago'],
                ':numero_recibo'   => $numeroRecibo,
                ':monto_total'     => (float) $data['monto_total'],
                ':monto_neto'      => (float) $data['monto_neto'],
                ':referencia_pago' => trim($data['referencia_pago'] ?? '') ?: null,
                ':observaciones'   => trim($data['observaciones']   ?? '') ?: null,
                ':id_cita'         => (int) $data['id_cita'],
                ':id_metodo_pago'  => (int) $data['id_metodo_pago'],
            ]);
            $idPago = (int) $this->db->lastInsertId();
 
            // Cambiar estatus de la cita a "Pagada" (id = 8 según tu BD)
            // Buscar el id correcto por texto para no asumir el número
            $stmtEstatus = $this->db->query(
                "SELECT id_estatus_cita FROM estadoscita
                 WHERE LOWER(estatus_cita) = 'pagada' LIMIT 1"
            );
            $idEstatusPagada = (int) $stmtEstatus->fetchColumn();
 
            if ($idEstatusPagada) {
                $this->db->prepare(
                    "UPDATE cita SET id_estatus_cita = :id_estatus WHERE id_cita = :id_cita"
                )->execute([
                    ':id_estatus' => $idEstatusPagada,
                    ':id_cita'    => (int) $data['id_cita'],
                ]);
            }
 
            $this->db->commit();
            return $idPago;
 
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Pago::create — ' . $e->getMessage());
            return false;
        }
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // ACTUALIZAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pago SET
                id_metodo_pago  = :id_metodo_pago,
                referencia_pago = :referencia_pago,
                monto_total     = :monto_total,
                monto_neto      = :monto_neto,
                observaciones   = :observaciones
            WHERE id_pago = :id
        ");
        return $stmt->execute([
            ':id_metodo_pago'  => (int) $data['id_metodo_pago'],
            ':referencia_pago' => trim($data['referencia_pago'] ?? '') ?: null,
            ':monto_total'     => (float) $data['monto_total'],
            ':monto_neto'      => (float) $data['monto_neto'],
            ':observaciones'   => trim($data['observaciones'] ?? '') ?: null,
            ':id'              => $id,
        ]);
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR
    // ─────────────────────────────────────────────────────────────────────────
 
    public function validar(array $data): ?string
    {
        if (empty($data['id_cita']))
            return 'Selecciona una cita';
        if (empty($data['id_metodo_pago']))
            return 'El método de pago es obligatorio';
        if (empty($data['fecha_pago']))
            return 'La fecha de pago es obligatoria';
        if ($data['fecha_pago'] > date('Y-m-d'))
            return 'La fecha de pago no puede ser futura';
        if (!isset($data['monto_total']) || $data['monto_total'] === '')
            return 'El monto total es obligatorio';
        if ((float)$data['monto_total'] <= 0)
            return 'El monto total debe ser mayor a 0';
        if (!isset($data['monto_neto']) || $data['monto_neto'] === '')
            return 'El monto neto es obligatorio';
        if ((float)$data['monto_neto'] <= 0)
            return 'El monto neto debe ser mayor a 0';
        if ((float)$data['monto_neto'] > (float)$data['monto_total'])
            return 'El monto neto no puede ser mayor al monto total';
        return null;
    }
 
    public function validarUpdate(array $data): ?string
    {
        if (empty($data['id_metodo_pago']))
            return 'El método de pago es obligatorio';
        if (!isset($data['monto_total']) || (float)$data['monto_total'] <= 0)
            return 'El monto total debe ser mayor a 0';
        if (!isset($data['monto_neto']) || (float)$data['monto_neto'] <= 0)
            return 'El monto neto debe ser mayor a 0';
        if ((float)$data['monto_neto'] > (float)$data['monto_total'])
            return 'El monto neto no puede ser mayor al monto total';
        return null;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // VERIFICAR QUE LA CITA NO TENGA YA UN PAGO
    // ─────────────────────────────────────────────────────────────────────────
 
    public function citaTienePago(int $idCita): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pago WHERE id_cita = :id"
        );
        $stmt->execute([':id' => $idCita]);
        return (int) $stmt->fetchColumn() > 0;
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // RESUMEN para tarjetas
    // ─────────────────────────────────────────────────────────────────────────
 
    public function getResumen(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*)                                    AS total_pagos,
                SUM(monto_neto)                             AS total_recaudado,
                SUM(estatus = 'Pagado')                     AS pagados,
                SUM(estatus = 'Pendiente')                  AS pendientes,
                SUM(MONTH(fecha_pago) = MONTH(CURDATE())
                    AND YEAR(fecha_pago) = YEAR(CURDATE())) AS pagos_mes
            FROM pago
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
 
    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────
 
    private function _generarNumeroRecibo(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pago WHERE YEAR(fecha_pago) = :anio"
        );
        $stmt->execute([':anio' => $anio]);
        $consecutivo = (int) $stmt->fetchColumn() + 1;
        return sprintf('REC-%s-%06d', $anio, $consecutivo);
    }
 
    private function _buildWhere(array $filtros): string
    {
        $conds = [];
        if (!empty($filtros['buscar']))
            $conds[] = "(p.numero_recibo LIKE :buscar
                         OR pac.nombre LIKE :buscar
                         OR pac.apellido_paterno LIKE :buscar)";
        if (!empty($filtros['estatus']))
            $conds[] = "p.estatus = :estatus";
        if (!empty($filtros['fecha_desde']))
            $conds[] = "p.fecha_pago >= :fecha_desde";
        if (!empty($filtros['fecha_hasta']))
            $conds[] = "p.fecha_pago <= :fecha_hasta";
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }
 
 
    // ─────────────────────────────────────────────────────────────────────────
    // FACTURACIÓN
    // ─────────────────────────────────────────────────────────────────────────
 
    /** Datos de facturación guardados del paciente */
    public function getDatosFacturacion(int $numeroPaciente): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM datosfacturacion WHERE numero_paciente = :id LIMIT 1"
        );
        $stmt->execute([':id' => $numeroPaciente]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    /** Verificar si el pago ya tiene solicitud de factura */
    public function tieneSolicitudFactura(int $idPago): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitudfactura WHERE id_pago = :id"
        );
        $stmt->execute([':id' => $idPago]);
        return (int)$stmt->fetchColumn() > 0;
    }
 
    /** Obtener solicitud de factura de un pago */
    public function getSolicitudFactura(int $idPago): ?array
    {
        $stmt = $this->db->prepare("
            SELECT sf.*, ef.estatus_factura, df.rfc, df.razon_social
            FROM   solicitudfactura sf
            JOIN   estadosfactura   ef ON ef.id_estatus_factura   = sf.id_estatus_factura
            JOIN   datosfacturacion df ON df.id_datos_facturacion = sf.id_datos_facturacion
            WHERE  sf.id_pago = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $idPago]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
 
    /** Crear solicitud de factura (paso 1) */
    public function crearSolicitudFactura(array $data)
    {
        $this->db->beginTransaction();
        try {
            // 1. Guardar/actualizar datos de facturación del paciente
            $numeroPaciente = (int)$data['numero_paciente'];
            $existing = $this->getDatosFacturacion($numeroPaciente);
 
            if ($existing) {
                $this->db->prepare("
                    UPDATE datosfacturacion SET
                        rfc          = :rfc,
                        razon_social = :razon_social
                    WHERE id_datos_facturacion = :id
                ")->execute([
                    ':rfc'          => strtoupper(trim($data['rfc'])),
                    ':razon_social' => strtoupper(trim($data['razon_social'])),
                    ':id'           => $existing['id_datos_facturacion'],
                ]);
                $idDatos = $existing['id_datos_facturacion'];
            } else {
                $this->db->prepare("
                    INSERT INTO datosfacturacion (rfc, razon_social, numero_paciente)
                    VALUES (:rfc, :razon_social, :paciente)
                ")->execute([
                    ':rfc'          => strtoupper(trim($data['rfc'])),
                    ':razon_social' => strtoupper(trim($data['razon_social'])),
                    ':paciente'     => $numeroPaciente,
                ]);
                $idDatos = (int)$this->db->lastInsertId();
            }
 
            // 2. Buscar id_estatus "Pendiente" en estadosfactura
            $stmt = $this->db->query(
                "SELECT id_estatus_factura FROM estadosfactura
                 WHERE LOWER(estatus_factura) LIKE '%pendiente%' LIMIT 1"
            );
            $idEstatus = (int)($stmt->fetchColumn() ?: 1);
 
            // 3. Crear solicitud
            $this->db->prepare("
                INSERT INTO solicitudfactura
                    (cfdi, id_estatus_factura, id_pago, id_datos_facturacion)
                VALUES
                    (:cfdi, :id_estatus, :id_pago, :id_datos)
            ")->execute([
                ':cfdi'      => strtoupper(trim($data['cfdi'] ?? 'G03')),
                ':id_estatus'=> $idEstatus,
                ':id_pago'   => (int)$data['id_pago'],
                ':id_datos'  => $idDatos,
            ]);
            $idSolicitud = (int)$this->db->lastInsertId();
 
            $this->db->commit();
            return $idSolicitud;
 
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('Pago::crearSolicitudFactura — ' . $e->getMessage());
            return false;
        }
    }
 
    /** Completar factura con datos del timbrado (paso 2) */
    public function completarFactura(int $idSolicitud, array $data): bool
    {
        // Buscar estatus "Timbrada" o similar
        $stmt = $this->db->query(
            "SELECT id_estatus_factura FROM estadosfactura
             WHERE LOWER(estatus_factura) LIKE '%timb%'
                OR LOWER(estatus_factura) LIKE '%complet%'
                OR LOWER(estatus_factura) LIKE '%emitid%'
             LIMIT 1"
        );
        $idEstatus = (int)($stmt->fetchColumn() ?: 2);
 
        $stmt = $this->db->prepare("
            UPDATE solicitudfactura SET
                folio_fiscal      = :folio_fiscal,
                fecha_facturacion = :fecha_facturacion,
                id_estatus_factura= :id_estatus
            WHERE id_solicitud_factura = :id
        ");
        return $stmt->execute([
            ':folio_fiscal'      => strtoupper(trim($data['folio_fiscal'])),
            ':fecha_facturacion' => $data['fecha_facturacion'],
            ':id_estatus'        => $idEstatus,
            ':id'                => $idSolicitud,
        ]);
    }
 
    /** Catálogos de CFDI y estatus factura */
    public function getCatalogosFacturacion(): array
    {
        return [
            'estadosFactura' => $this->_query(
                "SELECT id_estatus_factura, estatus_factura FROM estadosfactura ORDER BY id_estatus_factura"
            ),
            'usosCFDI' => [
                ['valor' => 'G01', 'etiqueta' => 'G01 — Adquisición de mercancias'],
                ['valor' => 'G03', 'etiqueta' => 'G03 — Gastos en general'],
                ['valor' => 'I08', 'etiqueta' => 'I08 — Gastos médicos por incapacidad o discapacidad'],
                ['valor' => 'D01', 'etiqueta' => 'D01 — Honorarios médicos, dentales y gastos hospitalarios'],
                ['valor' => 'D07', 'etiqueta' => 'D07 — Primas por seguros de gastos médicos'],
                ['valor' => 'P01', 'etiqueta' => 'P01 — Por definir'],
            ],
        ];
    }
 
    /** Datos necesarios para imprimir el recibo */
    public function getDatosRecibo(int $idPago): ?array
    {
        return $this->getById($idPago);
    }
 
    private function _query(string $sql): array
    {
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}