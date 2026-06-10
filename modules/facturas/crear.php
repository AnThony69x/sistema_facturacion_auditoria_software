<?php
// modules/facturas/crear.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
define('PAGE_TITLE', 'Nueva Factura');

$clientes = db()->fetchAll("SELECT * FROM clientes WHERE activo=1 ORDER BY nombres");
$config   = getConfig();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $cliente_id  = (int)($_POST['cliente_id'] ?? 0);
        $forma_pago  = $_POST['forma_pago'] ?? 'efectivo';
        $observaciones = trim($_POST['observaciones'] ?? '');
        $items       = $_POST['items'] ?? [];

        if (!$cliente_id)     $errors[] = 'Debe seleccionar un cliente.';
        if (empty($items))    $errors[] = 'Debe agregar al menos un producto.';

        if (!$errors) {
            // Calcular totales
            $subtotalSinIva = 0;
            $subtotalConIva = 0;
            $ivaTotal       = 0;
            $descuentoTotal = 0;

            foreach ($items as &$item) {
                $qty        = (float)($item['cantidad'] ?? 0);
                $precio     = (float)($item['precio'] ?? 0);
                $desc       = (float)($item['descuento'] ?? 0);
                $iva        = (float)($item['iva'] ?? 15);
                $base       = ($precio * $qty) - $desc;
                $ivaVal     = $base * ($iva / 100);

                $item['subtotal']  = $base;
                $item['iva_valor'] = $ivaVal;
                $item['total']     = $base + $ivaVal;

                if ($iva > 0) $subtotalConIva += $base;
                else          $subtotalSinIva += $base;
                $ivaTotal       += $ivaVal;
                $descuentoTotal += $desc;
            }

            $total = $subtotalSinIva + $subtotalConIva + $ivaTotal;

            try {
                db()->beginTransaction();

                // Generar número de factura y clave de acceso
                $numeroFactura = getNextNumeroFactura();
                $claveAcceso   = generarClaveAcceso(
                    date('dmY', strtotime(date('Y-m-d'))),
                    '01',
                    $config['ruc'] ?? '0999999999001',
                    $config['ambiente'] ?? '1',
                    $config['establecimiento'] ?? '001',
                    $config['punto_emision'] ?? '001',
                    generarSecuencial($config['secuencial'] ?? 1),
                    '1'
                );

                // Insertar factura
                db()->query(
                    "INSERT INTO facturas (numero_factura, clave_acceso, cliente_id, usuario_id, fecha_emision,
                     subtotal_sin_iva, subtotal_con_iva, descuento, iva_total, total, forma_pago, observaciones, estado)
                     VALUES (?,?,?,?,CURDATE(),?,?,?,?,?,?,?,'borrador')",
                    [$numeroFactura, $claveAcceso, $cliente_id, $_SESSION['user_id'],
                     $subtotalSinIva, $subtotalConIva, $descuentoTotal, $ivaTotal, $total,
                     $forma_pago, $observaciones]
                );
                $facturaId = db()->lastInsertId();

                // Insertar detalle
                foreach ($items as $item) {
                    $prod = db()->fetchOne("SELECT nombre, codigo FROM productos WHERE id=?", [$item['producto_id']]);
                    db()->query(
                        "INSERT INTO factura_detalle (factura_id,producto_id,descripcion,cantidad,
                         precio_unitario,descuento,iva_porcentaje,subtotal,iva_valor,total)
                         VALUES (?,?,?,?,?,?,?,?,?,?)",
                        [$facturaId, $item['producto_id'], $prod['nombre'] ?? '',
                         $item['cantidad'], $item['precio'], $item['descuento'],
                         $item['iva'], $item['subtotal'], $item['iva_valor'], $item['total']]
                    );
                    // Reducir stock
                    db()->query("UPDATE productos SET existencia = existencia - ? WHERE id=?",
                        [$item['cantidad'], $item['producto_id']]);
                }

                // Actualizar secuencial
                db()->query("UPDATE configuracion SET secuencial = secuencial + 1");

                db()->commit();
                setFlash('success', 'Factura #' . $numeroFactura . ' creada correctamente.');
                header('Location: ver.php?id=' . $facturaId);
                exit;

            } catch (Exception $e) {
                db()->rollback();
                $errors[] = 'Error al guardar la factura: ' . $e->getMessage();
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
  padding: 10px 14px; cursor: pointer;
  border-bottom: 1px solid var(--border-2);
  transition: background var(--transition);
}
.autocomplete-item:last-child { border-bottom: none; }
.autocomplete-item:hover { background: var(--bg-3); }
.form-control-sm {
  background: var(--bg-2); border: 1px solid var(--border);
  color: var(--text); border-radius: 4px; padding: 4px 8px;
  font-size: 13px; font-family: var(--font-body);
}
.form-control-sm:focus { outline: none; border-color: var(--accent); }
</style>

