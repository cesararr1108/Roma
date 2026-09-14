<?php
/**
 * Paso 1.5 (solo rama anticipo): contabilidad registra el número de
 * compensación generado en SAP y el flujo finaliza.
 */
class CompensacionHandler
{
    public static function registrar()
    {
        $usuario = Auth::requiereRol(Config::rolesContabilidad());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $numeroCompensacion = isset($_POST['numeroCompensacion']) ? trim($_POST['numeroCompensacion']) : '';

        if ($numeroCompensacion === '') {
            Respuesta::error('Debe indicar el número de compensación generado en SAP.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['ESTADO'] !== 'PAGADO' || (int) $solicitud['REQUIERE_ANTICIPO'] !== 1) {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'COMPENSADO');
            EstadoMachine::validarTransicion('COMPENSADO', 'FINALIZADO');

            GastoRepository::guardarCompensacion($idSolicitud, $numeroCompensacion, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'COMPENSADO');
            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'COMPENSADO',
                'REGISTRAR_COMPENSACION', 'Compensación '.$numeroCompensacion.'.', $usuario['id']);

            GastoRepository::cambiarEstado($idSolicitud, 'FINALIZADO');
            FlujoRepository::registrar($idSolicitud, 'COMPENSADO', 'FINALIZADO',
                'FINALIZAR', 'Flujo finalizado.', $usuario['id']);

            Respuesta::ok(null, 'Compensación registrada. El flujo ha finalizado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
