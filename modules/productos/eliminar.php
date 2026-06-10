<?php
// modules/productos/eliminar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$p  = db()->fetchOne("SELECT id FROM productos WHERE id=? AND activo=1", [$id]);
if ($p) {
    $used = db()->fetchOne("SELECT COUNT(*) c FROM factura_detalle WHERE producto_id=?", [$id])['c'];
    if ($used > 0) { setFlash('warning','No se puede eliminar: el producto tiene facturas asociadas.'); }
    else { db()->query("UPDATE productos SET activo=0 WHERE id=?", [$id]); setFlash('success','Producto eliminado.'); }
} else { setFlash('error','Producto no encontrado.'); }
header('Location: index.php'); exit;
