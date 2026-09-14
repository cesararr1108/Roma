<?php
/**
 * Creación de una solicitud tipo FACTURA DE GASTO: no requiere
 * cotizaciones ni la aprobación previa de gerencia administrativa sobre
 * una cotización -- arranca directo en la aprobación de la factura.
 */
class SolicitudFacturaHandler
{
    public static function crear()
    {
        $usuario = Auth::usuarioActual();
        $comunes = SolicitudComun::leerDatosComunes($usuario);
        $datosFactura = FacturaDatos::leerYValidar();

        try {
            $estadoInicial = Config::estadoInicial(Config::TIPO_GASTO_FACTURA);

            $idSolicitud = GastoRepository::crearSolicitud(array_merge($comunes, array(
                'tipoGasto'        => Config::TIPO_GASTO_FACTURA,
                'tipoAnticipo'     => null,
                'estado'           => $estadoInicial,
                'requiereAnticipo' => 0,
            )));

            GastoRepository::guardarFactura($idSolicitud, $datosFactura);

            FlujoRepository::registrar($idSolicitud, null, $estadoInicial,
                'SOLICITUD_CREADA', 'Solicitud de factura creada por un total de '.$datosFactura['total'].'.', $usuario['id']);

            Respuesta::ok(array('idSolicitud' => $idSolicitud, 'total' => $datosFactura['total']), 'Solicitud enviada a aprobación.');
        } catch (Exception $e) {
            Respuesta::error('Error al crear la solicitud: '.$e->getMessage());
        }
    }
}
