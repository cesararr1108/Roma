<?php
/**
 * Registro de factura sobre una solicitud ya existente. Cubre dos puntos
 * del flujo que llegan al mismo estado destino: la rama "sin anticipo"
 * de una cotización aprobada, y la legalización de un anticipo de tipo
 * BIENES_SERVICIOS. Ambos casos usan el mismo formulario/validación
 * (FacturaDatos), por eso comparten un único handler.
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

        if (!in_array($solicitud['ESTADO'], array('COTIZACION_APROBADA', 'ANTICIPO_APROBADO'), true)) {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        $datosFactura = FacturaDatos::leerYValidar();

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'SOPORTE_PENDIENTE_APROBACION');

            GastoRepository::guardarFactura($idSolicitud, $datosFactura);
            GastoRepository::cambiarEstado($idSolicitud, 'SOPORTE_PENDIENTE_APROBACION');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'SOPORTE_PENDIENTE_APROBACION',
                'REGISTRAR_FACTURA', 'Factura '.$datosFactura['numeroFactura'].' registrada por un total de '.$datosFactura['total'].'.', $usuario['id']);

            Respuesta::ok(array('total' => $datosFactura['total']), 'Factura registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
