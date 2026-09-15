<?php
/**
 * Creación de una solicitud tipo FACTURA DE GASTO: no requiere
 * cotizaciones -- arranca directo en el nodo "aprobacion_soporte".
 */
class SolicitudFacturaHandler
{
    public static function crear()
    {
        $usuario = Auth::usuarioActual();
        $comunes = SolicitudComun::leerDatosComunes($usuario);
        $datosFactura = FacturaDatos::leerYValidar();

        try {
            $nodoInicial = 'aprobacion_soporte';

            $idSolicitud = GastoRepository::crearSolicitud(array_merge($comunes, array(
                'tipoGasto'        => Config::TIPO_GASTO_FACTURA,
                'tipoAnticipo'     => null,
                'nodoInicial'      => $nodoInicial,
                'requiereAnticipo' => 0,
            )));

            GastoRepository::guardarFactura($idSolicitud, $datosFactura);

            FlujoRepository::registrar($idSolicitud, null, $nodoInicial,
                'SOLICITUD_CREADA', 'Solicitud de factura creada por un total de '.$datosFactura['total'].'.', $usuario['id']);

            Respuesta::ok(array('idSolicitud' => $idSolicitud, 'total' => $datosFactura['total']), 'Solicitud enviada a aprobación.');
        } catch (Exception $e) {
            Respuesta::error('Error al crear la solicitud: '.$e->getMessage());
        }
    }
}
