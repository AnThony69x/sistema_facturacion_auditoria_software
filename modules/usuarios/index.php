<?php
// modules/usuarios/index.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
requireRole('admin');
define('PAGE_TITLE', 'Usuarios del Sistema');

$search = sanitize($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));

$where  = "WHERE activo=1";
$params = [];
if ($search) {
    $where  .= " AND (nombres LIKE ? OR username LIKE ?)";
    $s = "%$search%"; $params = [$s, $s];
}

$pg       = paginate('usuarios', $where, $params, $page, 15);
$usuarios = db()->fetchAll("SELECT * FROM usuarios $where ORDER BY nombres ASC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}", $params);

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">Gestión de Usuarios</div>
    <a href="crear.php" class="btn btn-primary btn-sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo Usuario
    </a>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-bar">
      <input type="text" name="q" class="search-input" placeholder="Buscar por nombre o usuario..."
             value="<?= $search ?>">
      <button type="submit" class="btn btn-outline btn-sm">Buscar</button>
      <?php if ($search): ?><a href="index.php" class="btn btn-outline btn-sm">Limpiar</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Foto</th>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Rol</th>
          <th>Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td>
            <img src="<?= UPLOAD_URL ?>usuarios/<?= sanitize($u['foto']) ?>"
                 class="avatar-sm"
                 onerror="this.src='<?= BASE_URL ?>assets/img/default_user.png'">
          </td>
          <td style="font-weight:600"><?= sanitize($u['nombres']) ?></td>
          <td><code style="color:var(--accent-2);font-size:12px">@<?= sanitize($u['username']) ?></code></td>
          <td>
            <?php
            $rolBadge = ['admin'=>'badge-orange','cajero'=>'badge-info','bodeguero'=>'badge-muted'];
            ?>
            <span class="badge <?= $rolBadge[$u['rol']] ?? 'badge-muted' ?>"><?= ucfirst($u['rol']) ?></span>
          </td>
          <td style="color:var(--text-muted);font-size:12px"><?= formatDate($u['created_at']) ?></td>
          <td>
            <div class="td-actions">
              <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <a href="eliminar.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm btn-icon"
                 data-confirm="¿Eliminar al usuario <?= sanitize($u['nombres']) ?>?">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h3>No se encontraron usuarios</h3>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pg['pages'] > 1): ?>
  <div class="card-footer">
    <small style="color:var(--text-muted)"><?= $pg['total'] ?> usuarios</small>
    <div class="pagination">
      <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
