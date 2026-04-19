<?php
/**
 * Modelo para manejar planes de tratamiento.
 *
 * Tablas: plantratamiento, detalleplantratamiento,
 *         estadostratamiento, procedimientos, especialista.
 */
class PlanTratamiento
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ── Obtener todos los planes de un paciente ───────────────────
    public function getByPaciente(int $numeroPaciente): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                pt.id_plan_tratamiento,
                pt.fecha_creacion,
                pt.notas,
                pt.numero_paciente,
                pt.id_especialista,
                CONCAT(e.nombre, ' ', e.apellido_paterno) AS especialista,
                et.estatus_tratamiento,
                et.id_estatus_tratamiento,
                -- Número relativo del plan por paciente (1, 2, 3...)
                (
                    SELECT COUNT(*)
                    FROM plantratamiento pt2
                    WHERE pt2.numero_paciente = pt.numero_paciente
                      AND pt2.id_plan_tratamiento <= pt.id_plan_tratamiento
                ) AS numero_plan,
                -- Costo total del plan
                COALESCE((
                    SELECT SUM(
                        CASE WHEN d.costo_descuento IS NOT NULL
                             THEN d.costo_descuento
                             ELSE p.precio_base
                        END
                    )
                    FROM detalleplantratamiento d
                    INNER JOIN procedimientos p ON p.id_procedimiento = d.id_procedimiento
                    WHERE d.id_plan_tratamiento = pt.id_plan_tratamiento
                ), 0) AS costo_total
            FROM plantratamiento pt
            INNER JOIN estadostratamiento et ON et.id_estatus_tratamiento = pt.id_estatus_tratamiento
            INNER JOIN especialista        e  ON e.id_especialista        = pt.id_especialista
            WHERE pt.numero_paciente = :paciente
            ORDER BY pt.fecha_creacion DESC
        ");
        $stmt->bindValue(':paciente', $numeroPaciente, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($planes as &$plan) {
                $plan['detalle'] = $this->getDetalle($plan['id_plan_tratamiento']);
            }

            return $planes;
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::getByPaciente: " . $e->getMessage());
            return [];
        }
    }


    // ── Obtener detalle de un plan ────────────────────────────────
    public function getDetalle(int $idPlan): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                d.id_detalle_plan,
                d.id_procedimiento,
                d.numero_pieza,
                d.costo_descuento,
                p.nombre_procedimiento,
                p.precio_base,
                COALESCE(d.costo_descuento, p.precio_base) AS costo_final
            FROM detalleplantratamiento d
            INNER JOIN procedimientos p ON p.id_procedimiento = d.id_procedimiento
            WHERE d.id_plan_tratamiento = :id_plan
            ORDER BY d.id_detalle_plan ASC
        ");
        $stmt->bindValue(':id_plan', $idPlan, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::getDetalle: " . $e->getMessage());
            return [];
        }
    }

    // ── Crear plan con su detalle (transacción) ───────────────────
    public function crear(array $data): int|false
    {
        try {
            $this->conn->beginTransaction();

            // 1. Insertar plan
            $stmt = $this->conn->prepare("
                INSERT INTO plantratamiento
                    (fecha_creacion, id_estatus_tratamiento, notas, numero_paciente, id_especialista)
                VALUES
                    (:fecha, :estatus, :notas, :paciente, :especialista)
            ");
            $stmt->bindValue(':fecha', $data['fecha_creacion'] ?? date('Y-m-d'));
            $stmt->bindValue(':estatus', (int) ($data['id_estatus_tratamiento'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':notas', trim($data['notas'] ?? ''));
            $stmt->bindValue(':paciente', (int) $data['numero_paciente'], PDO::PARAM_INT);
            $stmt->bindValue(':especialista', (int) $data['id_especialista'], PDO::PARAM_INT);
            $stmt->execute();

            $idPlan = (int) $this->conn->lastInsertId();

            // 2. Insertar detalle (array de procedimientos)
            $procedimientos = $data['procedimientos'] ?? [];
            if (!empty($procedimientos)) {
                $this->insertarDetalle($idPlan, $procedimientos);
            }

            $this->conn->commit();
            return $idPlan;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en PlanTratamiento::crear: " . $e->getMessage());
            return false;
        }
    }

    // ── Actualizar estatus de un plan ─────────────────────────────
    public function cambiarEstatus(int $idPlan, int $nuevoEstatus): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE plantratamiento
            SET id_estatus_tratamiento = :estatus
            WHERE id_plan_tratamiento  = :id
        ");
        $stmt->bindValue(':estatus', $nuevoEstatus, PDO::PARAM_INT);
        $stmt->bindValue(':id', $idPlan, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::cambiarEstatus: " . $e->getMessage());
            return false;
        }
    }

    // ── Agregar procedimiento a un plan existente ─────────────────
    public function agregarProcedimiento(int $idPlan, array $proc): bool
    {
        try {
            $this->insertarDetalle($idPlan, [$proc]);
            return true;
        } catch (Exception $e) {
            error_log("Error en PlanTratamiento::agregarProcedimiento: " . $e->getMessage());
            return false;
        }
    }

    // ── Eliminar procedimiento de un plan ─────────────────────────
    public function eliminarProcedimiento(int $idDetalle): bool
    {
        $stmt = $this->conn->prepare("
            DELETE FROM detalleplantratamiento WHERE id_detalle_plan = :id
        ");
        $stmt->bindValue(':id', $idDetalle, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::eliminarProcedimiento: " . $e->getMessage());
            return false;
        }
    }

    // ── Catálogos necesarios para el formulario ───────────────────
    public function getCatalogos(): array
    {
        return [
            'estatus' => $this->getEstatus(),
            'especialistas' => $this->getEspecialistas(),
            'procedimientos' => $this->getProcedimientos(),
        ];
    }

    // ── Helpers privados ──────────────────────────────────────────

    private function insertarDetalle(int $idPlan, array $procedimientos): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO detalleplantratamiento
                (id_plan_tratamiento, id_procedimiento, numero_pieza, costo_descuento)
            VALUES
                (:plan, :procedimiento, :pieza, :descuento)
        ");

        foreach ($procedimientos as $proc) {
            $stmt->bindValue(':plan', $idPlan, PDO::PARAM_INT);
            $stmt->bindValue(':procedimiento', (int) $proc['id_procedimiento'], PDO::PARAM_INT);
            $stmt->bindValue(':pieza', !empty($proc['numero_pieza'])
                ? (int) $proc['numero_pieza'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':descuento', !empty($proc['costo_descuento'])
                ? (float) $proc['costo_descuento'] : null);
            $stmt->execute();
        }
    }

    private function getEstatus(): array
    {
        try {
            $stmt = $this->conn->query("
                SELECT id_estatus_tratamiento, estatus_tratamiento
                FROM estadostratamiento
                ORDER BY id_estatus_tratamiento
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::getEstatus: " . $e->getMessage());
            return [];
        }
    }

    private function getEspecialistas(): array
    {
        try {
            $stmt = $this->conn->query("
                SELECT id_especialista,
                       CONCAT(nombre, ' ', apellido_paterno) AS nombre_completo
                FROM especialista
                WHERE id_estatus = 1
                ORDER BY nombre
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::getEspecialistas: " . $e->getMessage());
            return [];
        }
    }

    private function getProcedimientos(): array
    {
        try {
            $stmt = $this->conn->query("
                SELECT id_procedimiento, nombre_procedimiento, precio_base
                FROM procedimientos
                WHERE id_estatus = 1
                ORDER BY nombre_procedimiento
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en PlanTratamiento::getProcedimientos: " . $e->getMessage());
            return [];
        }
    }

    // ── Validación ────────────────────────────────────────────────
    public function validar(array $data): ?string
    {
        if (empty($data['numero_paciente']) || !(int) $data['numero_paciente']) {
            return 'El paciente es requerido';
        }
        if (empty($data['id_especialista']) || !(int) $data['id_especialista']) {
            return 'El especialista es requerido';
        }
        if (empty($data['fecha_creacion'])) {
            return 'La fecha de creación es requerida';
        }
        try {
            $fecha = new DateTime($data['fecha_creacion']);
        } catch (Exception $e) {
            return 'El formato de la fecha no es válido';
        }
        if (!empty($data['id_estatus_tratamiento'])) {
            $permitidos = [1, 2, 3, 4];
            if (!in_array((int) $data['id_estatus_tratamiento'], $permitidos, true)) {
                return 'El estatus del plan no es válido';
            }
        }

        $errorFecha = $this->validarFecha($data['fecha_creacion'] ?? '');
        if ($errorFecha)
            return $errorFecha;

        return null;
    }

    /**
     * Eliminar plan completo y su detalle. 
     */
    public function eliminarPlan(int $idPlan): bool
    {
        try {
            $this->conn->beginTransaction();

            // Eliminar detalle primero
            $stmt = $this->conn->prepare(
                "DELETE FROM detalleplantratamiento WHERE id_plan_tratamiento = :id"
            );
            $stmt->bindValue(':id', $idPlan, PDO::PARAM_INT);
            $stmt->execute();

            // Eliminar plan
            $stmt = $this->conn->prepare(
                "DELETE FROM plantratamiento WHERE id_plan_tratamiento = :id"
            );
            $stmt->bindValue(':id', $idPlan, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error en PlanTratamiento::eliminarPlan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar fecha — no puede ser futura
     *
     *   $errorFecha = $this->validarFecha($data['fecha_creacion'] ?? '');
     *   if ($errorFecha) return $errorFecha;
     */
    private function validarFecha(string $fecha): ?string
    {
        if (empty($fecha))
            return null;

        try {
            $fechaDate = new DateTime($fecha);
            $hoy = new DateTime('today');

            if ($fechaDate < $hoy) {
                return 'La fecha de creación no puede ser anterior a hoy';
            }
        } catch (Exception $e) {
            return 'El formato de la fecha no es válido';
        }

        return null;
    }
}