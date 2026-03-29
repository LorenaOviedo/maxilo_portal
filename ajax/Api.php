<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * API centrarizada para operaciones AJAX.
 *
 * Punto de entrada único para todas las operaciones AJAX del sistema.
 *
 * USO:
 *   GET  ajax/api.php?modulo=procedimientos&accion=get&id=5
 *   POST ajax/api.php  →  body: modulo=procedimientos&accion=create&
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

$db = getDB();

// ── Acciones globales (no requieren módulo) ───────────────
if ($accion === 'buscar_cp') {
    $cp = trim($_GET['cp'] ?? '');
    if (empty($cp))
        responder(false, 'Código postal requerido');

    $stmt = $db->prepare("
        SELECT cp.id_cp, cp.colonia, m.municipio, e.estado
        FROM codigospostales cp
        INNER JOIN municipios m ON m.id_municipio = cp.id_municipio
        INNER JOIN estados    e ON e.id_estado    = m.id_estado
        WHERE cp.codigo_postal = :cp
        ORDER BY cp.colonia
    ");
    $stmt->execute([':cp' => $cp]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($resultados))
        responder(false, 'Código postal no encontrado');

    responder(true, 'OK', [
        'estado' => $resultados[0]['estado'],
        'municipio' => $resultados[0]['municipio'],
        'colonias' => array_map(fn($r) => [
            'id_cp' => $r['id_cp'],
            'colonia' => $r['colonia']
        ], $resultados)
    ]);
}

if (empty($modulo) || empty($accion)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros modulo y accion son obligatorios']);
    exit;
}

// ── Registro de módulos disponibles ──────────────────────────────
$modulos = [
    'dashboard' => [
        'modelo' => 'Dashboard',
        'archivo' => __DIR__ . '/../models/Dashboard.php',
        'campo_id' => null,
    ],

    'procedimientos' => [
        'modelo' => 'Procedimiento',
        'archivo' => __DIR__ . '/../models/Procedimientos.php',
        'campo_id' => 'id_procedimiento',
    ],

    'pacientes' => [
        'modelo' => 'Paciente',
        'archivo' => __DIR__ . '/../models/Paciente.php',
        'campo_id' => 'numero_paciente',
    ],

    'planes' => [
        'modelo' => 'PlanTratamiento',
        'archivo' => __DIR__ . '/../models/PlanTratamiento.php',
        'campo_id' => 'id_plan_tratamiento',
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

$model = new $config['modelo']($db);


// ── Enrutar acción ────────────────────────────────────────────────
switch ($accion) {

    // ── GET: obtener un registro por ID ──────────────────────────
    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            responder(false, 'ID inválido');
        }

        $result = $model->getById($id);
        if (!$result) {
            responder(false, 'Registro no encontrado');
        }

        // La clave de respuesta usa el nombre del módulo en singular
        $clave = rtrim($modulo, 's'); // Ej: 'procedimientos' → 'procedimiento'
        responder(true, 'OK', [$clave => $result]);
        break;

    // ── CREATE: crear nuevo registro ─────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(false, 'Método no permitido');
        }

        $data = sanitizarPost($_POST);

        if (method_exists($model, 'validar')) {
            $error = $model->validar($data);
            if ($error)
                responder(false, $error);
        }

        $validationError = null;
        if (method_exists($model, 'validarPaciente') && !$model->validarPaciente($data, $validationError)) {
            responder(false, $validationError);
        }

        $id = $model->create($data);

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

        $id = (int) ($_POST[$config['campo_id']] ?? 0);
        if (!$id) {
            responder(false, 'ID inválido');
        }

        $data = sanitizarPost($_POST);

        //LLamar método de validación general para cada modulo (si existe)
        if (method_exists($model, 'validar')) {
            $error = $model->validar($data);
            if ($error)
                responder(false, $error);
        }

        $validationError = null;
        if (method_exists($model, 'validarPaciente') && !$model->validarPaciente($data, $validationError)) {
            responder(false, $validationError);
        }

        $ok = $model->update($id, $data);

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

        $id = (int) ($_POST[$config['campo_id']] ?? 0);
        $nuevoEstatus = (int) ($_POST['id_estatus'] ?? 0);

        if (!$id || !in_array($nuevoEstatus, [1, 2])) {
            responder(false, 'Parámetros inválidos');
        }

        $ok = $model->cambiarEstatus($id, $nuevoEstatus);
        $accion = $nuevoEstatus === 1 ? 'activado' : 'desactivado';

        if ($ok) {
            responder(true, "Registro {$accion} correctamente");
        } else {
            responder(false, 'Error al cambiar el estatus');
        }
        break;

    case 'search':
        $buscar = trim($_GET['buscar'] ?? '');
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = max(1, (int) ($_GET['por_pagina'] ?? 10));

        $filtros = [];
        if ($buscar !== '')
            $filtros['buscar'] = $buscar;

        $total = $model->contarTotal($filtros);
        $totalPags = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPags);
        $inicio = (($pagina - 1) * $porPagina) + 1;
        $fin = min($pagina * $porPagina, $total);
        $pacientes = $model->getAll($filtros, $pagina, $porPagina);

        responder(true, 'OK', [
            'pacientes' => $pacientes,
            'paginacion' => [
                'total' => $total,
                'pagina_actual' => $pagina,
                'total_paginas' => $totalPags,
                'inicio' => $inicio,
                'fin' => $fin,
            ]
        ]);
        break;

    case 'next_id':
        $tabla = $config['tabla'];
        $stmt = $db->query("SELECT COALESCE(MAX({$config['campo_id']}), 0) + 1 AS next_id 
                         FROM {$tabla}");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        responder(true, 'OK', ['next_id' => $row['next_id']]);
        break;

    case 'check_duplicado':
        $nombre = trim($_GET['nombre'] ?? '');
        $apPat = trim($_GET['apellido_paterno'] ?? '');
        $apMat = trim($_GET['apellido_materno'] ?? '');
        $excluir = (int) ($_GET['excluir'] ?? 0);

        $stmt = $db->prepare("
        SELECT COUNT(*) AS total FROM paciente
        WHERE nombre           = :nombre
          AND apellido_paterno = :ap_pat
          AND apellido_materno = :ap_mat
          AND numero_paciente <> :excluir
    ");
        $stmt->execute([
            ':nombre' => mb_strtoupper(trim($nombre), 'UTF-8'),
            ':ap_pat' => mb_strtoupper(trim($apPat), 'UTF-8'),
            ':ap_mat' => mb_strtoupper(trim($apMat), 'UTF-8'),
            ':excluir' => $excluir,
        ]);
        $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        responder(true, 'OK', ['duplicado' => $total > 0]);
        break;

    case 'buscar_cp':
        $cp = trim($_GET['cp'] ?? '');
        if (empty($cp)) {
            responder(false, 'Código postal requerido');
        }

        $stmt = $db->prepare("
        SELECT 
            cp.id_cp,
            cp.colonia,
            m.municipio,
            e.estado
        FROM codigospostales cp
        INNER JOIN municipios m ON m.id_municipio = cp.id_municipio
        INNER JOIN estados    e ON e.id_estado    = m.id_estado
        WHERE cp.codigo_postal = :cp
        ORDER BY cp.colonia
    ");
        $stmt->execute([':cp' => $cp]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($resultados)) {
            responder(false, 'Código postal no encontrado');
        }

        responder(true, 'OK', [
            'estado' => $resultados[0]['estado'],
            'municipio' => $resultados[0]['municipio'],
            'colonias' => array_map(fn($r) => [
                'id_cp' => $r['id_cp'],
                'colonia' => $r['colonia']
            ], $resultados)
        ]);
        break;

    case 'resumen':
        require_once __DIR__ . '/../controllers/DashboardController.php';
        $controller = new DashboardController($db);
        responder(true, 'OK', $controller->resumen());
        break;

    case 'get_catalogos_historial':
        responder(true, 'OK', $model->getCatalogosHistorial());
        break;

    case 'get_by_paciente':
        $numeroPaciente = (int) ($_GET['numero_paciente'] ?? 0);
        if (!$numeroPaciente)
            responder(false, 'Paciente requerido');
        responder(true, 'OK', ['planes' => $model->getByPaciente($numeroPaciente)]);
        break;

    //Planes de tratamiento    

    case 'get_catalogos_planes':
        responder(true, 'OK', $model->getCatalogos());
        break;

    case 'crear_plan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        // Decodificar procedimientos desde JSON
        $data = $_POST;
        if (!empty($_POST['procedimientos_json'])) {
            $data['procedimientos'] = json_decode($_POST['procedimientos_json'], true) ?? [];
        }

        $error = $model->validar($data);
        if ($error)
            responder(false, $error);

        $idPlan = $model->crear($data);
        if ($idPlan) {
            responder(true, 'Plan creado correctamente', ['id_plan' => $idPlan]);
        } else {
            responder(false, 'Error al crear el plan');
        }
        break;

    case 'cambiar_estatus_plan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $idPlan = (int) ($_POST['id_plan_tratamiento'] ?? 0);
        $nuevoEstatus = (int) ($_POST['id_estatus_tratamiento'] ?? 0);

        if (!$idPlan || !$nuevoEstatus)
            responder(false, 'Parámetros inválidos');

        $ok = $model->cambiarEstatus($idPlan, $nuevoEstatus);
        responder($ok, $ok ? 'Estatus actualizado' : 'Error al actualizar estatus');
        break;

    case 'agregar_procedimiento':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $idPlan = (int) ($_POST['id_plan_tratamiento'] ?? 0);
        if (!$idPlan)
            responder(false, 'Plan requerido');

        $proc = [
            'id_procedimiento' => (int) ($_POST['id_procedimiento'] ?? 0),
            'numero_pieza' => $_POST['numero_pieza'] ?? null,
            'costo_descuento' => $_POST['costo_descuento'] ?? null,
        ];

        if (!$proc['id_procedimiento'])
            responder(false, 'Procedimiento requerido');

        $ok = $model->agregarProcedimiento($idPlan, $proc);
        responder($ok, $ok ? 'Procedimiento agregado' : 'Error al agregar procedimiento');
        break;

    case 'eliminar_procedimiento':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $idDetalle = (int) ($_POST['id_detalle_plan'] ?? 0);
        if (!$idDetalle)
            responder(false, 'Detalle requerido');

        $ok = $model->eliminarProcedimiento($idDetalle);
        responder($ok, $ok ? 'Procedimiento eliminado' : 'Error al eliminar procedimiento');
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
function responder(bool $success, string $message, array $extra = []): void
{
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
function sanitizarPost(array $post): array
{
    $clean = [];
    foreach ($post as $key => $value) {
        $clean[$key] = is_string($value)
            ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8')
            : $value;
    }
    return $clean;
}