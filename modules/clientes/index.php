<?php
// modules/clientes/index.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Clientes');

$search = sanitize($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));

$where  = "WHERE c.activo = 1";
$params = [];
if ($search) {
    $where  .= " AND (c.cedula LIKE ? OR c.nombres LIKE ? OR c.correo LIKE ? OR c.telefono LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s, $s];
}

$pg = paginate('clientes c', $where, $params, $page, 15);
$clientes = db()->fetchAll(
    "SELECT * FROM clientes c $where ORDER BY c.nombres ASC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}",
    $params
);

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">Gestión de Clientes</div>
    <a href="crear.php" class="btn btn-primary btn-sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo Cliente
    </a>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-bar">
      <input type="text" name="q" class="search-input" placeholder="Buscar por cédula, nombre, correo..."
             value="<?= $search ?>">
      <button type="submit" class="btn btn-outline btn-sm">Buscar</button>
      <?php if ($search): ?>
        <a href="index.php" class="btn btn-outline btn-sm">Limpiar</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Identificación</th>
          <th>Nombre / Razón Social</th>
          <th>Teléfono</th>
          <th>Correo</th>
          <th>Dirección</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr>
          <td style="color:var(--text-muted)"><?= $c['id'] ?></td>
          <td>
            <span class="badge badge-muted"><?= strtoupper($c['tipo_identificacion']) ?></span>
            <code style="margin-left:6px;color:var(--accent-2);font-size:12px"><?= sanitize($c['cedula']) ?></code>
          </td>
          <td style="font-weight:600"><?= sanitize($c['nombres']) ?></td>
          <td><?= sanitize($c['telefono']) ?></td>
          <td><?= sanitize($c['correo']) ?></td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted)"><?= sanitize($c['direccion']) ?></td>
          <td>
            <div class="td-actions">
              <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <a href="eliminar.php?id=<?= $c['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Eliminar"
                 data-confirm="¿Eliminar al cliente <?= sanitize($c['nombres']) ?>?">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clientes)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No se encontraron clientes</h3>
            <p><?= $search ? 'Intenta con otros términos de búsqueda.' : 'Crea el primer cliente.' ?></p>
            <a href="crear.php" class="btn btn-primary" style="margin-top:12px">Nuevo Cliente</a>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pg['pages'] > 1): ?>
  <div class="card-footer">
    <small style="color:var(--text-muted)"><?= $pg['total'] ?> registros</small>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
