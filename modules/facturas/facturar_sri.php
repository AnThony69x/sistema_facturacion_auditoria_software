<?php
// modules/facturas/facturar_sri.php
require_once dirname(dirname(__DIR__)) . '/includes/functions.php';
require_once dirname(dirname(__DIR__)) . '/sri/sri_service.php';
require_once dirname(dirname(__DIR__)) . '/sri/email_service.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}
if (!validateToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Token inválido.'); header('Location: index.php'); exit;
}

$facturaId = (int)($_POST['factura_id'] ?? 0);
$factura   = db()->fetchOne("SELECT * FROM facturas WHERE id=?", [$facturaId]);
if (!$factura) { setFlash('error','Factura no encontrada.'); header('Location: index.php'); exit; }

if (!in_array($factura['estado'], ['borrador','pendiente'])) {
    setFlash('warning','Esta factura ya fue procesada (estado: ' . $factura['estado'] . ').');
    header('Location: ver.php?id=' . $facturaId); exit;
}

$detalle = db()->fetchAll(
    "SELECT fd.*, p.codigo FROM factura_detalle fd JOIN productos p ON fd.producto_id=p.id WHERE fd.factura_id=?",
    [$facturaId]
);
$config  = getConfig();
$cliente = db()->fetchOne("SELECT * FROM clientes WHERE id=?", [$factura['cliente_id']]);

// ── Procesar con SRI ─────────────────────────────────────────────────────────
$resultado = SRIService::procesarFactura($factura, $detalle, $config, $cliente);

if ($resultado['success']) {
    // Factura autorizada
    db()->query(
        "UPDATE facturas SET
         estado = 'autorizada',
         estado_sri = ?,
         xml_generado = ?,
         xml_autorizado = ?,
         numero_autorizacion = ?,
         fecha_autorizacion = NOW()
         WHERE id = ?",
        [
            $resultado['estado'],
            $resultado['xml'] ?? '',
            $resultado['xml_autorizado'] ?? $resultado['xml'] ?? '',
            $resultado['numero'] ?? '',
            $facturaId,
        ]
    );

    // Enviar correo automáticamente si el cliente tiene correo
    if ($cliente['correo']) {
        $emailResult = EmailService::enviarFactura(
            $factura,
            $cliente,
            $config,
            $resultado['xml'] ?? '',
            ''
        );

        if ($emailResult['success']) {
            db()->query("UPDATE facturas SET enviado_correo=1 WHERE id=?", [$facturaId]);
            setFlash('success',
                '✅ Factura autorizada por el SRI y enviada al correo del cliente. ' .
                'N° Autorización: ' . ($resultado['numero'] ?? '')
            );
        } else {
            setFlash('warning',
                '✅ Factura autorizada. ⚠️ No se pudo enviar el correo: ' . $emailResult['message']
            );
        }
    } else {
        setFlash('success',
            '✅ Factura autorizada por el SRI. N° Autorización: ' . ($resultado['numero'] ?? '') .
            ' (El cliente no tiene correo registrado)'
        );
    }

} else {
    // Guardar como pendiente con el XML generado
    db()->query(
        "UPDATE facturas SET estado='pendiente', estado_sri=?, xml_generado=? WHERE id=?",
        [$resultado['estado'] ?? 'ERROR', $resultado['xml'] ?? '', $facturaId]
    );

    $msg = $resultado['mensaje'] ?? 'Error desconocido';
    $paso = $resultado['paso'] ?? '';

    if (str_contains($msg, 'Connection') || str_contains($msg, 'SOAP') || str_contains($msg, 'curl')) {
        setFlash('warning',
            '⚠️ El XML fue generado y firmado, pero no se pudo conectar al SRI. ' .
            'Revisa la conexión a internet y el certificado. Estado: ' . ($resultado['estado'] ?? 'ERROR')
        );
    } else {
        setFlash('error', 'Error al procesar en el SRI (' . $paso . '): ' . $msg);
    }
}

header('Location: ver.php?id=' . $facturaId);
exit;
