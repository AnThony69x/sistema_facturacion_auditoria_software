<?php
// modules/clientes/eliminar.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$c  = db()->fetchOne("SELECT id FROM clientes WHERE id=? AND activo=1", [$id]);

if ($c) {
    // Check if client has invoices
    $used = db()->fetchOne("SELECT COUNT(*) c FROM facturas WHERE cliente_id=?", [$id])['c'];
    if ($used > 0) {
        setFlash('warning', 'No se puede eliminar: el cliente tiene facturas asociadas.');
    } else {
        db()->query("UPDATE clientes SET activo=0 WHERE id=?", [$id]);
        setFlash('success', 'Cliente eliminado.');
    }
} else {
    setFlash('error', 'Cliente no encontrado.');
}

header('Location: index.php');
exit;
