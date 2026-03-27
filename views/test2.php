<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

$buscar = '%maria%';

// Test 1: query simple
$stmt = $db->prepare("SELECT nombre, apellido_paterno FROM paciente WHERE nombre LIKE :buscar");
$stmt->bindValue(':buscar', $buscar, PDO::PARAM_STR);
$stmt->execute();
echo "<b>Test 1 - LIKE simple:</b> " . $stmt->rowCount() . " filas<br>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "<hr>";

// Test 2: con LEFT JOIN y EXISTS
$stmt2 = $db->prepare("
    SELECT COUNT(DISTINCT p.numero_paciente) AS total
    FROM paciente p
    INNER JOIN estatus s ON s.id_estatus = p.id_estatus
    LEFT JOIN contactos c ON c.numero_paciente = p.numero_paciente
    WHERE (
        p.nombre LIKE :buscar OR
        p.apellido_paterno LIKE :buscar OR
        EXISTS (
            SELECT 1 FROM contactos cx
            WHERE cx.numero_paciente = p.numero_paciente
            AND cx.valor LIKE :buscar
        )
    )
");
$stmt2->bindValue(':buscar', $buscar, PDO::PARAM_STR);
$stmt2->execute();
echo "<b>Test 2 - Con EXISTS:</b><br>";
print_r($stmt2->fetch(PDO::FETCH_ASSOC));

echo "<hr>";

// Test 3: ver qué llega desde la API
$buscarRaw = 'maria';
$filtros = ['buscar' => $buscarRaw];
echo "<b>Test 3 - Valor del filtro buscar:</b> [" . $filtros['buscar'] . "]<br>";
echo "LIKE quedaría: [%" . $filtros['buscar'] . "%]<br>";