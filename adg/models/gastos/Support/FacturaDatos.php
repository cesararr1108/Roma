<?php
/**
 * Lectura/validación de los datos de factura. Se comparte entre 3 puntos
 * del flujo que piden exactamente los mismos campos: factura directa
 * (creación TIPO_GASTO_FACTURA), la rama "sin anticipo" tras una
 * cotización aprobada, y la legalización de un anticipo de tipo
 * BIENES_SERVICIOS.
 */
class FacturaDatos
{
    public static function leerYValidar()
    {
        $nit              = isset($_POST['nit']) ? trim($_POST['nit']) : '';
        $numeroPreliminar = isset($_POST['numeroPreliminarSap']) ? trim($_POST['numeroPreliminarSap']) : '';
        $numeroFactura    = isset($_POST['numeroFactura']) ? trim($_POST['numeroFactura']) : '';
        $fechaFactura     = isset($_POST['fechaFactura']) ? trim($_POST['fechaFactura']) : '';
        $idFondo          = isset($_POST['idFondo']) ? (int) $_POST['idFondo'] : 0;
        $numeroFondo      = isset($_POST['numeroFondo']) ? trim($_POST['numeroFondo']) : '';
        $subtotal         = isset($_POST['subtotal']) ? (float) $_POST['subtotal'] : 0;
        $iva              = isset($_POST['iva']) ? (float) $_POST['iva'] : 0;

        if ($nit === '' || $numeroPreliminar === '' || $numeroFactura === '' || $fechaFactura === '') {
            Respuesta::error('Debe diligenciar todos los datos de la factura y el preliminar en SAP.');
        }
        if (!array_key_exists($idFondo, Config::fondos())) {
            Respuesta::error('Debe seleccionar el fondo de donde sale el dinero.');
        }
        if (in_array($idFondo, Config::fondosQueRequierenNumero(), true) && $numeroFondo === '') {
            Respuesta::error('Debe indicar el número de fondo.');
        }
        if ($subtotal <= 0) {
            Respuesta::error('El subtotal debe ser mayor a cero.');
        }

        $tercero = GastoRepository::buscarTercero($nit);
        if (!$tercero) {
            Respuesta::error('El NIT del tercero no existe en la base de terceros.');
        }

        try {
            $archivo = ArchivoUploader::guardarPdf('factura', Config::RUTA_FACTURAS);
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }

        $nombreTercero = !empty($tercero['RAZON_COMERCIAL']) ? $tercero['RAZON_COMERCIAL'] : $tercero['NOMBRES'];
        $total = round($subtotal + $iva, 2);

        return array(
            'nit'              => $nit,
            'codigoSap'        => $tercero['CODIGO_SAP'],
            'nombreTercero'    => $nombreTercero,
            'numeroPreliminar' => $numeroPreliminar,
            'numeroFactura'    => $numeroFactura,
            'fechaFactura'     => $fechaFactura,
            'archivo'          => $archivo,
            'idFondo'          => $idFondo,
            'numeroFondo'      => $numeroFondo,
            'subtotal'         => $subtotal,
            'iva'              => $iva,
            'total'            => $total,
        );
    }
}
