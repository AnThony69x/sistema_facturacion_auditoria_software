<?php
// modules/facturas/anular.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$f  = db()->fetchOne("SELECT * FROM facturas WHERE id=? AND estado='borrador'", [$id]);
if ($f) {
    // Restaurar stock
    $det = db()->fetchAll("SELECT * FROM factura_detalle WHERE factura_id=?", [$id]);
    foreach ($det as $d) {
        db()->query("UPDATE productos SET existencia = existencia + ? WHERE id=?", [$d['cantidad'],$d['producto_id']]);
    }
    db()->query("UPDATE facturas SET estado='anulada' WHERE id=?", [$id]);
    setFlash('success','Factura anulada y stock restaurado.');
} else {
    setFlash('error','No se puede anular esta factura.');
}
header('Location: index.php'); exit;
