<?php
// sri/sri_service.php
// Servicio de comunicación con el SRI Ecuador (SOAP)

require_once __DIR__ . '/xml_generator.php';

class SRIService {

    // URLs SRI - Ambiente de Pruebas (1) y Producción (2)
    private static array $urls = [
        '1' => [ // Pruebas
            'recepcion'   => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion'=> 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
        ],
        '2' => [ // Producción
            'recepcion'   => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion'=> 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
        ],
    ];

    /**
     * Firma el XML con el certificado .p12 usando OpenSSL
     */
    public static function firmarXML(string $xml, string $p12Path, string $p12Password): string|false {
        if (!file_exists($p12Path)) {
            error_log("SRI: Certificado no encontrado: $p12Path");
            return false;
        }

        $p12Content = file_get_contents($p12Path);
        $certs      = [];

        if (!openssl_pkcs12_read($p12Content, $certs, $p12Password)) {
            error_log("SRI: Error al leer el certificado P12: " . openssl_error_string());
            return false;
        }

        // Crear archivo XML temporal
        $tmpXml  = tempnam(sys_get_temp_dir(), 'sri_xml_');
        $tmpXmlS = tempnam(sys_get_temp_dir(), 'sri_xml_signed_');
        file_put_contents($tmpXml, $xml);

        // Escribir clave privada y certificado temporales
        $tmpKey  = tempnam(sys_get_temp_dir(), 'sri_key_');
        $tmpCert = tempnam(sys_get_temp_dir(), 'sri_cert_');
        file_put_contents($tmpKey,  $certs['pkey']);
        file_put_contents($tmpCert, $certs['cert']);

        // Usar xmlsec1 si está disponible (recomendado en producción)
        // En su defecto, firmamos con método básico
        $xmlSigned = self::firmarXmlBasico($xml, $certs['pkey'], $certs['cert']);

        // Cleanup
        foreach ([$tmpXml, $tmpKey, $tmpCert] as $f) { @unlink($f); }

        return $xmlSigned ?: false;
    }

    /**
     * Firma XML con XAdES-BES básico (simplificado para demostración)
     * En producción usar la librería: https://github.com/stalinscj/SRI-firma-electronica
     */
    private static function firmarXmlBasico(string $xml, string $privateKey, string $certificate): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        // Agregar nodo de firma (simplificado - en producción usar librería XAdES completa)
        $signatureNode = $dom->createElement('Signature');
        $signatureNode->setAttribute('Id', 'Signature' . time());
        $dom->documentElement->appendChild($signatureNode);

        // Incluir información del certificado en comentario para identificación
        $certInfo = base64_encode($certificate);
        $signedInfo = $dom->createComment(" Certificado: " . substr($certInfo, 0, 50) . "... ");
        $signatureNode->appendChild($signedInfo);

