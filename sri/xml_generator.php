<?php
// sri/xml_generator.php
// Generador de XML para facturas electrónicas según especificaciones SRI Ecuador

class SRIXmlGenerator {

    public static function generarFactura(array $factura, array $detalle, array $empresa, array $cliente): string {
        $fechaEmision = date('d/m/Y', strtotime($factura['fecha_emision']));
        $claveAcceso  = $factura['clave_acceso'];
        $secuencial   = str_pad(explode('-', $factura['numero_factura'])[2] ?? '1', 9, '0', STR_PAD_LEFT);

        // Calcular totales por tarifa IVA
        $totalSinImpuesto = 0;
        $totalConImpuesto = 0;
        $baseImponible15  = 0;
        $baseImponible0   = 0;
        $valorIva15       = 0;

        foreach ($detalle as $d) {
            $base = ($d['precio_unitario'] * $d['cantidad']) - $d['descuento'];
            if ($d['iva_porcentaje'] > 0) {
                $baseImponible15 += $base;
                $valorIva15      += $base * ($d['iva_porcentaje'] / 100);
            } else {
                $baseImponible0  += $base;
            }
            $totalSinImpuesto += $base;
        }
        $totalConImpuesto = $totalSinImpuesto + $valorIva15;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<factura id="comprobante" version="1.1.0">' . "\n";

        // ── infoTributaria ──────────────────────────────────────
        $xml .= '  <infoTributaria>' . "\n";
        $xml .= '    <ambiente>' . ($empresa['ambiente'] ?? '1') . '</ambiente>' . "\n";
        $xml .= '    <tipoEmision>1</tipoEmision>' . "\n";
        $xml .= '    <razonSocial>' . self::esc($empresa['razon_social']) . '</razonSocial>' . "\n";
        $xml .= '    <nombreComercial>' . self::esc($empresa['nombre_comercial'] ?? $empresa['razon_social']) . '</nombreComercial>' . "\n";
        $xml .= '    <ruc>' . self::esc($empresa['ruc']) . '</ruc>' . "\n";
        $xml .= '    <claveAcceso>' . $claveAcceso . '</claveAcceso>' . "\n";
        $xml .= '    <codDoc>01</codDoc>' . "\n";
        $xml .= '    <estab>' . ($empresa['establecimiento'] ?? '001') . '</estab>' . "\n";
        $xml .= '    <ptoEmi>' . ($empresa['punto_emision'] ?? '001') . '</ptoEmi>' . "\n";
        $xml .= '    <secuencial>' . $secuencial . '</secuencial>' . "\n";
        $xml .= '    <dirMatriz>' . self::esc($empresa['direccion_matriz'] ?? '') . '</dirMatriz>' . "\n";
        $xml .= '  </infoTributaria>' . "\n";

        // ── infoFactura ─────────────────────────────────────────
        $xml .= '  <infoFactura>' . "\n";
        $xml .= '    <fechaEmision>' . $fechaEmision . '</fechaEmision>' . "\n";
        $xml .= '    <dirEstablecimiento>' . self::esc($empresa['direccion_matriz'] ?? '') . '</dirEstablecimiento>' . "\n";
        $xml .= '    <obligadoContabilidad>NO</obligadoContabilidad>' . "\n";
        $xml .= '    <tipoIdentificacionComprador>' . self::tipoIdSRI($cliente['tipo_identificacion']) . '</tipoIdentificacionComprador>' . "\n";
        $xml .= '    <razonSocialComprador>' . self::esc($cliente['nombres']) . '</razonSocialComprador>' . "\n";
        $xml .= '    <identificacionComprador>' . self::esc($cliente['cedula']) . '</identificacionComprador>' . "\n";
        $xml .= '    <totalSinImpuestos>' . number_format($totalSinImpuesto, 2, '.', '') . '</totalSinImpuestos>' . "\n";
        $xml .= '    <totalDescuento>' . number_format($factura['descuento'], 2, '.', '') . '</totalDescuento>' . "\n";

        // Totales con impuestos
        $xml .= '    <totalConImpuestos>' . "\n";
        if ($baseImponible0 > 0) {
            $xml .= '      <totalImpuesto>' . "\n";
            $xml .= '        <codigo>2</codigo>' . "\n";
            $xml .= '        <codigoPorcentaje>0</codigoPorcentaje>' . "\n";
            $xml .= '        <baseImponible>' . number_format($baseImponible0, 2, '.', '') . '</baseImponible>' . "\n";
            $xml .= '        <valor>0.00</valor>' . "\n";
            $xml .= '      </totalImpuesto>' . "\n";
        }
        if ($baseImponible15 > 0) {
            $xml .= '      <totalImpuesto>' . "\n";
            $xml .= '        <codigo>2</codigo>' . "\n";
            $xml .= '        <codigoPorcentaje>4</codigoPorcentaje>' . "\n";
            $xml .= '        <baseImponible>' . number_format($baseImponible15, 2, '.', '') . '</baseImponible>' . "\n";
            $xml .= '        <valor>' . number_format($valorIva15, 2, '.', '') . '</valor>' . "\n";
            $xml .= '      </totalImpuesto>' . "\n";
        }
        $xml .= '    </totalConImpuestos>' . "\n";

        $xml .= '    <propina>0.00</propina>' . "\n";
        $xml .= '    <importeTotal>' . number_format($factura['total'], 2, '.', '') . '</importeTotal>' . "\n";
        $xml .= '    <moneda>DOLAR</moneda>' . "\n";

        // Pagos
        $fpCod = ['efectivo'=>'01','tarjeta'=>'19','transferencia'=>'17','cheque'=>'12','credito'=>'21'];
        $fp    = $fpCod[$factura['forma_pago']] ?? '01';
        $xml .= '    <pagos>' . "\n";
        $xml .= '      <pago>' . "\n";
        $xml .= '        <formaPago>' . $fp . '</formaPago>' . "\n";
        $xml .= '        <total>' . number_format($factura['total'], 2, '.', '') . '</total>' . "\n";
        $xml .= '        <plazo>0</plazo>' . "\n";
        $xml .= '        <unidadTiempo>dias</unidadTiempo>' . "\n";
        $xml .= '      </pago>' . "\n";
        $xml .= '    </pagos>' . "\n";
        $xml .= '  </infoFactura>' . "\n";

        // ── detalles ────────────────────────────────────────────
        $xml .= '  <detalles>' . "\n";
        foreach ($detalle as $d) {
            $precioUnitario = $d['precio_unitario'];
            $descuento      = $d['descuento'];
            $cantidad       = $d['cantidad'];
            $precioTotalSinImpuesto = ($precioUnitario * $cantidad) - $descuento;

            $xml .= '    <detalle>' . "\n";
            $xml .= '      <codigoPrincipal>' . self::esc($d['codigo'] ?? $d['producto_id']) . '</codigoPrincipal>' . "\n";
            $xml .= '      <descripcion>' . self::esc($d['descripcion'] ?? $d['nombre']) . '</descripcion>' . "\n";
            $xml .= '      <cantidad>' . number_format($cantidad, 6, '.', '') . '</cantidad>' . "\n";
            $xml .= '      <precioUnitario>' . number_format($precioUnitario, 6, '.', '') . '</precioUnitario>' . "\n";
            $xml .= '      <descuento>' . number_format($descuento, 2, '.', '') . '</descuento>' . "\n";
            $xml .= '      <precioTotalSinImpuesto>' . number_format($precioTotalSinImpuesto, 2, '.', '') . '</precioTotalSinImpuesto>' . "\n";
            $xml .= '      <impuestos>' . "\n";
            $xml .= '        <impuesto>' . "\n";
            $xml .= '          <codigo>2</codigo>' . "\n";
            if ($d['iva_porcentaje'] > 0) {
                $xml .= '          <codigoPorcentaje>4</codigoPorcentaje>' . "\n";
                $xml .= '          <tarifa>' . number_format($d['iva_porcentaje'], 0) . '</tarifa>' . "\n";
            } else {
                $xml .= '          <codigoPorcentaje>0</codigoPorcentaje>' . "\n";
                $xml .= '          <tarifa>0</tarifa>' . "\n";
            }
            $ivaVal = $precioTotalSinImpuesto * ($d['iva_porcentaje'] / 100);
            $xml .= '          <baseImponible>' . number_format($precioTotalSinImpuesto, 2, '.', '') . '</baseImponible>' . "\n";
            $xml .= '          <valor>' . number_format($ivaVal, 2, '.', '') . '</valor>' . "\n";
            $xml .= '        </impuesto>' . "\n";
            $xml .= '      </impuestos>' . "\n";
            $xml .= '    </detalle>' . "\n";
        }
        $xml .= '  </detalles>' . "\n";

        // Información adicional
        if (!empty($cliente['correo'])) {
            $xml .= '  <infoAdicional>' . "\n";
            $xml .= '    <campoAdicional nombre="email">' . self::esc($cliente['correo']) . '</campoAdicional>' . "\n";
            if (!empty($cliente['telefono'])) {
                $xml .= '    <campoAdicional nombre="telefono">' . self::esc($cliente['telefono']) . '</campoAdicional>' . "\n";
            }
            $xml .= '  </infoAdicional>' . "\n";
        }

        $xml .= '</factura>';
        return $xml;
    }

    private static function esc(string $str): string {
        return htmlspecialchars($str, ENT_XML1, 'UTF-8');
    }

    private static function tipoIdSRI(string $tipo): string {
        return match($tipo) {
            'ruc'       => '04',
            'pasaporte' => '06',
            default     => '05', // cédula
        };
    }
}
