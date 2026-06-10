<?php
// modules/facturas/enviar_correo.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
require_once dirname(dirname(__DIR__)) . '/sri/email_service.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if (!validateToken($_POST['csrf_token'] ?? '')) { setFlash('error','Token inválido.'); header('Location: index.php'); exit; }

$facturaId = (int)($_POST['factura_id'] ?? 0);
$factura   = db()->fetchOne("SELECT * FROM facturas WHERE id=?", [$facturaId]);
if (!$factura) { setFlash('error','Factura no encontrada.'); header('Location: index.php'); exit; }

$cliente = db()->fetchOne("SELECT * FROM clientes WHERE id=?", [$factura['cliente_id']]);
$config  = getConfig();

$result = EmailService::enviarFactura($factura, $cliente, $config, $factura['xml_generado'] ?? '', '');

if ($result['success']) {
    db()->query("UPDATE facturas SET enviado_correo=1 WHERE id=?", [$facturaId]);
    setFlash('success', '✉️ Correo enviado correctamente a ' . $cliente['correo']);
} else {
    setFlash('error', 'Error al enviar correo: ' . $result['message']);
}

header('Location: ver.php?id=' . $facturaId); exit;
