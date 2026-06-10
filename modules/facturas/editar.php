<?php
// modules/facturas/editar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id      = (int)($_GET['id'] ?? 0);
$factura = db()->fetchOne("SELECT * FROM facturas WHERE id=?", [$id]);
if (!$factura || $factura['estado'] !== 'borrador') {
    setFlash('error','Solo se pueden modificar facturas en estado borrador.');
    header('Location: index.php'); exit;
}

define('PAGE_TITLE', 'Modificar Factura');
$clientes = db()->fetchAll("SELECT * FROM clientes WHERE activo=1 ORDER BY nombres");
$detalle  = db()->fetchAll("SELECT fd.*, p.nombre AS prod_nombre, p.iva, p.precio_venta FROM factura_detalle fd JOIN productos p ON fd.producto_id=p.id WHERE fd.factura_id=?", [$id]);
$config   = getConfig();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Token inválido.'; }
    else {
        $cliente_id  = (int)($_POST['cliente_id'] ?? 0);
        $forma_pago  = $_POST['forma_pago'] ?? 'efectivo';
        $observaciones = trim($_POST['observaciones'] ?? '');
        $items       = $_POST['items'] ?? [];

        if (!$cliente_id)     $errors[] = 'Debe seleccionar un cliente.';
        if (empty($items))    $errors[] = 'Debe agregar al menos un producto.';

        if (!$errors) {
            $subtotalSinIva = $subtotalConIva = $ivaTotal = $descuentoTotal = 0;

            foreach ($items as &$item) {
                $qty    = (float)($item['cantidad'] ?? 0);
                $precio = (float)($item['precio'] ?? 0);
                $desc   = (float)($item['descuento'] ?? 0);
                $iva    = (float)($item['iva'] ?? 15);
                $base   = ($precio * $qty) - $desc;
                $ivaVal = $base * ($iva / 100);

                $item['subtotal'] = $base;
                $item['iva_valor']= $ivaVal;
                $item['total']    = $base + $ivaVal;

                if ($iva > 0) $subtotalConIva += $base;
                else          $subtotalSinIva += $base;
                $ivaTotal       += $ivaVal;
                $descuentoTotal += $desc;
            }
            $total = $subtotalSinIva + $subtotalConIva + $ivaTotal;

            try {
                db()->beginTransaction();

                // Restaurar stock del detalle anterior
                foreach ($detalle as $d) {
                    db()->query("UPDATE productos SET existencia = existencia + ? WHERE id=?",
                        [$d['cantidad'], $d['producto_id']]);
                }

                // Actualizar factura
                db()->query(
                    "UPDATE facturas SET cliente_id=?,forma_pago=?,observaciones=?,
                     subtotal_sin_iva=?,subtotal_con_iva=?,descuento=?,iva_total=?,total=?
                     WHERE id=?",
                    [$cliente_id,$forma_pago,$observaciones,
                     $subtotalSinIva,$subtotalConIva,$descuentoTotal,$ivaTotal,$total,$id]
                );

                // Borrar y reinsertar detalle
                db()->query("DELETE FROM factura_detalle WHERE factura_id=?", [$id]);
                foreach ($items as $item) {
                    $prod = db()->fetchOne("SELECT nombre, codigo FROM productos WHERE id=?", [$item['producto_id']]);
                    db()->query(
                        "INSERT INTO factura_detalle (factura_id,producto_id,descripcion,cantidad,precio_unitario,descuento,iva_porcentaje,subtotal,iva_valor,total)
                         VALUES (?,?,?,?,?,?,?,?,?,?)",
                        [$id,$item['producto_id'],$prod['nombre']??'',
                         $item['cantidad'],$item['precio'],$item['descuento'],
                         $item['iva'],$item['subtotal'],$item['iva_valor'],$item['total']]
                    );
                    // Descontar nuevo stock
                    db()->query("UPDATE productos SET existencia = existencia - ? WHERE id=?",
                        [$item['cantidad'], $item['producto_id']]);
                }

                db()->commit();
                setFlash('success','Factura actualizada correctamente.');
                header('Location: ver.php?id='.$id); exit;

            } catch (Exception $e) {
                db()->rollback();
                $errors[] = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
.autocomplete-wrapper { position: relative; }
#productResults {
  position: absolute; top: 100%; left: 0; right: 0; z-index: 99;
  background: var(--bg-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm); max-height: 300px; overflow-y: auto;
  box-shadow: var(--shadow); display: none;
}
.autocomplete-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--border-2);
  transition: background var(--transition);
}
.autocomplete-item:hover { background: var(--bg-3); }
.form-control-sm {
  background: var(--bg-2); border: 1px solid var(--border);
  color: var(--text); border-radius: 4px; padding: 4px 8px;
  font-size: 13px; font-family: var(--font-body);
}
.form-control-sm:focus { outline: none; border-color: var(--accent); }
</style>