<div class="invoice-wrapper">

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><span><?= sanitize($e) ?></span></div>
  <?php endforeach; ?>

  <form method="POST" id="invoiceForm">
    <?= csrfField() ?>

    <!-- Header de factura -->
    <div class="invoice-header-bar">
      <div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:4px">Emisor</div>
        <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:700"><?= sanitize($config['nombre_comercial'] ?? $config['razon_social'] ?? 'Mi Empresa') ?></div>
        <div style="font-size:12px;color:var(--text-muted)">RUC: <?= sanitize($config['ruc'] ?? '') ?></div>
        <div style="font-size:12px;color:var(--text-muted)"><?= sanitize($config['direccion_matriz'] ?? '') ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:4px">Número de Factura</div>
        <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--accent-2)"><?= getNextNumeroFactura() ?></div>
        <div style="font-size:12px;color:var(--text-muted)">Fecha: <?= date('d/m/Y') ?></div>
        <div style="margin-top:8px">
          <span class="badge badge-warning">BORRADOR</span>
          <span class="badge badge-muted" style="margin-left:6px">Amb: <?= $config['ambiente']=='1'?'Pruebas':'Producción' ?></span>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

      <!-- Cliente -->
      <div class="card">
        <div class="card-header"><div class="card-title">Datos del Cliente</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Cliente <span style="color:var(--danger)">*</span></label>
            <select name="cliente_id" id="clienteSelect" required onchange="cargarDatosCliente(this.value)">
              <option value="">— Seleccionar cliente —</option>
              <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>"
                        data-cedula="<?= sanitize($c['cedula']) ?>"
                        data-correo="<?= sanitize($c['correo']) ?>"
                        data-telefono="<?= sanitize($c['telefono']) ?>"
                        data-direccion="<?= sanitize($c['direccion']) ?>">
                  <?= sanitize($c['nombres']) ?> — <?= sanitize($c['cedula']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="clienteInfo" style="display:none;margin-top:12px;background:var(--bg-3);border-radius:var(--radius-sm);padding:12px;font-size:13px">
            <div><strong>Cédula/RUC:</strong> <span id="ci_cedula"></span></div>
            <div><strong>Correo:</strong> <span id="ci_correo"></span></div>
            <div><strong>Teléfono:</strong> <span id="ci_telefono"></span></div>
            <div><strong>Dirección:</strong> <span id="ci_direccion"></span></div>
          </div>
          <a href="<?= BASE_URL ?>modules/clientes/crear.php" target="_blank"
             style="display:inline-flex;align-items:center;gap:5px;margin-top:10px;font-size:12px;color:var(--accent-2);text-decoration:none">
            + Crear nuevo cliente
          </a>
        </div>
      </div>

      <!-- Configuración de pago -->
      <div class="card">
        <div class="card-header"><div class="card-title">Configuración</div></div>
        <div class="card-body">
          <div class="form-group">
            <label>Forma de Pago</label>
            <select name="forma_pago">
              <option value="efectivo">💵 Efectivo</option>
              <option value="tarjeta">💳 Tarjeta de Crédito/Débito</option>
              <option value="transferencia">🏦 Transferencia Bancaria</option>
              <option value="cheque">📝 Cheque</option>
              <option value="credito">⏳ Crédito</option>
            </select>
          </div>
          <div class="form-group" style="margin-top:14px">
            <label>Observaciones</label>
            <textarea name="observaciones" placeholder="Observaciones adicionales..." rows="3"></textarea>
          </div>
          <input type="hidden" id="ivaRate" value="15">
        </div>
      </div>
    </div>

    <!-- Productos -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title">Productos / Servicios</div>
      </div>
      <div class="card-body">
        <div class="autocomplete-wrapper" style="margin-bottom:16px">
          <div class="form-group">
            <label>Buscar y Agregar Producto</label>
            <input type="text" id="productSearch"
                   placeholder="Escribe el nombre o código del producto..."
                   autocomplete="off">
          </div>
          <div id="productResults"></div>
        </div>

        <div class="table-wrapper">
          <table class="detail-table">
            <thead>
              <tr>
                <th style="width:35%">Producto</th>
                <th style="width:10%">Cantidad</th>
                <th style="width:12%">P. Unitario</th>
                <th style="width:10%">Descuento</th>
                <th style="width:8%">IVA</th>
                <th style="width:12%">Total</th>
                <th style="width:5%"></th>
              </tr>
            </thead>
            <tbody id="invoiceLines">
              <tr id="emptyRow">
                <td colspan="7" style="text-align:center;padding:30px;color:var(--text-muted)">
                  Busca y agrega productos usando el campo de arriba
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Totales y acciones -->
    <div style="display:flex;justify-content:flex-end;gap:20px;align-items:flex-start;flex-wrap:wrap">

      <div class="totals-panel">
        <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px">Resumen</div>
        <div id="invoiceTotals">
          <div class="totals-row"><span>Subtotal 0%</span><span>$0.00</span></div>
          <div class="totals-row"><span>Subtotal 15% IVA</span><span>$0.00</span></div>
          <div class="totals-row"><span>Descuento</span><span>-$0.00</span></div>
          <div class="totals-row"><span>IVA</span><span>$0.00</span></div>
          <div class="totals-row total-final"><span>TOTAL</span><span>$0.00</span></div>
        </div>

        <!-- Hidden fields for form submit -->
        <input type="hidden" id="h_subtotal_sin_iva" name="subtotal_sin_iva" value="0">
        <input type="hidden" id="h_subtotal_con_iva" name="subtotal_con_iva" value="0">
        <input type="hidden" id="h_descuento_total"  name="descuento_total"  value="0">
        <input type="hidden" id="h_iva_total"        name="iva_total"        value="0">
        <input type="hidden" id="h_total"            name="total"            value="0">
      </div>

      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="index.php" class="btn btn-outline">Cancelar</a>
        <button type="submit" name="accion" value="guardar" class="btn btn-primary btn-lg">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Guardar Factura
        </button>
      </div>
    </div>

  </form>
</div>

<script>
function cargarDatosCliente(id) {
  const sel  = document.getElementById('clienteSelect');
  const opt  = sel.options[sel.selectedIndex];
  const info = document.getElementById('clienteInfo');
  if (!id) { info.style.display = 'none'; return; }
  document.getElementById('ci_cedula').textContent   = opt.dataset.cedula   || '—';
  document.getElementById('ci_correo').textContent   = opt.dataset.correo   || '—';
  document.getElementById('ci_telefono').textContent = opt.dataset.telefono || '—';
  document.getElementById('ci_direccion').textContent= opt.dataset.direccion|| '—';
  info.style.display = 'block';
}

// Remove empty row when items are added
const origRender = InvoiceBuilder.render.bind(InvoiceBuilder);
InvoiceBuilder.render = function() {
  origRender();
  const emptyRow = document.getElementById('emptyRow');
  if (emptyRow) emptyRow.style.display = this.items.length ? 'none' : 'table-row';
};

document.getElementById('invoiceForm').addEventListener('submit', function(e) {
  if (InvoiceBuilder.items.length === 0) {
    e.preventDefault();
    alert('Debe agregar al menos un producto a la factura.');
  }
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
