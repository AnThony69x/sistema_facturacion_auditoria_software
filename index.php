<?php
// index.php
require_once 'includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Dashboard');

// Stats
$totalClientes  = db()->fetchOne("SELECT COUNT(*) c FROM clientes WHERE activo=1")['c'];
$totalProductos = db()->fetchOne("SELECT COUNT(*) c FROM productos WHERE activo=1")['c'];
$facturasHoy    = db()->fetchOne("SELECT COUNT(*) c FROM facturas WHERE DATE(fecha_emision)=CURDATE()")['c'];
$ventasHoy      = db()->fetchOne("SELECT COALESCE(SUM(total),0) c FROM facturas WHERE DATE(fecha_emision)=CURDATE() AND estado!='anulada'")['c'];
$ultimasFacturas= db()->fetchAll("SELECT * FROM v_facturas ORDER BY id DESC LIMIT 8");
$topProductos   = db()->fetchAll("SELECT p.nombre, SUM(fd.cantidad) total_vendido
    FROM factura_detalle fd JOIN productos p ON fd.producto_id=p.id
    GROUP BY p.id ORDER BY total_vendido DESC LIMIT 5");

include 'includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <div class="stat-value"><?= $facturasHoy ?></div>
      <div class="stat-label">Facturas hoy</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <div>
      <div class="stat-value"><?= money($ventasHoy) ?></div>
      <div class="stat-label">Ventas hoy</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    </div>
    <div>
      <div class="stat-value"><?= $totalClientes ?></div>
      <div class="stat-label">Clientes</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    </div>
    <div>
      <div class="stat-value"><?= $totalProductos ?></div>
      <div class="stat-label">Productos</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

  <!-- Últimas facturas -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Últimas Facturas</div>
      <a href="<?= BASE_URL ?>modules/facturas/index.php" class="btn btn-outline btn-sm">Ver todas</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>N° Factura</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultimasFacturas as $f): ?>
          <tr>
            <td><code style="color:var(--accent-2);font-size:12px"><?= sanitize($f['numero_factura']) ?></code></td>
            <td><?= sanitize($f['cliente']) ?></td>
            <td><?= formatDate($f['fecha_emision']) ?></td>
            <td style="font-weight:600"><?= money($f['total']) ?></td>
            <td>
              <?php
              $badgeMap = ['autorizada'=>'badge-success','anulada'=>'badge-danger','pendiente'=>'badge-warning','borrador'=>'badge-muted'];
              $badge    = $badgeMap[$f['estado']] ?? 'badge-muted';
              ?>
              <span class="badge <?= $badge ?>"><?= ucfirst($f['estado']) ?></span>
            </td>
            <td>
              <a href="<?= BASE_URL ?>modules/facturas/ver.php?id=<?= $f['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Ver">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ultimasFacturas)): ?>
          <tr><td colspan="6"><div class="empty-state"><p>No hay facturas registradas</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top productos -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Productos Más Vendidos</div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($topProductos)): ?>
        <div class="empty-state" style="padding:30px"><p>Sin datos</p></div>
      <?php else: ?>
        <?php foreach ($topProductos as $i => $p): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border-2)">
          <div style="width:26px;height:26px;border-radius:50%;background:var(--accent-glow);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">
            <?= $i+1 ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($p['nombre']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= $p['total_vendido'] ?> unidades</div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
