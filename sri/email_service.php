<?php
// sri/email_service.php
// Servicio de envío de facturas por correo electrónico

class EmailService {

    /**
     * Enviar factura electrónica al cliente usando SMTP nativo (sin PHPMailer)
     * Para producción, instalar PHPMailer: composer require phpmailer/phpmailer
     */
    public static function enviarFactura(
        array  $factura,
        array  $cliente,
        array  $empresa,
        string $xmlContent,
        string $pdfPath = ''
    ): array {
        $to      = $cliente['correo'] ?? '';
        $toName  = $cliente['nombres'] ?? '';
        $from    = $empresa['correo'] ?? '';
        $fromName= $empresa['nombre_comercial'] ?? $empresa['razon_social'] ?? 'Sistema';

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El cliente no tiene correo válido.'];
        }

        // Verificar si PHPMailer está disponible (instalado vía Composer)
        $phpMailerPath = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($phpMailerPath)) {
            return self::enviarConPHPMailer($factura, $cliente, $empresa, $xmlContent, $pdfPath);
        }

        // Fallback: correo nativo PHP (sin adjuntos SMTP)
        return self::enviarNativo($factura, $cliente, $empresa, $xmlContent, $from, $fromName);
    }

    /**
     * Envío con PHPMailer (recomendado para producción)
     */
    private static function enviarConPHPMailer(
        array $factura, array $cliente, array $empresa,
        string $xmlContent, string $pdfPath
    ): array {
        require_once dirname(__DIR__) . '/vendor/autoload.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host       = $empresa['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $empresa['smtp_user'] ?? '';
            $mail->Password   = $empresa['smtp_pass'] ?? '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($empresa['smtp_port'] ?? 587);
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];

            // Remitente / Destinatario
            $mail->setFrom($empresa['smtp_user'] ?? $empresa['correo'], $empresa['smtp_from_name'] ?? $empresa['nombre_comercial']);
            $mail->addAddress($cliente['correo'], $cliente['nombres']);

            $mail->isHTML(true);
            $mail->Subject = 'Factura Electrónica ' . $factura['numero_factura'] . ' - ' . ($empresa['nombre_comercial'] ?? $empresa['razon_social']);
            $mail->Body    = self::buildEmailHtml($factura, $cliente, $empresa);
            $mail->AltBody = 'Estimado(a) ' . $cliente['nombres'] . ', adjuntamos su factura electrónica ' . $factura['numero_factura'];

            // Adjuntar XML
            $xmlFilename = 'factura_' . $factura['numero_factura'] . '.xml';
            $mail->addStringAttachment($xmlContent, $xmlFilename, 'base64', 'text/xml');

            // Adjuntar PDF si existe
            if ($pdfPath && file_exists($pdfPath)) {
                $mail->addAttachment($pdfPath, 'factura_' . $factura['numero_factura'] . '.pdf');
            }

            $mail->send();
            return ['success' => true, 'message' => 'Factura enviada correctamente a ' . $cliente['correo']];

        } catch (Exception $e) {
            error_log("EmailService PHPMailer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar: ' . $e->getMessage()];
        }
    }

    /**
     * Envío nativo PHP (sin adjuntos SMTP, para demostración)
     */
    private static function enviarNativo(
        array $factura, array $cliente, array $empresa,
        string $xmlContent, string $from, string $fromName
    ): array {
        $to      = $cliente['correo'];
        $subject = '=?UTF-8?B?' . base64_encode('Factura Electrónica ' . $factura['numero_factura']) . '?=';
        $html    = self::buildEmailHtml($factura, $cliente, $empresa);

        $boundary = md5(time());
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: FacturaSRI\r\n";

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";

        // Adjuntar XML
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/xml; charset=UTF-8; name=\"factura_" . $factura['numero_factura'] . ".xml\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"factura_" . $factura['numero_factura'] . ".xml\"\r\n\r\n";
        $body .= chunk_split(base64_encode($xmlContent)) . "\r\n";
        $body .= "--$boundary--";

        $sent = @mail($to, $subject, $body, $headers);

        if ($sent) {
            return ['success' => true, 'message' => 'Factura enviada a ' . $to];
        }

        return ['success' => false, 'message' => 'No se pudo enviar el correo. Verifica la configuración SMTP.'];
    }

    /**
     * Plantilla HTML del correo
     */
    public static function buildEmailHtml(array $factura, array $cliente, array $empresa): string {
        $empresa_nombre = htmlspecialchars($empresa['nombre_comercial'] ?? $empresa['razon_social'] ?? '');
        $empresa_ruc    = htmlspecialchars($empresa['ruc'] ?? '');
        $cliente_nombre = htmlspecialchars($cliente['nombres'] ?? '');
        $nro_factura    = htmlspecialchars($factura['numero_factura'] ?? '');
        $fecha          = date('d/m/Y', strtotime($factura['fecha_emision']));
        $total          = '$' . number_format($factura['total'], 2, '.', ',');
        $estado         = strtoupper($factura['estado'] ?? '');
        $num_auth       = $factura['numero_autorizacion'] ?? '(pendiente)';
        $clave          = $factura['clave_acceso'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 0">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">

      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#e8562a,#f5863d);padding:28px 36px">
        <table width="100%"><tr>
          <td><div style="font-size:24px;font-weight:800;color:#fff;letter-spacing:-1px">Factura<span style="opacity:.8">SRI</span></div>
              <div style="color:rgba(255,255,255,.8);font-size:13px;margin-top:2px">Comprobante Electrónico</div></td>
          <td align="right"><div style="background:rgba(255,255,255,.2);border-radius:8px;padding:10px 16px;color:#fff;font-size:13px;font-weight:600">
            {$nro_factura}
          </div></td>
        </tr></table>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:32px 36px">
        <p style="color:#555;font-size:15px;margin:0 0 24px">Estimado(a) <strong>{$cliente_nombre}</strong>,</p>
        <p style="color:#555;font-size:14px;margin:0 0 24px">
          Adjuntamos su comprobante electrónico emitido por <strong>{$empresa_nombre}</strong> (RUC: {$empresa_ruc}).
          Este documento ha sido generado cumpliendo las especificaciones del SRI Ecuador.
        </p>

        <!-- Detalles -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa;border-radius:8px;margin-bottom:24px">
          <tr><td style="padding:16px 20px;border-bottom:1px solid #e9ecef">
            <table width="100%"><tr>
              <td style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px">N° Factura</td>
              <td align="right" style="font-weight:700;color:#333">{$nro_factura}</td>
            </tr></table>
          </td></tr>
          <tr><td style="padding:16px 20px;border-bottom:1px solid #e9ecef">
            <table width="100%"><tr>
              <td style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px">Fecha Emisión</td>
              <td align="right" style="font-weight:600;color:#333">{$fecha}</td>
            </tr></table>
          </td></tr>
          <tr><td style="padding:16px 20px;border-bottom:1px solid #e9ecef">
            <table width="100%"><tr>
              <td style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px">N° Autorización SRI</td>
              <td align="right" style="font-weight:600;color:#333;font-size:12px">{$num_auth}</td>
            </tr></table>
          </td></tr>
          <tr><td style="padding:20px">
            <table width="100%"><tr>
              <td style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px">TOTAL</td>
              <td align="right" style="font-size:22px;font-weight:800;color:#e8562a">{$total}</td>
            </tr></table>
          </td></tr>
        </table>

        <!-- Clave acceso -->
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:14px 16px;margin-bottom:24px;font-size:11px">
          <strong>Clave de Acceso:</strong><br>
          <code style="font-size:10px;word-break:break-all;color:#856404">{$clave}</code>
        </div>

        <p style="color:#888;font-size:12px;margin:0">
          Puede verificar este comprobante en el portal del SRI: 
          <a href="https://srienlinea.sri.gob.ec/sri-en-linea/inicio/NAP" style="color:#e8562a">srienlinea.sri.gob.ec</a>
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f8f9fa;padding:20px 36px;border-top:1px solid #e9ecef;text-align:center">
        <p style="color:#aaa;font-size:11px;margin:0">{$empresa_nombre} — Este correo fue generado automáticamente por FacturaSRI</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }
}
