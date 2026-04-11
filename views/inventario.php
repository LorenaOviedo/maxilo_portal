<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Inventario.php';
 
session_start();
 
$auth = new AuthController();
if (!$auth->verificarSesion()) {
    redirect('index.php');
}
 
$page_title = 'Inventario';
$page_css   = ['catalogos-tabla.css', 'modal.css'];
$page_js    = [];
 
// Paginación y filtros
$porPagina    = 10;
$paginaActual = max(1, (int) ($_GET['pagina']    ?? 1));
$buscar       = trim($_GET['buscar']              ?? '');
$stockBajo    = !empty($_GET['stock_bajo']);
$porCaducar   = !empty($_GET['por_caducar']);
 
$db    = getDB();
$model = new Inventario($db);
 
$filtros = [];
if ($buscar !== '')  $filtros['buscar']      = $buscar;
if ($stockBajo)      $filtros['stock_bajo']  = true;
if ($porCaducar)     $filtros['por_caducar'] = true;
 
$total        = $model->contarTotal($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
$paginaActual = min($paginaActual, $totalPaginas);
$items        = $model->getAll($filtros, $paginaActual, $porPagina);
$resumen      = $model->getResumen();
 
// Helper badge estatus stock
function badgeStock(int $stock, int $minimo): string {
    if ($stock === 0)           return 'badge-danger';
    if ($stock <= $minimo)      return 'badge-warning';
    return 'badge-success';
}
 
$hoy = date('Y-m-d');
$en30 = date('Y-m-d', strtotime('+30 days'));
 
include '../includes/header.php';
include '../includes/sidebar.php';
?>
 
        <main class="main-content">
 
            <nav class="breadcrumb">
                <div class="breadcrumb-item">
                    <a href="<?php echo view_url('catalogos_inventario.php'); ?>">Inventarios</a>
                </div>
                <span class="breadcrumb-separator">▶</span>
                <div class="breadcrumb-item">
                    <span class="breadcrumb-current">Inventario actual</span>
                </div>
            </nav>
 
            <div class="page-header">
                <h1>Inventario Actual</h1>
                <p class="page-description">Estado actual del stock de productos</p>
            </div>
 
            <!-- ── Tarjetas de resumen ─────────────────────────────────── -->
            <div class="inv-resumen" style="
                display:grid;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap:14px; margin-bottom:24px;">
 
                <div class="inv-card" style="background:#fff;border-radius:10px;
                    padding:16px 20px;box-shadow:0 1px 6px rgba(0,0,0,.07);
                    border-left:4px solid #20a89e;">
                    <div style="font-size:28px;font-weight:700;color:#20a89e;">
                        <?php echo (int)($resumen['total_productos'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Total productos</div>
                </div>
 
                <a href="?stock_bajo=1" style="text-decoration:none;">
                <div class="inv-card" style="background:#fff;border-radius:10px;
                    padding:16px 20px;box-shadow:0 1px 6px rgba(0,0,0,.07);
                    border-left:4px solid #dc3545; cursor:pointer;
                    transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.12)'"
                    onmouseout="this.style.boxShadow='0 1px 6px rgba(0,0,0,.07)'">
                    <div style="font-size:28px;font-weight:700;color:#dc3545;">
                        <?php echo (int)($resumen['sin_stock'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Sin stock</div>
                </div>
                </a>
 
                <a href="?stock_bajo=1" style="text-decoration:none;">
                <div class="inv-card" style="background:#fff;border-radius:10px;
                    padding:16px 20px;box-shadow:0 1px 6px rgba(0,0,0,.07);
                    border-left:4px solid #ffc107; cursor:pointer;
                    transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.12)'"
                    onmouseout="this.style.boxShadow='0 1px 6px rgba(0,0,0,.07)'">
                    <div style="font-size:28px;font-weight:700;color:#e65100;">
                        <?php echo (int)($resumen['stock_bajo'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Stock bajo</div>
                </div>
                </a>
 
                <a href="?por_caducar=1" style="text-decoration:none;">
                <div class="inv-card" style="background:#fff;border-radius:10px;
                    padding:16px 20px;box-shadow:0 1px 6px rgba(0,0,0,.07);
                    border-left:4px solid #fd7e14; cursor:pointer;
                    transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.12)'"
                    onmouseout="this.style.boxShadow='0 1px 6px rgba(0,0,0,.07)'">
                    <div style="font-size:28px;font-weight:700;color:#fd7e14;">
                        <?php echo (int)($resumen['por_caducar'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Por caducar</div>
                </div>
                </a>
 
                <a href="?por_caducar=1" style="text-decoration:none;">
                <div class="inv-card" style="background:#fff;border-radius:10px;
                    padding:16px 20px;box-shadow:0 1px 6px rgba(0,0,0,.07);
                    border-left:4px solid #6c757d; cursor:pointer;
                    transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,.12)'"
                    onmouseout="this.style.boxShadow='0 1px 6px rgba(0,0,0,.07)'">
                    <div style="font-size:28px;font-weight:700;color:#6c757d;">
                        <?php echo (int)($resumen['caducados'] ?? 0); ?>
                    </div>
                    <div style="font-size:13px;color:#6c757d;margin-top:4px;">Caducados</div>
                </div>
                </a>
 
            </div><!-- /.inv-resumen -->
 
            <!-- ── Filtros activos ─────────────────────────────────────── -->
            <?php if ($stockBajo || $porCaducar): ?>
            <div style="margin-bottom:14px; display:flex; align-items:center; gap:10px;">
                <span style="font-size:13px;color:#6c757d;">Filtro activo:</span>
                <?php if ($stockBajo): ?>
                    <span class="badge badge-danger">Stock bajo / sin stock</span>
                <?php endif; ?>
                <?php if ($porCaducar): ?>
                    <span class="badge badge-warning">Por caducar / caducados</span>
                <?php endif; ?>
                <a href="?" style="font-size:12px;color:#20a89e;">
                    <i class="ri-close-circle-line"></i> Limpiar filtro
                </a>
            </div>
            <?php endif; ?>
 
            <!-- ── Búsqueda ────────────────────────────────────────────── -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <input
                        type="text"
                        class="search-input"
                        placeholder="Buscar por nombre, código, marca, lote..."
                        id="searchInput"
                        value="<?php echo htmlspecialchars($buscar); ?>"
                    >
                </div>
                <button type="button" class="btn-search">
                    <i class="ri-search-line"></i> Buscar
                </button>
            </div>
 
            <!-- ── Tabla ───────────────────────────────────────────────── -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-sort="codigo_producto">CÓDIGO</th>
                            <th data-sort="nombre_producto">PRODUCTO</th>
                            <th data-sort="nombre_tipo_producto">TIPO</th>
                            <th data-sort="lote">LOTE</th>
                            <th class="text-center" data-sort="stock">STOCK</th>
                            <th class="text-center" data-sort="stock_minimo">MÍN.</th>
                            <th class="text-center" data-sort="fecha_fabricacion">F. FABRICACIÓN</th>
                            <th class="text-center" data-sort="fecha_caducidad">F. CADUCIDAD</th>
                            <th class="col-actions">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="ri-archive-line"></i>
                                    </div>
                                    <h3 class="empty-state-title">Sin registros de inventario</h3>
                                    <p class="empty-state-text">
                                        Los productos aparecen aquí una vez registrados
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($items as $i): ?>
                            <?php
                                $caducado   = $i['fecha_caducidad'] && $i['fecha_caducidad'] < $hoy;
                                $porCad     = $i['fecha_caducidad'] && $i['fecha_caducidad'] >= $hoy
                                              && $i['fecha_caducidad'] <= $en30;
                                $badgeCls   = badgeStock((int)$i['stock'], (int)$i['stock_minimo']);
                            ?>
                            <tr <?php echo $caducado ? 'style="opacity:.7;"' : ''; ?>>
 
                                <td data-label="Código">
                                        <?php echo htmlspecialchars($i['codigo_producto']); ?>
                                </td>
 
                                <td data-label="Producto">
                                    <strong><?php echo htmlspecialchars($i['nombre_producto']); ?></strong>
                                    <?php if ($i['marca']): ?>
                                        <div style="font-size:12px;color:#6c757d;">
                                            <?php echo htmlspecialchars($i['marca']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
 
                                <td data-label="Tipo">
                                    <span class="badge badge-info" style="font-size:11px;">
                                        <?php echo htmlspecialchars($i['nombre_tipo_producto']); ?>
                                    </span>
                                </td>
 
                                <td data-label="Lote">
                                    <?php echo htmlspecialchars($i['lote'] ?? '—'); ?>
                                </td>
 
                                <td class="text-center" data-label="Stock">
                                    <span class="badge <?php echo $badgeCls; ?>">
                                        <?php echo (int)$i['stock']; ?>
                                    </span>
                                </td>
 
                                <td class="text-center" data-label="Mínimo">
                                    <?php echo (int)$i['stock_minimo']; ?>
                                </td>
 
                                <td class="text-center" data-label="F. Fabricación">
                                    <?php echo $i['fecha_fabricacion']
                                        ? date('d/m/Y', strtotime($i['fecha_fabricacion']))
                                        : '—'; ?>
                                </td>
 
                                <td class="text-center" data-label="F. Caducidad">
                                    <?php if (!$i['fecha_caducidad']): ?>
                                        <span style="color:#adb5bd;">—</span>
                                    <?php elseif ($caducado): ?>
                                        <span class="badge badge-danger">
                                            <?php echo date('d/m/Y', strtotime($i['fecha_caducidad'])); ?>
                                        </span>
                                    <?php elseif ($porCad): ?>
                                        <span class="badge badge-warning">
                                            <?php echo date('d/m/Y', strtotime($i['fecha_caducidad'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo date('d/m/Y', strtotime($i['fecha_caducidad'])); ?>
                                    <?php endif; ?>
                                </td>
 
                                <td class="col-actions" data-label="Acciones">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-action btn-view"
                                            title="Ver detalle"
                                            onclick="inventarioController.ver(<?php echo $i['id_inventario']; ?>)">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                </td>
 
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- ── Paginación ──────────────────────────────────────────── -->
            <?php if ($total > 0): ?>
            <?php
                $params   = http_build_query(array_filter([
                    'buscar'      => $buscar,
                    'stock_bajo'  => $stockBajo  ? '1' : '',
                    'por_caducar' => $porCaducar ? '1' : '',
                ]));
                $urlBase  = view_url('inventario.php') . '?' . $params . '&pagina=';
                $inicio   = (($paginaActual - 1) * $porPagina) + 1;
                $fin      = min($paginaActual * $porPagina, $total);
            ?>
            <div class="pagination">
                <a href="<?php echo $urlBase . ($paginaActual - 1); ?>"
                   class="pagination-btn <?php echo $paginaActual <= 1 ? 'disabled' : ''; ?>"
                   <?php echo $paginaActual <= 1 ? 'onclick="return false;"' : ''; ?>>
                    <i class="ri-arrow-left-line"></i> Anterior
                </a>
                <span class="pagination-info">
                    Mostrando <?php echo $inicio; ?>–<?php echo $fin; ?>
                    de <?php echo $total; ?> producto(s)
                    &nbsp;·&nbsp; Página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?>
                </span>
                <a href="<?php echo $urlBase . ($paginaActual + 1); ?>"
                   class="pagination-btn <?php echo $paginaActual >= $totalPaginas ? 'disabled' : ''; ?>"
                   <?php echo $paginaActual >= $totalPaginas ? 'onclick="return false;"' : ''; ?>>
                    Siguiente <i class="ri-arrow-right-line"></i>
                </a>
            </div>
            <?php endif; ?>
 
        </main>
 
<script>
var API_URL = '<?php echo ajax_url('Api.php'); ?>';
</script>
 
<?php include '../includes/modal_inventario.php'; ?>
<script src="<?php echo asset('js/inventario.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
 
<?php include '../includes/footer.php'; ?>