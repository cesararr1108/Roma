<?php
/**
 * Paso 1.1: gerencia administrativa aprueba (eligiendo la cotización
 * ganadora) o rechaza (con motivo) la solicitud.
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

        $solicitud = self::obtenerYValidarEstado($idSolicitud, 'EN_APROBACION_COTIZACION');

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'COTIZACION_APROBADA');

            GastoRepository::guardarAprobacionCotizacion($idSolicitud, $idCotizacion, 'APROBADO', null, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'COTIZACION_APROBADA');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'COTIZACION_APROBADA',
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

        $solicitud = self::obtenerYValidarEstado($idSolicitud, 'EN_APROBACION_COTIZACION');

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'COTIZACION_RECHAZADA');

            GastoRepository::guardarAprobacionCotizacion($idSolicitud, null, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'COTIZACION_RECHAZADA');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'COTIZACION_RECHAZADA',
                'RECHAZAR_COTIZACION', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Solicitud rechazada.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    private static function obtenerYValidarEstado($idSolicitud, $estadoEsperado)
    {
        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        if ($solicitud['ESTADO'] !== $estadoEsperado) {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }
        return $solicitud;
    }
}
