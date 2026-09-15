<?php
/**
 * Observación sin cambio de nodo: cualquiera de los roles que participan
 * del flujo (o el dueño) puede dejar una nota en la bitácora sin mover
 * la solicitud, para dejar constancia de algo sin bloquear el paso.
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

        $rolesConAcceso = array_merge(
            Config::rolesGerenciaAdministrativa(),
            Config::rolesContabilidad(),
            Config::rolesTesoreria()
        );
        $esDueno = (int) $solicitud['ID_USUARIO_SOLICITA'] === (int) $usuario['id'];

        if (!$esDueno && !in_array($usuario['rolId'], $rolesConAcceso, true)) {
            Respuesta::error('No tiene permisos para dejar observaciones en esta solicitud.', 403);
        }

        FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $solicitud['NODO_ACTUAL'],
            'OBSERVACION', $comentario, $usuario['id']);

        Respuesta::ok(null, 'Observación registrada.');
    }
}
