<?php
/**
 * Nodo "aprobacion_cotizacion": gerencia administrativa aprueba
 * (eligiendo la cotización ganadora) o rechaza. Precondición propia de
 * esta acción: la solicitud debe estar en ese nodo -- eso no es "conocer
 * el flujo", es una comprobación local de esta única acción.
 */
class AprobacionHandler
{
    public static function aprobar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());

        $idSolicitud  = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $idCotizacion = isset($_POST['idCotizacionAprobada']) ? (int) $_POST['idCotizacionAprobada'] : 0;

        if ($idSolicitud <= 0 || $idCotizacion <= 0) {
            Respuesta::error('Debe seleccionar la cotización aprobada.');
        }

        $solicitud = self::obtenerYValidarNodo($idSolicitud, 'aprobacion_cotizacion');

        try {
            $nodoSiguiente = 'decision_anticipo_factura';

            GastoRepository::guardarAprobacionCotizacion($idSolicitud, $idCotizacion, 'APROBADO', null, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'APROBAR_COTIZACION', 'Cotización #'.$idCotizacion.' aprobada.', $usuario['id']);

            Respuesta::ok(null, 'Cotización aprobada correctamente.');
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

        $solicitud = self::obtenerYValidarNodo($idSolicitud, 'aprobacion_cotizacion');

        try {
            $nodoSiguiente = 'rechazado_cotizacion';

            GastoRepository::guardarAprobacionCotizacion($idSolicitud, null, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'RECHAZAR_COTIZACION', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Solicitud rechazada.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    private static function obtenerYValidarNodo($idSolicitud, $nodoEsperado)
    {
        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        if ($solicitud['NODO_ACTUAL'] !== $nodoEsperado) {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }
        return $solicitud;
    }
}
