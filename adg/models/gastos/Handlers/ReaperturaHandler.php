<?php
/**
 * Reabre una solicitud rechazada de vuelta al nodo del que salió. Es un
 * mapa fijo de 3 entradas (los 3 puntos de rechazo del flujo) -- no un
 * motor de reglas, no conoce el resto del grafo.
 */
class ReaperturaHandler
{
    private static $vuelveA = array(
        'rechazado_cotizacion' => 'aprobacion_cotizacion',
        'rechazado_anticipo'   => 'anticipo_aprobacion',
        'rechazado_soporte'    => 'aprobacion_soporte',
    );

    public static function reabrir()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }

        if (!isset(self::$vuelveA[$solicitud['NODO_ACTUAL']])) {
            Respuesta::error('Esta solicitud no se puede reabrir desde su paso actual.');
        }

        $nodoSiguiente = self::$vuelveA[$solicitud['NODO_ACTUAL']];

        try {
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REABRIR_SOLICITUD', $comentario !== '' ? $comentario : 'Solicitud reabierta.', $usuario['id']);

            Respuesta::ok(null, 'Solicitud reabierta correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
