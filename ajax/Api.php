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

    'odontograma' => [
        'modelo' => 'Odontograma',
        'archivo' => __DIR__ . '/../models/Odontograma.php',
        'campo_id' => 'id_odontograma',
    ],

    'especialistas' => [
        'modelo' => 'Especialista',
        'archivo' => __DIR__ . '/../models/Especialista.php',
        'campo_id' => 'id_especialista',
    ],

    'citas' => [
        'modelo' => 'Cita',
        'archivo' => __DIR__ . '/../models/Cita.php',
        'campo_id' => 'id_cita',
    ],

    'proveedores' => [
        'modelo' => 'Proveedor',
        'archivo' => __DIR__ . '/../models/Proveedor.php',
        'campo_id' => 'id_proveedor',
    ],

    'compras' => [
        'modelo' => 'OrdenCompra',
        'archivo' => __DIR__ . '/../models/OrdenCompra.php',
        'campo_id' => 'id_compra',
    ],

    'productos' => [
        'modelo' => 'Producto',
        'archivo' => __DIR__ . '/../models/Producto.php',
        'campo_id' => 'id_producto',
    ],

    'inventario' => [
        'modelo' => 'Inventario',
        'archivo' => __DIR__ . '/../models/Inventario.php',
        'campo_id' => 'id_inventario',
    ],

    'movimientos' => [
        'modelo' => 'MovimientoInventario',
        'archivo' => __DIR__ . '/../models/MovimientoInventario.php',
        'campo_id' => 'id_movimiento',
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

    case 'eliminar_plan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $idPlan = (int) ($_POST['id_plan_tratamiento'] ?? 0);
        if (!$idPlan)
            responder(false, 'Plan requerido');

        $ok = $model->eliminarPlan($idPlan);
        responder($ok, $ok ? 'Plan eliminado correctamente' : 'Error al eliminar el plan');
        break;


    case 'delete':
        if ($modulo !== 'productos')
            break;  // solo para productos por ahora
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $id = (int) ($_POST['id_producto'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        $ok = $model->delete($id);
        responder($ok, $ok ? 'Producto eliminado correctamente' : 'Error al eliminar. Verifique que el producto no tenga órdenes de compra asociadas');
        break;


    case 'buscar_inventario':
        $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
        $lote = trim($_GET['lote'] ?? '');
        if (empty($codigo))
            responder(false, 'El codigo del producto es requerido');
        if (empty($lote))
            responder(false, 'El lote es requerido');
        $inv = $model->buscarInventario($codigo, $lote);
        if (!$inv)
            responder(false, 'No se encontro ningun producto con ese codigo y lote', ['inventario' => null]);
        responder(true, 'OK', ['inventario' => $inv]);
        break;

    // Devuelve los lotes disponibles en inventario para un producto dado
    case 'get_lotes':
        $idProducto = (int) ($_GET['id_producto'] ?? 0);
        if (!$idProducto)
            responder(false, 'ID de producto requerido');
        $lotes = $model->getLotesPorProducto($idProducto);
        responder(true, 'OK', ['lotes' => $lotes]);
        break;

    // Devuelve todos los productos que tienen entrada en inventario (para el select)
    case 'get_productos_inventario':
        $productos = $model->getProductosConInventario();
        responder(true, 'OK', ['productos' => $productos]);
        break;

    case 'registrar_movimiento':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $data = sanitizarPost($_POST);
        $error = $model->validar($data);
        if ($error)
            responder(false, $error);
        $idUsuario = (int) ($_SESSION['usuario_id'] ?? $_POST['id_usuario'] ?? 0);
        if (!$idUsuario)
            responder(false, 'Usuario no identificado');
        $resultado = $model->registrar($data, $idUsuario);
        responder(
            $resultado['success'],
            $resultado['message'],
            array_filter($resultado, fn($k) => $k !== 'success' && $k !== 'message', ARRAY_FILTER_USE_KEY)
        );
        break;


    // ── ODONTOGRAMA: registros de un paciente ─────────────────────────────────
    // GET ajax/api.php?modulo=odontograma&accion=get_by_paciente_odontograma&numero_paciente=X
    // Retorna: { success, registros: { "[pieza]": [...] } }
    case 'get_catalogos_odontograma':
        responder(true, 'OK', $model->getCatalogos());
        break;


    // ── ODONTOGRAMA: registros de un paciente ─────────────────────────────────
    // GET ajax/api.php?modulo=odontograma&accion=get_by_paciente_odontograma&numero_paciente=X
    // Retorna: { success, registros: { "[pieza]": [...] } }
    case 'get_by_paciente_odontograma':
        $numeroPaciente = (int) ($_GET['numero_paciente'] ?? 0);
        if (!$numeroPaciente)
            responder(false, 'numero_paciente requerido');

        $registros = $model->getByPaciente($numeroPaciente);
        responder(true, 'OK', ['registros' => $registros]);
        break;


    // ── ODONTOGRAMA: guardar hallazgo ─────────────────────────────────────────
    // POST ajax/api.php?modulo=odontograma&accion=guardar_odontograma
    // Body JSON: { numero_paciente, id_especialista, numero_pieza,
    //              id_anomalia, id_caras[], id_procedimiento }
    case 'guardar_odontograma':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $numeroPaciente = (int) ($body['numero_paciente'] ?? 0);
        $idEspecialista = (int) ($body['id_especialista'] ?? 0);
        $numeroPieza = (int) ($body['numero_pieza'] ?? 0);
        $idAnomalia = (int) ($body['id_anomalia'] ?? 0);
        $idCaras = $body['id_caras'] ?? [];
        $idProcedimiento = (int) ($body['id_procedimiento'] ?? 0);
        $idEstatus = (int) ($body['id_estatus_hallazgo'] ?? 1);

        if (!$numeroPaciente || !$idEspecialista || !$numeroPieza || !$idAnomalia)
            responder(false, 'Campos requeridos: numero_paciente, id_especialista, numero_pieza, id_anomalia');

        if (empty($idCaras))
            responder(false, 'Se requiere al menos una cara dental (id_caras)');

        if (!$idProcedimiento)
            responder(false, 'El procedimiento es obligatorio (id_procedimiento)');

        $resultado = $model->guardar([
            'numero_paciente' => $numeroPaciente,
            'id_especialista' => $idEspecialista,
            'numero_pieza' => $numeroPieza,
            'id_anomalia' => $idAnomalia,
            'id_caras' => $idCaras,
            'id_procedimiento' => $idProcedimiento,
            'id_estatus_hallazgo' => $idEstatus,
        ]);

        responder(
            $resultado['success'],
            $resultado['message'] ?? 'Registro guardado correctamente',
            $resultado
        );
        break;


    // ── ODONTOGRAMA: actualizar estatus de hallazgo ───────────────────────────
    // POST Body JSON: { id_odontograma, id_estatus_hallazgo, numero_paciente }
    case 'actualizar_estatus_odontograma':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $idOdontograma = (int) ($body['id_odontograma'] ?? 0);
        $idEstatus = (int) ($body['id_estatus_hallazgo'] ?? 0);
        $numeroPaciente = (int) ($body['numero_paciente'] ?? 0);

        if (!$idOdontograma || !$idEstatus || !$numeroPaciente)
            responder(false, 'id_odontograma, id_estatus_hallazgo y numero_paciente son requeridos');

        $resultado = $model->actualizarEstatus($idOdontograma, $idEstatus, $numeroPaciente);
        responder(
            $resultado['success'],
            $resultado['message'] ?? 'Estatus actualizado correctamente'
        );
        break;


    // ── ODONTOGRAMA: eliminar hallazgo ────────────────────────────────────────
    // POST ajax/api.php?modulo=odontograma&accion=eliminar_odontograma
    // Body JSON: { id_odontograma, numero_paciente }
    case 'eliminar_odontograma':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $idOdontograma = (int) ($body['id_odontograma'] ?? 0);
        $numeroPaciente = (int) ($body['numero_paciente'] ?? 0);

        if (!$idOdontograma || !$numeroPaciente)
            responder(false, 'id_odontograma y numero_paciente son requeridos');

        $resultado = $model->eliminar($idOdontograma, $numeroPaciente);
        responder(
            $resultado['success'],
            $resultado['message'] ?? 'Registro eliminado correctamente'
        );
        break;


    // ── Especialistas: catálogos ──────────────────────────────────────────────
    case 'get_catalogos_especialistas':
        responder(true, 'OK', $model->getCatalogos());
        break;

    // ── Especialistas: detalle por id ─────────────────────────────────────────
    case 'get_especialista':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id)
            responder(false, 'ID inválido');
        $result = $model->getById($id);
        if (!$result)
            responder(false, 'Especialista no encontrado');
        responder(true, 'OK', ['especialista' => $result]);
        break;

    // ── Especialistas: crear ──────────────────────────────────────────────────
    case 'crear_especialista':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $data = sanitizarPost($_POST);

        // Decodificar especialidades — sanitizarPost rompería el JSON si no se hace antes
        if (!empty($_POST['especialidades_json'])) {
            $data['especialidades'] = json_decode($_POST['especialidades_json'], true) ?? [];
        }

        $error = $model->validar($data);
        if ($error)
            responder(false, $error);

        $id = $model->create($data);
        if ($id) {
            responder(true, 'Especialista creado correctamente', ['id' => $id]);
        } else {
            responder(false, 'Error al crear el especialista');
        }
        break;

    // ── Especialistas: actualizar ─────────────────────────────────────────────
    case 'actualizar_especialista':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $id = (int) ($_POST['id_especialista'] ?? 0);
        if (!$id)
            responder(false, 'ID inválido');

        $data = sanitizarPost($_POST);

        // Decodificar especialidades ANTES de sanitizarPost (ya aplicado arriba)
        if (!empty($_POST['especialidades_json'])) {
            $data['especialidades'] = json_decode($_POST['especialidades_json'], true) ?? [];
        }

        $error = $model->validar($data);
        if ($error)
            responder(false, $error);

        $ok = $model->update($id, $data);
        responder(
            $ok,
            $ok ? 'Especialista actualizado correctamente' : 'Error al actualizar'
        );
        break;

    // ── Especialistas: cambiar estatus ────────────────────────────────────────
    case 'status_especialista':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Método no permitido');

        $id = (int) ($_POST['id_especialista'] ?? 0);
        $nuevoEstatus = (int) ($_POST['id_estatus'] ?? 0);

        if (!$id || !in_array($nuevoEstatus, [1, 2]))
            responder(false, 'Parámetros inválidos');

        $ok = $model->cambiarEstatus($id, $nuevoEstatus);
        $accion = $nuevoEstatus === 1 ? 'activado' : 'desactivado';
        responder($ok, $ok ? "Especialista {$accion} correctamente" : 'Error al cambiar estatus');
        break;

    // ── Especialistas: búsqueda paginada ──────────────────────────────────────
    case 'buscar_especialistas':
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

        responder(true, 'OK', [
            'especialistas' => $model->getAll($filtros, $pagina, $porPagina),
            'paginacion' => [
                'total' => $total,
                'pagina_actual' => $pagina,
                'total_paginas' => $totalPags,
                'inicio' => $inicio,
                'fin' => $fin,
            ],
        ]);
        break;

    case 'get_proveedor':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        $result = $model->getById($id);
        if (!$result)
            responder(false, 'Proveedor no encontrado');
        responder(true, 'OK', ['proveedor' => $result]);
        break;

    case 'crear_proveedor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $data = sanitizarPost($_POST);
        $error = $model->validar($data);
        if ($error)
            responder(false, $error);
        if ($model->rfcExiste($data['rfc']))
            responder(false, 'Ya existe un proveedor con ese RFC');
        $id = $model->create($data);
        if ($id)
            responder(true, 'Proveedor creado correctamente', ['id' => $id]);
        responder(false, 'Error al crear el proveedor');
        break;

    case 'actualizar_proveedor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $id = (int) ($_POST['id_proveedor'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        $data = sanitizarPost($_POST);
        $error = $model->validar($data);
        if ($error)
            responder(false, $error);
        if ($model->rfcExiste($data['rfc'], $id))
            responder(false, 'Ya existe otro proveedor con ese RFC');
        $ok = $model->update($id, $data);
        responder($ok, $ok ? 'Proveedor actualizado correctamente' : 'Error al actualizar');
        break;

    case 'status_proveedor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $id = (int) ($_POST['id_proveedor'] ?? 0);
        $nuevoEstatus = (int) ($_POST['id_estatus'] ?? 0);
        if (!$id || !in_array($nuevoEstatus, [1, 2]))
            responder(false, 'Parametros invalidos');
        $ok = $model->cambiarEstatus($id, $nuevoEstatus);
        $texto = $nuevoEstatus === 1 ? 'activado' : 'desactivado';
        responder($ok, $ok ? "Proveedor {$texto} correctamente" : 'Error al cambiar estatus');
        break;

    case 'get_compra':
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        $result = $model->getById($id);
        if (!$result)
            responder(false, 'Orden no encontrada');
        responder(true, 'OK', ['compra' => $result]);
        break;

    case 'crear_compra':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $data = sanitizarPost($_POST);
        $data['detalle'] = json_decode($_POST['detalle_json'] ?? '[]', true) ?? [];
        $error = $model->validar($data);
        if ($error)
            responder(false, $error);
        if ($model->folioExiste($data['folio_compra']))
            responder(false, 'Ya existe una orden con ese folio');
        $id = $model->create($data);
        if ($id)
            responder(true, 'Orden de compra creada correctamente', ['id' => $id]);
        responder(false, 'Error al crear la orden de compra');
        break;

    case 'actualizar_compra':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $id = (int) ($_POST['id_compra'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        $data = sanitizarPost($_POST);
        $data['detalle'] = json_decode($_POST['detalle_json'] ?? '[]', true) ?? [];
        $error = $model->validar($data);
        if ($error)
            responder(false, $error);
        if ($model->folioExiste($data['folio_compra'], $id))
            responder(false, 'Ya existe otra orden con ese folio');
        $ok = $model->update($id, $data);
        responder($ok, $ok ? 'Orden actualizada correctamente' : 'Error al actualizar');
        break;

    case 'cancelar_compra':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            responder(false, 'Metodo no permitido');
        $id = (int) ($_POST['id_compra'] ?? 0);
        if (!$id)
            responder(false, 'ID invalido');
        // Buscar id_estatus_orden_compra de Cancelada en BD
        $stmt = $db->query("SELECT id_estatus_orden_compra FROM estadosordencompra WHERE LOWER(estatus_orden_compra) LIKE '%cancel%' LIMIT 1");
        $idCancelada = (int) ($stmt->fetchColumn() ?: 4);
        $ok = $model->cambiarEstatus($id, $idCancelada);
        responder($ok, $ok ? 'Orden cancelada correctamente' : 'Error al cancelar');
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