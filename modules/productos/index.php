<?php
// modules/productos/index.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Productos');

$search = sanitize($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$cat    = (int)($_GET['cat'] ?? 0);

$where  = "WHERE p.activo=1";
$params = [];
if ($search) {
    $where .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
    $s = "%$search%"; $params[] = $s; $params[] = $s;
}
if ($cat) { $where .= " AND p.categoria_id=?"; $params[] = $cat; }

$pg = paginate('productos p', $where, $params, $page, 12);
$productos   = db()->fetchAll("SELECT p.*, c.nombre AS cat_nombre FROM productos p
    LEFT JOIN categorias c ON p.categoria_id=c.id $where
    ORDER BY p.nombre ASC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}", $params);
$categorias  = db()->fetchAll("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre");

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">Inventario de Productos</div>
    <a href="crear.php" class="btn btn-primary btn-sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo Producto
    </a>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-bar">
      <input type="text" name="q" class="search-input" placeholder="Buscar producto o código..."
             value="<?= $search ?>">
      <select name="cat" style="width:160px">
        <option value="0">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
      <?php if ($search||$cat): ?><a href="index.php" class="btn btn-outline btn-sm">Limpiar</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Foto</th>
          <th>Código</th>
          <th>Producto</th>
          <th>Categoría</th>
          <th>Existencia</th>
          <th>P. Compra</th>
          <th>P. Venta</th>
          <th>IVA</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p): ?>
        <tr>
          <td>
            <img src="<?= UPLOAD_URL ?>productos/<?= sanitize($p['foto']) ?>"
                 class="img-thumb"
                 onerror="this.src='<?= BASE_URL ?>assets/img/default_product.png'">
          </td>
          <td><code style="color:var(--text-muted);font-size:12px"><?= sanitize($p['codigo'] ?? '—') ?></code></td>
          <td style="font-weight:600"><?= sanitize($p['nombre']) ?></td>
          <td><span class="badge badge-muted"><?= sanitize($p['cat_nombre'] ?? '') ?></span></td>
          <td>
            <span class="badge <?= $p['existencia'] <= 0 ? 'badge-danger' : ($p['existencia'] <= 5 ? 'badge-warning' : 'badge-success') ?>">
              <?= number_format($p['existencia'], 2) ?>
            </span>
          </td>
          <td><?= money($p['precio_compra']) ?></td>
          <td style="font-weight:600;color:var(--accent-2)"><?= money($p['precio_venta']) ?></td>
          <td><?= $p['iva'] ?>%</td>
          <td>
            <div class="td-actions">
              <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <a href="eliminar.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm btn-icon"
                 data-confirm="¿Eliminar el producto <?= sanitize($p['nombre']) ?>?">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($productos)): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            <h3>No se encontraron productos</h3>
            <a href="crear.php" class="btn btn-primary" style="margin-top:12px">Nuevo Producto</a>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pg['pages'] > 1): ?>
  <div class="card-footer">
    <small style="color:var(--text-muted)"><?= $pg['total'] ?> productos</small>
    <div class="pagination">
      <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $cat ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
