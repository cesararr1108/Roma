<?php
/**
 * Adaptador de autenticación/autorización. Lee las variables de sesión
 * que ya establece el login del sistema (ses_Login, ses_Id, ses_RolesId, ...)
 * y expone guardas reutilizables por los Handlers.
 */
class Auth
{
    public static function usuarioActual()
    {
        if (empty($_SESSION['ses_Login'])) {
            Respuesta::error('Sesión no válida. Por favor inicie sesión nuevamente.', 401);
        }

        return array(
            'id'     => isset($_SESSION['ses_Id']) ? (int) $_SESSION['ses_Id'] : 0,
            'login'  => $_SESSION['ses_Login'],
            'nombre' => isset($_SESSION['ses_Usuario']) ? $_SESSION['ses_Usuario'] : '',
            'rolId'  => isset($_SESSION['ses_RolesId']) ? (int) $_SESSION['ses_RolesId'] : 0,
            'depId'  => isset($_SESSION['ses_DepId']) ? $_SESSION['ses_DepId'] : null,
        );
    }

    public static function requiereRol($rolesPermitidos)
    {
        $usuario = self::usuarioActual();
        if (!in_array($usuario['rolId'], $rolesPermitidos, true)) {
            Respuesta::error('No tiene permisos para realizar esta acción.', 403);
        }
        return $usuario;
    }

    public static function requiereDueno($idUsuarioDueno)
    {
        $usuario = self::usuarioActual();
        if ((int) $idUsuarioDueno !== (int) $usuario['id']) {
            Respuesta::error('Solo el solicitante original puede realizar esta acción.', 403);
        }
        return $usuario;
    }
}
