<?php
/**
 * Bitácora general del flujo: todos los cambios de nodo, motivos y
 * comentarios de cada solicitud quedan registrados aquí.
 */
class FlujoRepository
{
    public static function registrar($idSolicitud, $nodoAnterior, $nodoNuevo, $accion, $comentario, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_FLUJO_HISTORIAL
                    (ID_SOLICITUD, NODO_ANTERIOR, NODO_NUEVO, ACCION, COMENTARIO, ID_USUARIO, FECHA)
                VALUES
                    (:idSolicitud, :nodoAnterior, :nodoNuevo, :accion, :comentario, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud'   => $idSolicitud,
            'nodoAnterior'  => $nodoAnterior,
            'nodoNuevo'     => $nodoNuevo,
            'accion'        => $accion,
            'comentario'    => $comentario,
            'idUsuario'     => $idUsuario,
        ));
    }

    public static function historial($idSolicitud)
    {
        $sql = "SELECT h.ID, h.NODO_ANTERIOR, h.NODO_NUEVO, h.ACCION, h.COMENTARIO, h.FECHA,
                       h.ID_USUARIO, u.NOMBRES + ' ' + u.APELLIDOS AS USUARIO
                FROM GTOS_FLUJO_HISTORIAL h
                LEFT JOIN T_USUARIOS u ON u.ID = h.ID_USUARIO
                WHERE h.ID_SOLICITUD = :idSolicitud
                ORDER BY h.FECHA ASC, h.ID ASC";

        return Db::query($sql, array('idSolicitud' => $idSolicitud));
    }
}
