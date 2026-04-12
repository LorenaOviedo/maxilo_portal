<?php
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . SITE_URL . "index.php");
    exit();
}
 
if (isset($_SESSION['ultimo_acceso'])) {
    if (time() - $_SESSION['ultimo_acceso'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: " . SITE_URL . "index.php?expired=1");
        exit();
    }
}
 
$_SESSION['ultimo_acceso'] = time();
 
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
 
/**
 * Verificar que el usuario tiene permiso al módulo actual.
 * Llamar al inicio de cada vista protegida:
 *   verificarPermiso('configuracion');
 *
 * Si no tiene permiso → redirige a inicio.php
 */
function verificarPermiso(string $modulo): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
 
    try {
        $db = getDB();
 
        $stmt = $db->prepare(
            "SELECT id_rol FROM usuario WHERE id_usuario = :id LIMIT 1"
        );
        $stmt->execute([':id' => (int)$_SESSION['usuario_id']]);
        $idRol = (int)$stmt->fetchColumn();
 
        if (!$idRol) {
            header("Location: " . SITE_URL . "index.php");
            exit();
        }
 
        $stmt2 = $db->prepare("
            SELECT LOWER(m.modulo) AS modulo
            FROM   rolpermiso rp
            JOIN   modulos    m ON m.id_modulo = rp.id_modulo
            WHERE  rp.id_rol = :id_rol
        ");
        $stmt2->execute([':id_rol' => $idRol]);
        $permitidos = array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'modulo');
 
        //Dejar aunque se eliminaron de los existentes
        // Normalizar acentos para comparar sin importar tildes
        $normalizar = function(string $s): string {
            return strtr(mb_strtolower($s, 'UTF-8'), [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
                'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
                'ñ'=>'n'
            ]);
        };
 
        $moduloN     = $normalizar($modulo);
        $tieneAcceso = false;
 
        foreach ($permitidos as $p) {
            $pN = $normalizar($p);
            if (strpos($pN, $moduloN) !== false ||
                strpos($moduloN, $pN) !== false) {
                $tieneAcceso = true;
                break;
            }
        }
 
        if (!$tieneAcceso) {
            header("Location: " . SITE_URL . "views/inicio.php");
            exit();
        }
 
    } catch (Exception $e) {
        error_log('verificarPermiso error: ' . $e->getMessage());
        header("Location: " . SITE_URL . "index.php");
        exit();
    }
}