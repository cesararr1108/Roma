<?php
/**
 * Nodo "cruzado" (solo rama factura, sin anticipo): contabilidad cruza
 * el pago contra la factura. Siguiente nodo siempre fijo: "finalizado".
 */
class CruceHandler
{
    public static function registrar()
    {
        $usuario = Auth::requiereRol(Config::rolesContabilidad());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $numeroCruce = isset($_POST['numeroCruce']) ? trim($_POST['numeroCruce']) : '';

        if ($numeroCruce === '') {
            Respuesta::error('Debe indicar el número de cruce.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'cruzado') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        try {
            $nodoSiguiente = 'finalizado';

            GastoRepository::guardarCruce($idSolicitud, $numeroCruce, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REGISTRAR_CRUCE', 'Cruce '.$numeroCruce.'.', $usuario['id']);

            Respuesta::ok(null, 'Cruce registrado. El flujo ha finalizado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
