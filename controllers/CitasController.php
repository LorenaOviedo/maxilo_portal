<?php
/**
 * Controlador de Citas
 * Sistema Maxilofacial Texcoco
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cita.php';

class CitasController {
    private $db;
    private $citaModel;

    public function __construct() {
        $this->db = getDB();
        $this->citaModel = new Cita($this->db);
    }

    /** Listar citas con filtros opcionales */
    public function index() {
        $filtros = [];
        if (!empty($_GET['fecha']))          $filtros['fecha']          = $_GET['fecha'];
        if (!empty($_GET['estatus']))        $filtros['estatus']        = $_GET['estatus'];
        if (!empty($_GET['id_especialista']))$filtros['id_especialista']= $_GET['id_especialista'];
        if (!empty($_GET['mes']))            $filtros['mes']            = $_GET['mes'];
        if (!empty($_GET['anio']))           $filtros['anio']           = $_GET['anio'];

        $this->json(['success' => true, 'data' => $this->citaModel->getAll($filtros)]);
    }

    /** Obtener una cita por ID */
    public function show($id) {
        $cita = $this->citaModel->getById($id);
        if (!$cita) $this->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        $this->json(['success' => true, 'data' => $cita]);
    }

    /** Crear nueva cita */
    public function store() {
        $data = $this->getPostData();

        $errores = $this->validar($data);
        if (!empty($errores)) {
            $this->json(['success' => false, 'message' => implode(', ', $errores)]);
        }

        if ($this->citaModel->existeConflicto(
            $data['id_especialista'],
            $data['fecha_cita'],
            $data['hora_inicio'],
            $data['duracion_aproximada'] ?? 60
        )) {
            $this->json(['success' => false, 'message' => 'El especialista ya tiene una cita en ese horario']);
        }

        $id = $this->citaModel->create($data);
        if ($id) {
            $this->json(['success' => true, 'message' => 'Cita creada exitosamente', 'data' => $this->citaModel->getById($id)]);
        }
        $this->json(['success' => false, 'message' => 'Error al crear la cita']);
    }

    /** Actualizar cita */
    public function update($id) {
        if (!$this->citaModel->getById($id)) {
            $this->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        }

        $data = $this->getPostData();
        $errores = $this->validar($data);
        if (!empty($errores)) {
            $this->json(['success' => false, 'message' => implode(', ', $errores)]);
        }

        if (!empty($data['fecha_cita']) && !empty($data['hora_inicio'])) {
            if ($this->citaModel->existeConflicto(
                $data['id_especialista'],
                $data['fecha_cita'],
                $data['hora_inicio'],
                $data['duracion_aproximada'] ?? 60,
                $id
            )) {
                $this->json(['success' => false, 'message' => 'El especialista ya tiene una cita en ese horario']);
            }
        }

        if ($this->citaModel->update($id, $data)) {
            $this->json(['success' => true, 'message' => 'Cita actualizada exitosamente', 'data' => $this->citaModel->getById($id)]);
        }
        $this->json(['success' => false, 'message' => 'Error al actualizar la cita']);
    }

    /** Cambiar estatus */
    public function cambiarEstatus($id) {
        $data = $this->getPostData();
        if (empty($data['estatus'])) $this->json(['success' => false, 'message' => 'Estatus requerido']);

        if ($this->citaModel->cambiarEstatus($id, $data['estatus'])) {
            $this->json(['success' => true, 'message' => 'Estatus actualizado']);
        }
        $this->json(['success' => false, 'message' => 'Error al actualizar el estatus']);
    }

    /** Eliminar cita */
    public function destroy($id) {
        if (!$this->citaModel->getById($id)) {
            $this->json(['success' => false, 'message' => 'Cita no encontrada'], 404);
        }
        if ($this->citaModel->delete($id)) {
            $this->json(['success' => true, 'message' => 'Cita eliminada exitosamente']);
        }
        $this->json(['success' => false, 'message' => 'Error al eliminar la cita']);
    }

    /** Días con citas del mes (para el calendario) */
    public function diasConCitas() {
        $mes  = $_GET['mes']  ?? date('n');
        $anio = $_GET['anio'] ?? date('Y');
        $this->json(['success' => true, 'data' => $this->citaModel->getDiasConCitas($mes, $anio)]);
    }

    /** Pacientes para el select */
    public function getPacientes() {
        try {
            $stmt = $this->db->query(
                "SELECT numero_paciente,
                        TRIM(CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno,''))) AS nombre_completo
                 FROM paciente
                 ORDER BY nombre ASC"
            );
            $this->json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            error_log("Error al obtener pacientes: " . $e->getMessage());
            $this->json(['success' => false, 'data' => []]);
        }
    }

    /** Especialistas para el select */
    public function getEspecialistas() {
        try {
            $stmt = $this->db->query(
                "SELECT id_especialista,
                        CONCAT(nombre, ' ', apellido_paterno) AS nombre_completo
                 FROM especialista
                 WHERE estatus = 'activo'
                 ORDER BY nombre ASC"
            );
            $this->json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            error_log("Error al obtener especialistas: " . $e->getMessage());
            $this->json(['success' => false, 'data' => []]);
        }
    }

    /** Motivos de consulta para el select */
    public function getMotivos() {
        try {
            $stmt = $this->db->query(
                "SELECT id_motivo_consulta, motivo_consulta
                 FROM motivoconsulta
                 ORDER BY motivo_consulta ASC"
            );
            $this->json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            error_log("Error al obtener motivos: " . $e->getMessage());
            $this->json(['success' => false, 'data' => []]);
        }
    }

    // ==================== HELPERS PRIVADOS ====================

    private function validar($data) {
        $errores = [];
        if (empty($data['id_paciente']))        $errores[] = 'El paciente es requerido';
        if (empty($data['id_especialista']))    $errores[] = 'El especialista es requerido';
        if (empty($data['id_motivo_consulta'])) $errores[] = 'El motivo de consulta es requerido';
        if (empty($data['fecha_cita']))         $errores[] = 'La fecha es requerida';
        if (empty($data['hora_inicio']))        $errores[] = 'La hora es requerida';
        if (empty($data['tipoPaciente']))       $errores[] = 'El tipo de paciente es requerido';
        return $errores;
    }

    private function getPostData() {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $_POST;
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// ==================== ROUTER ====================
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $ctrl   = new CitasController();
    $action = $_GET['action'] ?? '';
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

    switch ($action) {
        case 'index':           $ctrl->index();             break;
        case 'show':            $ctrl->show($id);           break;
        case 'store':           $ctrl->store();             break;
        case 'update':          $ctrl->update($id);         break;
        case 'cambiar_estatus': $ctrl->cambiarEstatus($id); break;
        case 'destroy':         $ctrl->destroy($id);        break;
        case 'dias_con_citas':  $ctrl->diasConCitas();      break;
        case 'get_pacientes':   $ctrl->getPacientes();      break;
        case 'get_especialistas':$ctrl->getEspecialistas(); break;
        case 'get_motivos':     $ctrl->getMotivos();        break;
        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
    }
}
?>