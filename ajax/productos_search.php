<?php
// ajax/productos_search.php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$s = "%$q%";
$productos = db()->fetchAll(
    "SELECT id, nombre, codigo, existencia, precio_venta, iva, foto
     FROM productos
     WHERE activo=1 AND existencia > 0 AND (nombre LIKE ? OR codigo LIKE ?)
     ORDER BY nombre ASC LIMIT 10",
    [$s, $s]
);

echo json_encode($productos);
