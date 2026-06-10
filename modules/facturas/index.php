<?php
// modules/facturas/index.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Facturas');

$search = sanitize($_GET['q'] ?? '');
$estado = sanitize($_GET['estado'] ?? '');
$desde  = sanitize($_GET['desde'] ?? '');
$hasta  = sanitize($_GET['hasta'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));

$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where  .= " AND (numero_factura LIKE ? OR cliente LIKE ? OR cedula LIKE ?)";
    $s = "%$search%"; $params = array_merge($params, [$s,$s,$s]);
}
if ($estado) { $where .= " AND estado=?"; $params[] = $estado; }
if ($desde)  { $where .= " AND fecha_emision >= ?"; $params[] = $desde; }
if ($hasta)  { $where .= " AND fecha_emision <= ?"; $params[] = $hasta; }

$pg       = paginate('v_facturas', $where, $params, $page, 20);
$facturas = db()->fetchAll("SELECT * FROM v_facturas $where ORDER BY id DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}", $params);
$totalVentas = db()->fetchOne("SELECT COALESCE(SUM(total),0) t FROM v_facturas $where AND estado='autorizada'", $params)['t'];

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">Registro de Facturas</div>
    <div style="display:flex;gap:8px;align-items:center">
      <span style="font-size:12px;color:var(--text-muted)">Total autorizadas: <strong style="color:var(--success)"><?= money($totalVentas) ?></strong></span>
      <a href="crear.php" class="btn btn-primary btn-sm">+ Nueva Factura</a>
    </div>
  </div>

  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-bar">
      <input type="text" name="q" class="search-input" placeholder="N° factura, cliente, cédula..."
             value="<?= $search ?>">
      <select name="estado" style="width:140px">
        <option value="">Todos</option>
        <option value="borrador"   <?= $estado==='borrador'?'selected':'' ?>>Borrador</option>
        <option value="pendiente"  <?= $estado==='pendiente'?'selected':'' ?>>Pendiente</option>
        <option value="autorizada" <?= $estado==='autorizada'?'selected':'' ?>>Autorizada</option>
        <option value="anulada"    <?= $estado==='anulada'?'selected':'' ?>>Anulada</option>
      </select>
      <input type="date" name="desde" value="<?= $desde ?>" style="width:140px">
      <input type="date" name="hasta" value="<?= $hasta ?>" style="width:140px">
      <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
      <?php if ($search||$estado||$desde||$hasta): ?>
        <a href="index.php" class="btn btn-outline btn-sm">Limpiar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>N° Factura</th>
          <th>Fecha</th>
          <th>Cliente</th>
          <th>Cédula/RUC</th>
          <th style="text-align:right">Total</th>
          <th>Pago</th>
          <th>Estado SRI</th>
          <th>Correo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($facturas as $f): ?>
        <tr>
          <td>
            <code style="color:var(--accent-2);font-size:12px;font-weight:700"><?= sanitize($f['numero_factura']) ?></code>
          </td>
          <td style="color:var(--text-muted);font-size:12px;white-space:nowrap"><?= formatDate($f['fecha_emision']) ?></td>
          <td style="font-weight:500"><?= sanitize($f['cliente']) ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= sanitize($f['cedula']) ?></td>
          <td style="text-align:right;font-weight:700"><?= money($f['total']) ?></td>
          <td style="font-size:12px;text-transform:capitalize"><?= sanitize($f['forma_pago']) ?></td>
          <td>
            <?php
            $bm = ['autorizada'=>'badge-success','anulada'=>'badge-danger','pendiente'=>'badge-warning','borrador'=>'badge-muted'];
            ?>
            <span class="badge <?= $bm[$f['estado']] ?? 'badge-muted' ?>"><?= ucfirst($f['estado']) ?></span>
          </td>
          <td style="text-align:center">
            <?php if ($f['enviado_correo']): ?>
              <span title="Enviado" style="color:var(--success)">✉</span>
            <?php else: ?>
              <span style="color:var(--text-dim)">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="td-actions">
              <a href="ver.php?id=<?= $f['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Ver">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
              <?php if ($f['estado'] === 'borrador'): ?>
              <a href="editar.php?id=<?= $f['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php endif; ?>
              <a href="imprimir.php?id=<?= $f['id'] ?>" target="_blank" class="btn btn-outline btn-sm btn-icon" title="Imprimir">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($facturas)): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
            <h3>No se encontraron facturas</h3>
            <a href="crear.php" class="btn btn-primary" style="margin-top:12px">Nueva Factura</a>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pg['pages'] > 1): ?>
  <div class="card-footer">
    <small style="color:var(--text-muted)"><?= $pg['total'] ?> facturas</small>
    <div class="pagination">
      <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&estado=<?= $estado ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>"
           class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
