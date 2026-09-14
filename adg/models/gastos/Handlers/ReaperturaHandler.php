<?php
/**
 * Reabre una solicitud rechazada (cotización, anticipo o soporte) de
 * vuelta al estado pendiente del que salió. A diferencia del legacy (que
 * guardaba un "switch" numérico para saber a dónde volver), acá cada
 * estado de rechazo declara su propio 'reabreA' en Config::estados(), así
 * que no hace falta ninguna lógica ad-hoc.
 */
class ReaperturaHandler
{
    public static function reabrir()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }

        $definicion = Config::estado($solicitud['ESTADO']);
        if (!$definicion || empty($definicion['reabreA'])) {
            Respuesta::error('Esta solicitud no se puede reabrir desde su estado actual.');
        }

        $estadoDestino = $definicion['reabreA'];

        try {
            GastoRepository::cambiarEstado($idSolicitud, $estadoDestino);

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], $estadoDestino,
                'REABRIR_SOLICITUD', $comentario !== '' ? $comentario : 'Solicitud reabierta.', $usuario['id']);

            Respuesta::ok(null, 'Solicitud reabierta correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
