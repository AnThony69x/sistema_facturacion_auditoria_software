<?php
// modules/facturas/ver.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id      = (int)($_GET['id'] ?? 0);
$factura = db()->fetchOne("SELECT * FROM v_facturas WHERE id=?", [$id]);
if (!$factura) { setFlash('error','Factura no encontrada.'); header('Location: index.php'); exit; }

$detalle = db()->fetchAll(
    "SELECT fd.*, p.nombre AS prod_nombre, p.codigo
     FROM factura_detalle fd JOIN productos p ON fd.producto_id = p.id
     WHERE fd.factura_id = ?", [$id]
);
$facturaRaw = db()->fetchOne("SELECT * FROM facturas WHERE id=?", [$id]);
$config     = getConfig();
$cliente    = db()->fetchOne("SELECT * FROM clientes WHERE id=?", [$facturaRaw['cliente_id']]);

define('PAGE_TITLE', 'Factura ' . $factura['numero_factura']);
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
.action-panel { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
.factura-print { max-width: 900px; }
@media print {
  .action-panel, .topbar, .sidebar { display: none !important; }
  .main-content { margin-left: 0 !important; }
}
</style>

<!-- Action Bar -->
<div class="action-panel">
  <a href="index.php" class="btn btn-outline btn-sm">← Volver</a>

  <?php if ($factura['estado'] === 'borrador'): ?>
  <a href="editar.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    Modificar Factura
  </a>
  <?php endif; ?>

  <a href="imprimir.php?id=<?= $id ?>" target="_blank" class="btn btn-outline btn-sm">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir
  </a>

  <?php if (in_array($factura['estado'], ['borrador','pendiente'])): ?>
  <form method="POST" action="facturar_sri.php" style="display:inline" id="formSRI">
    <input type="hidden" name="factura_id" value="<?= $id ?>">
    <?= csrfField() ?>
    <button type="submit" class="btn btn-sri" id="btnFacturar">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      ⚡ Facturar Electrónicamente (SRI)
    </button>
  </form>
  <?php endif; ?>

  <?php if ($factura['estado'] === 'autorizada' && !$factura['enviado_correo'] && $factura['cliente_correo']): ?>
  <form method="POST" action="enviar_correo.php" style="display:inline">
    <input type="hidden" name="factura_id" value="<?= $id ?>">
    <?= csrfField() ?>
    <button type="submit" class="btn btn-success btn-sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      Reenviar Correo
    </button>
  </form>
  <?php endif; ?>

  <?php if ($factura['estado'] === 'borrador'): ?>
  <a href="anular.php?id=<?= $id ?>" class="btn btn-danger btn-sm"
     data-confirm="¿Anular esta factura?">
    Anular
  </a>
  <?php endif; ?>
</div>

<!-- Factura visual -->
<div class="factura-print">

  <!-- Cabecera -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:24px;align-items:center">

        <!-- Empresa -->
        <div>
          <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:800"><?= sanitize($config['nombre_comercial'] ?? '') ?></div>
          <div style="font-size:12px;color:var(--text-muted)">RUC: <?= sanitize($config['ruc'] ?? '') ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= sanitize($config['direccion_matriz'] ?? '') ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= sanitize($config['telefono'] ?? '') ?></div>
        </div>

        <!-- Tipo documento -->
        <div style="text-align:center;background:var(--bg-3);border-radius:var(--radius);padding:16px 24px;border:1px solid var(--border)">
          <div style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted)">Comprobante</div>
          <div style="font-family:var(--font-display);font-weight:800;font-size:1rem;margin:4px 0">FACTURA</div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--text-muted)">Nro.</div>
          <div style="font-family:var(--font-display);font-weight:700;color:var(--accent-2)"><?= sanitize($factura['numero_factura']) ?></div>
        </div>

        <!-- Estado / Auth -->
        <div style="text-align:right">
          <?php
          $badgeMap = ['autorizada'=>'badge-success','anulada'=>'badge-danger','pendiente'=>'badge-warning','borrador'=>'badge-muted'];
          ?>
          <span class="badge <?= $badgeMap[$factura['estado']] ?? 'badge-muted' ?>" style="font-size:13px;padding:5px 14px">
            <?= strtoupper($factura['estado']) ?>
          </span>
          <?php if ($factura['enviado_correo']): ?>
          <span class="badge badge-info" style="margin-left:6px">✉ Enviado</span>
          <?php endif; ?>
          <div style="margin-top:8px;font-size:12px;color:var(--text-muted)">Fecha: <?= formatDate($factura['fecha_emision']) ?></div>
          <?php if ($facturaRaw['numero_autorizacion']): ?>
          <div style="margin-top:4px;font-size:11px;color:var(--success)">✓ Auth: <?= sanitize($facturaRaw['numero_autorizacion']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Clave de acceso -->
      <?php if ($factura['clave_acceso']): ?>
      <div style="margin-top:16px;padding:10px 14px;background:var(--bg-3);border-radius:var(--radius-sm);font-size:11px;border:1px solid var(--border-2)">
        <strong>Clave de Acceso:</strong>
        <code style="color:var(--accent-2);margin-left:8px;word-break:break-all"><?= sanitize($factura['clave_acceso']) ?></code>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Datos del cliente -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
        <div>
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);margin-bottom:3px">Cliente</div>
          <div style="font-weight:600"><?= sanitize($factura['cliente']) ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= sanitize($factura['cedula']) ?></div>
        </div>
        <div>
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);margin-bottom:3px">Contacto</div>
          <div style="font-size:13px"><?= sanitize($factura['cliente_correo']) ?></div>
        </div>
        <div>
          <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);margin-bottom:3px">Forma de Pago</div>
          <div style="font-size:13px;font-weight:600;text-transform:capitalize"><?= sanitize($facturaRaw['forma_pago']) ?></div>
          <div style="font-size:11px;color:var(--text-muted)">Emisor: <?= sanitize($factura['usuario']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detalle de productos -->
  <div class="card" style="margin-bottom:16px">
    <div class="table-wrapper">
      <table class="detail-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Descripción</th>
            <th style="text-align:right">Cant.</th>
            <th style="text-align:right">P. Unitario</th>
            <th style="text-align:right">Desc.</th>
            <th style="text-align:right">Base Imp.</th>
            <th style="text-align:right">IVA %</th>
            <th style="text-align:right">IVA $</th>
            <th style="text-align:right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($detalle as $i => $d): ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $i+1 ?></td>
            <td>
              <div style="font-weight:600"><?= sanitize($d['descripcion']) ?></div>
              <?php if ($d['codigo']): ?><div style="font-size:11px;color:var(--text-muted)">Cód: <?= sanitize($d['codigo']) ?></div><?php endif; ?>
            </td>
            <td style="text-align:right"><?= number_format($d['cantidad'], 2) ?></td>
            <td style="text-align:right"><?= money($d['precio_unitario']) ?></td>
            <td style="text-align:right;color:var(--danger)"><?= $d['descuento'] > 0 ? '-'.money($d['descuento']) : '—' ?></td>
            <td style="text-align:right"><?= money($d['subtotal']) ?></td>
            <td style="text-align:right"><?= $d['iva_porcentaje'] ?>%</td>
            <td style="text-align:right"><?= money($d['iva_valor']) ?></td>
            <td style="text-align:right;font-weight:700"><?= money($d['total']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5"></td>
            <td colspan="2" style="text-align:right;color:var(--text-muted)">Subtotal 0%</td>
            <td colspan="2" style="text-align:right"><?= money($factura['subtotal_sin_iva']) ?></td>
          </tr>
          <tr>
            <td colspan="5"></td>
            <td colspan="2" style="text-align:right;color:var(--text-muted)">Subtotal 15%</td>
            <td colspan="2" style="text-align:right"><?= money($factura['subtotal_con_iva']) ?></td>
          </tr>
          <tr>
            <td colspan="5"></td>
            <td colspan="2" style="text-align:right;color:var(--text-muted)">Descuento</td>
            <td colspan="2" style="text-align:right;color:var(--danger)">-<?= money($factura['descuento']) ?></td>
          </tr>
          <tr>
            <td colspan="5"></td>
            <td colspan="2" style="text-align:right;color:var(--text-muted)">IVA 15%</td>
            <td colspan="2" style="text-align:right"><?= money($factura['iva_total']) ?></td>
          </tr>
          <tr class="invoice-total-row">
            <td colspan="5"></td>
            <td colspan="2" style="font-size:1rem;font-weight:700">TOTAL USD</td>
            <td colspan="2" style="font-size:1.1rem;font-weight:800;text-align:right"><?= money($factura['total']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php if ($facturaRaw['observaciones']): ?>
  <div class="card">
    <div class="card-body">
      <strong style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">Observaciones:</strong>
      <p style="margin-top:6px;font-size:13px"><?= sanitize($facturaRaw['observaciones']) ?></p>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
document.getElementById('formSRI')?.addEventListener('submit', function() {
  const btn = document.getElementById('btnFacturar');
  btn.disabled = true;
  btn.innerHTML = '<span class="loading-spinner"></span> Procesando...';
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
