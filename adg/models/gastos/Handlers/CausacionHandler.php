<?php
/**
 * Nodo "causacion": contabilidad causa la solicitud. Siguiente nodo
 * siempre fijo: "pago".
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
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'causacion') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        try {
            $nodoSiguiente = 'pago';

            GastoRepository::guardarCausacion($idSolicitud, $numeroCausacion, $nota, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REGISTRAR_CAUSACION', 'Causación '.$numeroCausacion.'. '.$nota, $usuario['id']);

            Respuesta::ok(null, 'Causación registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
