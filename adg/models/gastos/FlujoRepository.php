<?php
/**
 * Bitácora general del flujo: todos los cambios de estado, motivos y
 * comentarios de cada solicitud quedan registrados aquí (requisito
 * explícito del módulo).
 */
class FlujoRepository
{
    public static function registrar($idSolicitud, $estadoAnterior, $estadoNuevo, $accion, $comentario, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_FLUJO_HISTORIAL
                    (ID_SOLICITUD, ESTADO_ANTERIOR, ESTADO_NUEVO, ACCION, COMENTARIO, ID_USUARIO, FECHA)
                VALUES
                    (:idSolicitud, :estadoAnterior, :estadoNuevo, :accion, :comentario, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud'    => $idSolicitud,
            'estadoAnterior' => $estadoAnterior,
            'estadoNuevo'    => $estadoNuevo,
            'accion'         => $accion,
            'comentario'     => $comentario,
            'idUsuario'      => $idUsuario,
        ));
    }

    public static function historial($idSolicitud)
    {
        // Ajustar el nombre de la tabla/columnas de usuarios (T_USUARIOS) si difiere en el proyecto real.
        $sql = "SELECT h.ID, h.ESTADO_ANTERIOR, h.ESTADO_NUEVO, h.ACCION, h.COMENTARIO, h.FECHA,
                       h.ID_USUARIO, u.NOMBRES + ' ' + u.APELLIDOS AS USUARIO
                FROM GTOS_FLUJO_HISTORIAL h
                LEFT JOIN T_USUARIOS u ON u.ID = h.ID_USUARIO
                WHERE h.ID_SOLICITUD = :idSolicitud
                ORDER BY h.FECHA ASC, h.ID ASC";

        return Db::query($sql, array('idSolicitud' => $idSolicitud));
    }
}
