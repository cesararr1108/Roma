<?php
/**
 * Paso 1.2 (rama factura): el solicitante registra los datos del tercero,
 * el preliminar en SAP, la factura en PDF y el fondo de pago. El
 * subtotal + IVA se calculan como total en el servidor (nunca se confía
 * en el total enviado por el cliente).
 */
class FacturaHandler
{
    public static function registrar()
    {
        $usuario = Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        Auth::requiereDueno($solicitud['ID_USUARIO_SOLICITA']);

        if ($solicitud['ESTADO'] !== 'COTIZACION_APROBADA') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

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
        if ($idFondo !== Config::FONDO_ROMA && $numeroFondo === '') {
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

        $total = round($subtotal + $iva, 2);

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'FACTURA_REGISTRADA');

            GastoRepository::guardarFactura($idSolicitud, array(
                'nit'              => $nit,
                'codigoSap'        => $tercero['CODIGO_SAP'],
                'nombreTercero'    => $tercero['NOMBRES'],
                'numeroPreliminar' => $numeroPreliminar,
                'numeroFactura'    => $numeroFactura,
                'fechaFactura'     => $fechaFactura,
                'archivo'          => $archivo,
                'idFondo'          => $idFondo,
                'numeroFondo'      => $numeroFondo,
                'subtotal'         => $subtotal,
                'iva'              => $iva,
                'total'            => $total,
            ));

            GastoRepository::cambiarEstado($idSolicitud, 'FACTURA_REGISTRADA');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'FACTURA_REGISTRADA',
                'REGISTRAR_FACTURA', 'Factura '.$numeroFactura.' registrada por un total de '.$total.'.', $usuario['id']);

            Respuesta::ok(array('total' => $total), 'Factura registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
