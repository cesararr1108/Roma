<?php
/**
 * Nodo "compensacion" (solo rama anticipo, tras legalización aprobada):
 * contabilidad registra el número de compensación generado en SAP.
 * Siguiente nodo siempre fijo: "finalizado".
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
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'compensacion') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        try {
            $nodoSiguiente = 'finalizado';

            GastoRepository::guardarCompensacion($idSolicitud, $numeroCompensacion, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REGISTRAR_COMPENSACION', 'Compensación '.$numeroCompensacion.'.', $usuario['id']);

            Respuesta::ok(null, 'Compensación registrada. El flujo ha finalizado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
