<?php
/**
 * Segunda aprobación de gerencia administrativa: sobre la factura
 * (directa, sin-anticipo, o legalización de bienes/servicios) o sobre la
 * legalización de viáticos. Todas llegan al mismo estado
 * SOPORTE_PENDIENTE_APROBACION, por eso un único handler las cubre.
 */
class AprobacionSoporteHandler
{
    public static function aprobar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = self::obtenerYValidarEstado($idSolicitud);

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'SOPORTE_APROBADO');

            GastoRepository::guardarAprobacionSoporte($idSolicitud, 'APROBADO', null, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'SOPORTE_APROBADO');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'SOPORTE_APROBADO',
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

        $solicitud = self::obtenerYValidarEstado($idSolicitud);

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'SOPORTE_RECHAZADO');

            GastoRepository::guardarAprobacionSoporte($idSolicitud, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'SOPORTE_RECHAZADO');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'SOPORTE_RECHAZADO',
                'RECHAZAR_SOPORTE', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Factura/legalización rechazada.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    private static function obtenerYValidarEstado($idSolicitud)
    {
        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        if ($solicitud['ESTADO'] !== 'SOPORTE_PENDIENTE_APROBACION') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }
        return $solicitud;
    }
}
