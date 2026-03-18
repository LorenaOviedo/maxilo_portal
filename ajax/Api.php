<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * API Central AJAX
 * Sistema Maxilofacial Texcoco
 *
 * Punto de entrada único para todas las operaciones AJAX del sistema.
 *
 * USO:
 *   GET  ajax/api.php?modulo=procedimientos&accion=get&id=5
 *   POST ajax/api.php  →  body: modulo=procedimientos&accion=create&...
 *
 * MÓDULOS REGISTRADOS:
 *   procedimientos  → models/Procedimiento.php
 *   especialidades  → models/Especialidad.php   (agregar cuando exista)
 *   (agregar más módulos en el array $modulos)
 */
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
 
header('Content-Type: application/json; charset=utf-8');
session_start();
 
// ── Autenticación ────────────────────────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
 
// ── Leer parámetros (GET o POST) ─────────────────────────────────
$params = array_merge($_GET, $_POST);
$modulo = trim($params['modulo'] ?? '');
$accion = trim($params['accion'] ?? '');
 
if (empty($modulo) || empty($accion)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros modulo y accion son obligatorios']);
    exit;
}
 
// ── Registro de módulos disponibles ──────────────────────────────
// Para agregar un módulo nuevo:
//   1. Crea el modelo en models/NombreModelo.php
//   2. Agrega una entrada aquí con la clase y el campo ID que usa
$modulos = [
    'procedimientos' => [
        'modelo'   => 'Procedimiento',
        'archivo'  => __DIR__ . '/../models/Procedimientos.php',
        'campo_id' => 'id_procedimiento',
    ],
    // 'especialidades' => [
    //     'modelo'   => 'Especialidad',
    //     'archivo'  => __DIR__ . '/../models/Especialidad.php',
    //     'campo_id' => 'id_especialidad',
    // ],
];
 
// ── Validar módulo solicitado ─────────────────────────────────────
if (!array_key_exists($modulo, $modulos)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Módulo '{$modulo}' no registrado"]);
    exit;
}
 
$config = $modulos[$modulo];
 
// Cargar modelo
if (!file_exists($config['archivo'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Modelo no encontrado para '{$modulo}'"]);
    exit;
}
 
require_once $config['archivo'];
 
$db    = getDB();
$model = new $config['modelo']($db);
 
// ── Enrutar acción ────────────────────────────────────────────────
switch ($accion) {
 
    // ── GET: obtener un registro por ID ──────────────────────────
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            responder(false, 'ID inválido');
        }
 
        $result = $model->getById($id);
        if (!$result) {
            responder(false, 'Registro no encontrado');
        }
 
        // La clave de respuesta usa el nombre del módulo en singular
        $clave = rtrim($modulo, 's'); // procedimientos → procedimiento
        responder(true, 'OK', [$clave => $result]);
        break;
 
    // ── CREATE: crear nuevo registro ─────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(false, 'Método no permitido');
        }
 
        $data = sanitizarPost($_POST);
        $id   = $model->create($data);
 
        if ($id) {
            responder(true, 'Registro creado correctamente', ['id' => $id]);
        } else {
            responder(false, 'Error al crear el registro');
        }
        break;
 
    // ── UPDATE: actualizar registro ──────────────────────────────
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(false, 'Método no permitido');
        }
 
        $id = (int)($_POST[$config['campo_id']] ?? 0);
        if (!$id) {
            responder(false, 'ID inválido');
        }
 
        $data = sanitizarPost($_POST);
        $ok   = $model->update($id, $data);
 
        if ($ok) {
            responder(true, 'Registro actualizado correctamente');
        } else {
            responder(false, 'Error al actualizar el registro');
        }
        break;
 
    // ── STATUS: cambiar estatus (activar / desactivar) ───────────
    case 'status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(false, 'Método no permitido');
        }
 
        $id          = (int)($_POST[$config['campo_id']] ?? 0);
        $nuevoEstatus= (int)($_POST['id_estatus'] ?? 0);
 
        if (!$id || !in_array($nuevoEstatus, [1, 2])) {
            responder(false, 'Parámetros inválidos');
        }
 
        $ok     = $model->cambiarEstatus($id, $nuevoEstatus);
        $accion = $nuevoEstatus === 1 ? 'activado' : 'desactivado';
 
        if ($ok) {
            responder(true, "Registro {$accion} correctamente");
        } else {
            responder(false, 'Error al cambiar el estatus');
        }
        break;
 
    // ── Acción no reconocida ──────────────────────────────────────
    default:
        http_response_code(400);
        responder(false, "Acción '{$accion}' no reconocida");
}
 
// ── Helpers ───────────────────────────────────────────────────────
 
/**
 * Enviar respuesta JSON y terminar ejecución.
 */
function responder(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}
 
/**
 * Sanitizar datos del POST.
 * Solo limpia strings — no castea tipos (eso lo hace el modelo).
 * Protección contra XSS se maneja al mostrar los datos, no aquí.
 */
function sanitizarPost(array $post): array {
    $clean = [];
    foreach ($post as $key => $value) {
        $clean[$key] = is_string($value)
            ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8')
            : $value;
    }
    return $clean;
}