<?php
/**
 * Observación sin cambio de estado: cubre el "no causado / no pagado /
 * no compensado" del legacy sin inventar estados de devolución nuevos --
 * el rol responsable simplemente deja un comentario en la bitácora
 * (visible para el solicitante) y la solicitud sigue en su misma cola de
 * trabajo para reintentar la acción.
 */
class ObservacionHandler
{
    public static function agregar()
    {
        $usuario = Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

        if ($comentario === '') {
            Respuesta::error('Debe escribir la observación.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }

        if (!EstadoMachine::esResponsable($solicitud['ESTADO'], $usuario['rolId'])) {
            Respuesta::error('No tiene permisos para dejar observaciones en el estado actual de esta solicitud.', 403);
        }

        FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], $solicitud['ESTADO'],
            'OBSERVACION', $comentario, $usuario['id']);

        Respuesta::ok(null, 'Observación registrada.');
    }
}