<div class="invoice-wrapper">
  <div class="alert alert-warning" style="margin-bottom:16px">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    Modificando factura <strong><?= sanitize($factura['numero_factura']) ?></strong>. Solo facturas en estado borrador pueden ser modificadas.
  </div>

  <?php foreach ($errors as $e): ?><div class="alert alert-error"><span><?= sanitize($e) ?></span></div><?php endforeach; ?>

  <form method="POST" id="invoiceForm">
    <?= csrfField() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
      <div class="card">
        <div class="card-header"><div class="card-title">Cliente</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Cliente *</label>
            <select name="cliente_id" id="clienteSelect" required onchange="cargarDatosCliente(this.value)">
              <option value="">— Seleccionar —</option>
              <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>"
                        data-cedula="<?= sanitize($c['cedula']) ?>"
                        data-correo="<?= sanitize($c['correo']) ?>"
                        <?= $c['id']==$factura['cliente_id']?'selected':'' ?>>
                  <?= sanitize($c['nombres']) ?> — <?= sanitize($c['cedula']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title">Pago</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Forma de Pago</label>
            <select name="forma_pago">
              <?php foreach (['efectivo'=>'Efectivo','tarjeta'=>'Tarjeta','transferencia'=>'Transferencia','cheque'=>'Cheque','credito'=>'Crédito'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $factura['forma_pago']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="2"><?= sanitize($factura['observaciones']) ?></textarea>
          </div>
          <input type="hidden" id="ivaRate" value="15">
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><div class="card-title">Productos</div></div>
      <div class="card-body">
        <div class="autocomplete-wrapper" style="margin-bottom:16px">
          <div class="form-group">
            <label>Agregar Producto</label>
            <input type="text" id="productSearch" placeholder="Buscar producto..." autocomplete="off">
          </div>
          <div id="productResults"></div>
        </div>
        <div class="table-wrapper">
          <table class="detail-table">
            <thead>
              <tr><th>Producto</th><th>Cantidad</th><th>P. Unitario</th><th>Descuento</th><th>IVA</th><th>Total</th><th></th></tr>
            </thead>
            <tbody id="invoiceLines">
              <tr id="emptyRow" style="display:none">
                <td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">Agrega productos</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:20px;align-items:flex-start">
      <div class="totals-panel">
        <div id="invoiceTotals"></div>
        <input type="hidden" id="h_subtotal_sin_iva" name="subtotal_sin_iva" value="0">
        <input type="hidden" id="h_subtotal_con_iva" name="subtotal_con_iva" value="0">
        <input type="hidden" id="h_descuento_total"  name="descuento_total"  value="0">
        <input type="hidden" id="h_iva_total"        name="iva_total"        value="0">
        <input type="hidden" id="h_total"            name="total"            value="0">
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="ver.php?id=<?= $id ?>" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Cambios</button>
      </div>
    </div>
  </form>
</div>

<script>
// Precargar items existentes
<?php foreach ($detalle as $d): ?>
InvoiceBuilder.addProduct(
  <?= $d['producto_id'] ?>,
  '<?= addslashes($d['descripcion'] ?? $d['prod_nombre']) ?>',
  <?= $d['precio_unitario'] ?>,
  <?= $d['iva_porcentaje'] ?>
);
// Ajustar cantidad y descuento
(function() {
  const item = InvoiceBuilder.items[InvoiceBuilder.items.length-1];
  if (item) { item.qty = <?= $d['cantidad'] ?>; item.descuento = <?= $d['descuento'] ?>; }
})();
<?php endforeach; ?>
InvoiceBuilder.render();

function cargarDatosCliente(id) {}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
