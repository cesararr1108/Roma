<?php
/**
 * Nodo "aprobacion_soporte": gerencia administrativa aprueba o rechaza
 * la factura o la legalización de viáticos -- llegan aquí desde dos
 * puntos distintos del flujo (factura_datos, o legalizacion tras haber
 * pasado ya por causación/pago), así que el siguiente nodo depende de
 * cuál de los dos era: si ya existe una causación guardada para esta
 * solicitud, es que viene de legalizar un anticipo y sigue a
 * compensación; si no, es la primera vuelta y sigue a causación. Ese
 * dato sale de una tabla que el propio servidor ya escribió antes --
 * nunca de lo que mande el navegador.
 */
class AprobacionSoporteHandler
{
    public static function aprobar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = self::obtenerYValidarNodo($idSolicitud);

        try {
            $yaCausado = GastoRepository::obtenerCausacion($idSolicitud) !== null;
            $nodoSiguiente = $yaCausado ? 'compensacion' : 'causacion';

            GastoRepository::guardarAprobacionSoporte($idSolicitud, 'APROBADO', null, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'APROBAR_SOPORTE', 'Factura/legalización aprobada.', $usuario['id']);

            Respuesta::ok(null, 'Factura/legalización aprobada.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    public static function rechazar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

        if ($motivo === '') {
            Respuesta::error('Debe indicar el motivo del rechazo.');
        }

        $solicitud = self::obtenerYValidarNodo($idSolicitud);

        try {
            $nodoSiguiente = 'rechazado_soporte';

            GastoRepository::guardarAprobacionSoporte($idSolicitud, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'RECHAZAR_SOPORTE', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Factura/legalización rechazada.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    private static function obtenerYValidarNodo($idSolicitud)
    {
        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        if ($solicitud['NODO_ACTUAL'] !== 'aprobacion_soporte') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }
        return $solicitud;
    }
}
