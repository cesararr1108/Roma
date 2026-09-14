<?php
/**
 * Rama "requiere anticipo" que se desprende de una cotización aprobada:
 * el solicitante indica el tipo de anticipo y los datos del beneficiario
 * (propio o tercero), y queda pendiente de aprobación de gerencia
 * administrativa. Reutiliza los mismos validadores que
 * SolicitudAnticipoHandler porque piden exactamente los mismos campos.
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

        $tipoAnticipo = isset($_POST['tipoAnticipo']) ? $_POST['tipoAnticipo'] : '';
        if (!array_key_exists($tipoAnticipo, Config::tiposAnticipo())) {
            Respuesta::error('Debe indicar el tipo de anticipo (viáticos o bienes y servicios).');
        }

        $datosAnticipo = AnticipoDatos::leerYValidar();
        $datosViaticos = ($tipoAnticipo === Config::TIPO_ANTICIPO_VIATICOS) ? ViaticosDatos::leerSolicitudYValidar() : null;

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'ANTICIPO_PENDIENTE_APROBACION');

            GastoRepository::fijarTipoAnticipo($idSolicitud, $tipoAnticipo);
            GastoRepository::guardarAnticipo($idSolicitud, $datosAnticipo);

            if ($datosViaticos) {
                ViaticosRepository::guardarSolicitud($idSolicitud, $datosViaticos);
            }

            GastoRepository::cambiarEstado($idSolicitud, 'ANTICIPO_PENDIENTE_APROBACION');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'ANTICIPO_PENDIENTE_APROBACION',
                'REGISTRAR_DATOS_ANTICIPO', 'Datos de anticipo registrados para '.$datosAnticipo['nombreTercero'].'.', $usuario['id']);

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
