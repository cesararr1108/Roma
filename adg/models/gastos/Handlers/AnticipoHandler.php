<?php
/**
 * Paso 1.2 (rama anticipo): el solicitante registra los datos del
 * beneficiario (propio o tercero) y queda a la espera de aprobación de
 * gerencia administrativa (paso 1.2.1).
 */
class AnticipoHandler
{
    public static function registrarDatos()
    {
        $usuario = Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        Auth::requiereDueno($solicitud['ID_USUARIO_SOLICITA']);

        if ($solicitud['ESTADO'] !== 'COTIZACION_APROBADA') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        $datos = array(
            'beneficiario' => isset($_POST['beneficiario']) ? $_POST['beneficiario'] : 'PROPIO', // PROPIO | TERCERO
            'tipoPersona'  => isset($_POST['tipoPersona']) ? $_POST['tipoPersona'] : '',
            'nit'          => isset($_POST['nit']) ? trim($_POST['nit']) : '',
            'nombres'      => isset($_POST['nombres']) ? trim($_POST['nombres']) : '',
            'telefono'     => isset($_POST['telefono']) ? trim($_POST['telefono']) : '',
            'email'        => isset($_POST['email']) ? trim($_POST['email']) : '',
        );

        if ($datos['nit'] === '' || $datos['nombres'] === '') {
            Respuesta::error('El NIT y el nombre/razón comercial son obligatorios.');
        }
        if (!in_array($datos['tipoPersona'], array(Config::TIPO_PERSONA_NATURAL, Config::TIPO_PERSONA_JURIDICA), true)) {
            Respuesta::error('Debe indicar el tipo de persona (natural o jurídica).');
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'ANTICIPO_PENDIENTE_APROBACION');

            GastoRepository::guardarAnticipo($idSolicitud, $datos);
            GastoRepository::cambiarEstado($idSolicitud, 'ANTICIPO_PENDIENTE_APROBACION');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'ANTICIPO_PENDIENTE_APROBACION',
                'REGISTRAR_DATOS_ANTICIPO', 'Datos de anticipo registrados para '.$datos['nombres'].'.', $usuario['id']);

            Respuesta::ok(null, 'Anticipo enviado a aprobación de gerencia administrativa.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    public static function aprobar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['ESTADO'] !== 'ANTICIPO_PENDIENTE_APROBACION') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'ANTICIPO_APROBADO');

            GastoRepository::actualizarEstadoAnticipo($idSolicitud, 'APROBADO', null, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'ANTICIPO_APROBADO');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'ANTICIPO_APROBADO',
                'APROBAR_ANTICIPO', 'Anticipo aprobado.', $usuario['id']);

            Respuesta::ok(null, 'Anticipo aprobado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }

    public static function rechazar()
    {
        $usuario = Auth::requiereRol(Config::rolesGerenciaAdministrativa());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

        if ($motivo === '') {
            Respuesta::error('Debe indicar el motivo del rechazo.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['ESTADO'] !== 'ANTICIPO_PENDIENTE_APROBACION') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'ANTICIPO_RECHAZADO');

            GastoRepository::actualizarEstadoAnticipo($idSolicitud, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, 'ANTICIPO_RECHAZADO');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'ANTICIPO_RECHAZADO',
                'RECHAZAR_ANTICIPO', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Anticipo rechazado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
