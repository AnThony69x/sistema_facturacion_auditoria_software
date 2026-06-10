<?php
// modules/facturas/imprimir.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id      = (int)($_GET['id'] ?? 0);
$factura = db()->fetchOne("SELECT * FROM v_facturas WHERE id=?", [$id]);
if (!$factura) die('Factura no encontrada');

$detalle = db()->fetchAll("SELECT fd.*, p.codigo FROM factura_detalle fd JOIN productos p ON fd.producto_id=p.id WHERE fd.factura_id=?", [$id]);
$facturaRaw = db()->fetchOne("SELECT * FROM facturas WHERE id=?", [$id]);
$config     = getConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura <?= sanitize($factura['numero_factura']) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #333; background: #fff; }
  .page { max-width: 800px; margin: 0 auto; padding: 24px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #e8562a; padding-bottom: 16px; }
  .empresa-name { font-size: 20px; font-weight: 800; color: #e8562a; }
  .factura-box { background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; padding: 12px 18px; text-align: center; }
  .factura-box .tipo { font-size: 10px; text-transform: uppercase; color: #888; }
  .factura-box .nro { font-size: 18px; font-weight: 800; color: #333; }
  .section { margin-bottom: 16px; }
  .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 8px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .info-item label { font-size: 10px; color: #888; text-transform: uppercase; }
  .info-item span { display: block; font-weight: 600; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin: 12px 0; }
  th { background: #f0f0f0; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #ddd; }
  td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
  .text-right { text-align: right; }
  .totals { margin-left: auto; width: 280px; }
  .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }
  .totals-row.final { border-top: 2px solid #333; margin-top: 6px; padding-top: 8px; font-size: 16px; font-weight: 800; color: #e8562a; }
  .clave { font-size: 9px; word-break: break-all; background: #fff8e1; border: 1px solid #ffd; border-radius: 4px; padding: 8px; margin-top: 12px; }
  .footer { margin-top: 24px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 12px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
  .badge-autorizada { background: #d4edda; color: #155724; }
  .badge-borrador   { background: #e2e3e5; color: #383d41; }
  .badge-pendiente  { background: #fff3cd; color: #856404; }
  .badge-anulada    { background: #f8d7da; color: #721c24; }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none; }
  }
</style>
</head>
<body>
<div class="page">

  <!-- Botón imprimir (se oculta en impresión) -->
  <div class="no-print" style="margin-bottom:16px;display:flex;gap:10px">
    <button onclick="window.print()" style="background:#e8562a;color:#fff;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-weight:600">🖨 Imprimir</button>
    <button onclick="window.close()" style="background:#f0f0f0;color:#333;border:none;padding:8px 16px;border-radius:6px;cursor:pointer">Cerrar</button>
  </div>

  <div class="header">
    <div>
      <div class="empresa-name"><?= sanitize($config['nombre_comercial'] ?? $config['razon_social'] ?? '') ?></div>
      <div>RUC: <?= sanitize($config['ruc'] ?? '') ?></div>
      <div><?= sanitize($config['direccion_matriz'] ?? '') ?></div>
      <div><?= sanitize($config['telefono'] ?? '') ?> | <?= sanitize($config['correo'] ?? '') ?></div>
    </div>
    <div class="factura-box">
      <div class="tipo">FACTURA ELECTRÓNICA</div>
      <div class="nro"><?= sanitize($factura['numero_factura']) ?></div>
      <div style="font-size:11px;color:#666;margin-top:4px">Fecha: <?= formatDate($factura['fecha_emision']) ?></div>
      <div style="margin-top:8px"><span class="badge badge-<?= $factura['estado'] ?>"><?= strtoupper($factura['estado']) ?></span></div>
      <?php if ($facturaRaw['numero_autorizacion']): ?>
      <div style="font-size:9px;color:#888;margin-top:4px">Auth: <?= sanitize($facturaRaw['numero_autorizacion']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Datos cliente -->
  <div class="section">
    <div class="section-title">Datos del Cliente</div>
    <div class="info-grid">
      <div class="info-item"><label>Nombre</label><span><?= sanitize($factura['cliente']) ?></span></div>
      <div class="info-item"><label>Cédula/RUC</label><span><?= sanitize($factura['cedula']) ?></span></div>
      <div class="info-item"><label>Correo</label><span><?= sanitize($factura['cliente_correo']) ?></span></div>
    </div>
  </div>

  <!-- Detalle -->
  <div class="section">
    <div class="section-title">Detalle de Productos</div>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Descripción</th>
          <th class="text-right">Cant.</th>
          <th class="text-right">P. Unit.</th>
          <th class="text-right">Desc.</th>
          <th class="text-right">Base Imp.</th>
          <th class="text-right">IVA%</th>
          <th class="text-right">IVA$</th>
          <th class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($detalle as $i => $d): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= sanitize($d['descripcion']) ?><?php if ($d['codigo']): ?> <small style="color:#888">[<?= sanitize($d['codigo']) ?>]</small><?php endif; ?></td>
          <td class="text-right"><?= number_format($d['cantidad'], 2) ?></td>
          <td class="text-right">$<?= number_format($d['precio_unitario'], 2) ?></td>
          <td class="text-right"><?= $d['descuento'] > 0 ? '$'.number_format($d['descuento'],2) : '—' ?></td>
          <td class="text-right">$<?= number_format($d['subtotal'], 2) ?></td>
          <td class="text-right"><?= $d['iva_porcentaje'] ?>%</td>
          <td class="text-right">$<?= number_format($d['iva_valor'], 2) ?></td>
          <td class="text-right"><strong>$<?= number_format($d['total'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Totales -->
  <div style="display:flex;justify-content:flex-end">
    <div class="totals">
      <div class="totals-row"><span>Subtotal 0%</span><span>$<?= number_format($factura['subtotal_sin_iva'],2) ?></span></div>
      <div class="totals-row"><span>Subtotal 15% IVA</span><span>$<?= number_format($factura['subtotal_con_iva'],2) ?></span></div>
      <div class="totals-row"><span>Descuento</span><span>-$<?= number_format($factura['descuento'],2) ?></span></div>
      <div class="totals-row"><span>IVA 15%</span><span>$<?= number_format($factura['iva_total'],2) ?></span></div>
      <div class="totals-row final"><span>TOTAL USD</span><span>$<?= number_format($factura['total'],2) ?></span></div>
    </div>
  </div>

  <!-- Forma de pago -->
  <div style="margin-top:16px;font-size:11px"><strong>Forma de Pago:</strong> <?= ucfirst(sanitize($facturaRaw['forma_pago'])) ?></div>

  <?php if ($facturaRaw['observaciones']): ?>
  <div style="margin-top:8px;font-size:11px"><strong>Observaciones:</strong> <?= sanitize($facturaRaw['observaciones']) ?></div>
  <?php endif; ?>

  <!-- Clave de acceso -->
  <?php if ($factura['clave_acceso']): ?>
  <div class="clave">
    <strong>Clave de Acceso:</strong><br>
    <?= sanitize($factura['clave_acceso']) ?>
  </div>
  <?php endif; ?>

  <div class="footer">
    Documento generado por FacturaSRI &mdash; <?= sanitize($config['razon_social'] ?? '') ?> &mdash; <?= date('d/m/Y H:i') ?>
  </div>

</div>
</body>
</html>
