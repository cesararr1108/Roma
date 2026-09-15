<?php
/**
 * Registra una factura. La misma acción sirve para dos nodos distintos
 * del flujo -- "factura_datos" (cotización sin anticipo, o factura
 * directa cuando ya trae los datos desde la creación) y "legalizacion"
 * (anticipo de bienes/servicios) -- porque ambos piden exactamente el
 * mismo formulario y ambos terminan en el mismo sitio: aprobación de
 * gerencia administrativa.
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

        if (!in_array($solicitud['NODO_ACTUAL'], array('factura_datos', 'legalizacion'), true)) {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        $datosFactura = FacturaDatos::leerYValidar();

        try {
            $nodoSiguiente = 'aprobacion_soporte';

            GastoRepository::guardarFactura($idSolicitud, $datosFactura);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REGISTRAR_FACTURA', 'Factura '.$datosFactura['numeroFactura'].' registrada por un total de '.$datosFactura['total'].'.', $usuario['id']);

            Respuesta::ok(array('total' => $datosFactura['total']), 'Factura registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
