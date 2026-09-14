<?php
/**
 * Contabilidad causa la solicitud, sin importar si llegó por factura
 * directa, cotización sin anticipo, o legalización de anticipo (todas
 * confluyen en SOPORTE_APROBADO antes de este paso).
 */
class CausacionHandler
{
    public static function registrar()
    {
        $usuario = Auth::requiereRol(Config::rolesContabilidad());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $numeroCausacion = isset($_POST['numeroCausacion']) ? trim($_POST['numeroCausacion']) : '';
        $nota = isset($_POST['nota']) ? trim($_POST['nota']) : '';

        if ($numeroCausacion === '') {
            Respuesta::error('Debe indicar el número de causación.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['ESTADO'] !== 'SOPORTE_APROBADO') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'CAUSADO');

            GastoRepository::guardarCausacion($idSolicitud, $numeroCausacion, $nota, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'CAUSADO');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'CAUSADO',
                'REGISTRAR_CAUSACION', 'Causación '.$numeroCausacion.'. '.$nota, $usuario['id']);

            Respuesta::ok(null, 'Causación registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