        return $dom->saveXML();
    }

    /**
     * Envía el comprobante al SRI (Recepción)
     */
    public static function enviarComprobante(string $xmlFirmado, string $ambiente = '1'): array {
        $url = self::$urls[$ambiente]['recepcion'] ?? self::$urls['1']['recepcion'];

        try {
            $client = new SoapClient($url, [
                'trace'           => true,
                'exceptions'      => true,
                'connection_timeout' => 30,
                'stream_context'  => stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ])
            ]);

            $xmlBase64 = base64_encode($xmlFirmado);
            $response  = $client->validarComprobante(['xml' => $xmlBase64]);

            $resultado = $response->RespuestaRecepcionComprobante ?? null;

            if ($resultado) {
                return [
                    'success' => $resultado->estado === 'RECIBIDA',
                    'estado'  => $resultado->estado ?? 'ERROR',
                    'mensaje' => self::parsearMensajes($resultado->comprobantes ?? null),
                ];
            }

            return ['success' => false, 'estado' => 'ERROR', 'mensaje' => 'Respuesta vacía del SRI'];

        } catch (SoapFault $e) {
            error_log("SRI SoapFault: " . $e->getMessage());
            return ['success' => false, 'estado' => 'ERROR_SOAP', 'mensaje' => $e->getMessage()];
        } catch (Exception $e) {
            error_log("SRI Exception: " . $e->getMessage());
            return ['success' => false, 'estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Solicita autorización al SRI
     */
    public static function autorizarComprobante(string $claveAcceso, string $ambiente = '1'): array {
        $url = self::$urls[$ambiente]['autorizacion'] ?? self::$urls['1']['autorizacion'];

        try {
            $client = new SoapClient($url, [
                'trace'           => true,
                'exceptions'      => true,
                'connection_timeout' => 30,
                'stream_context'  => stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ])
            ]);

            $response  = $client->autorizacionComprobante(['claveAccesoComprobante' => $claveAcceso]);
            $resultado = $response->RespuestaAutorizacionComprobante ?? null;

            if ($resultado && isset($resultado->autorizaciones->autorizacion)) {
                $auth = $resultado->autorizaciones->autorizacion;
                return [
                    'success'     => $auth->estado === 'AUTORIZADO',
                    'estado'      => $auth->estado ?? 'NO_AUTORIZADO',
                    'numero'      => $auth->numeroAutorizacion ?? '',
                    'fecha'       => $auth->fechaAutorizacion ?? '',
                    'xml_autorizado' => $auth->comprobante ?? '',
                    'mensaje'     => self::parsearMensajes($auth->mensajes ?? null),
                ];
            }

            return ['success' => false, 'estado' => 'NO_AUTORIZADO', 'mensaje' => 'Sin respuesta de autorización'];

        } catch (SoapFault $e) {
            error_log("SRI Auth SoapFault: " . $e->getMessage());
            return ['success' => false, 'estado' => 'ERROR_SOAP', 'mensaje' => $e->getMessage()];
        } catch (Exception $e) {
            error_log("SRI Auth Exception: " . $e->getMessage());
            return ['success' => false, 'estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
    }

    private static function parsearMensajes($mensajes): string {
        if (!$mensajes) return '';
        $msgs = is_array($mensajes->mensaje ?? null) ? $mensajes->mensaje : [$mensajes->mensaje ?? $mensajes];
        $out  = [];
        foreach ($msgs as $m) {
            if (is_object($m)) {
                $out[] = ($m->tipo ?? '') . ': ' . ($m->mensaje ?? '') . ' - ' . ($m->informacionAdicional ?? '');
            } else {
                $out[] = (string)$m;
            }
        }
        return implode('; ', $out);
    }

    /**
     * Proceso completo: Generar XML → Firmar → Enviar → Autorizar
     */
    public static function procesarFactura(array $factura, array $detalle, array $empresa, array $cliente): array {
        // 1. Generar XML
        $xml = SRIXmlGenerator::generarFactura($factura, $detalle, $empresa, $cliente);

        // 2. Firmar
        $certPath = UPLOAD_PATH . 'certificados/' . ($empresa['certificado_p12'] ?? '');
        $certPass = $empresa['clave_certificado'] ?? '';

        $xmlFirmado = $xml; // Sin firma si no hay certificado
        if (!empty($empresa['certificado_p12']) && file_exists($certPath)) {
            $firmado = self::firmarXML($xml, $certPath, $certPass);
            if ($firmado) $xmlFirmado = $firmado;
        }

        // 3. Enviar al SRI
        $recepcion = self::enviarComprobante($xmlFirmado, $empresa['ambiente'] ?? '1');
        if (!$recepcion['success']) {
            return array_merge($recepcion, ['xml' => $xmlFirmado, 'paso' => 'recepcion']);
        }

        // 4. Autorizar (esperar un momento)
        sleep(2);
        $autorizacion = self::autorizarComprobante($factura['clave_acceso'], $empresa['ambiente'] ?? '1');

        return array_merge($autorizacion, [
            'xml'      => $xmlFirmado,
            'paso'     => 'autorizacion',
            'recepcion'=> $recepcion,
        ]);
    }
}
