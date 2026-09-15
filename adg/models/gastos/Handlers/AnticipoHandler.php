<?php
/**
 * Nodo "anticipo_datos": el dueño registra los datos del beneficiario
 * (propio o tercero) del anticipo. Nodo "anticipo_aprobacion": gerencia
 * administrativa aprueba o rechaza.
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

        if ($solicitud['NODO_ACTUAL'] !== 'anticipo_datos') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        $tipoAnticipo = isset($_POST['tipoAnticipo']) ? $_POST['tipoAnticipo'] : '';
        if (!array_key_exists($tipoAnticipo, Config::tiposAnticipo())) {
            Respuesta::error('Debe indicar el tipo de anticipo (viáticos o bienes y servicios).');
        }

        $datosAnticipo = AnticipoDatos::leerYValidar();
        $datosViaticos = ($tipoAnticipo === Config::TIPO_ANTICIPO_VIATICOS) ? ViaticosDatos::leerSolicitudYValidar() : null;

        try {
            $nodoSiguiente = 'anticipo_aprobacion';

            GastoRepository::fijarTipoAnticipo($idSolicitud, $tipoAnticipo);
            GastoRepository::guardarAnticipo($idSolicitud, $datosAnticipo);

            if ($datosViaticos) {
                ViaticosRepository::guardarSolicitud($idSolicitud, $datosViaticos);
            }

            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
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
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'anticipo_aprobacion') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        try {
            $nodoSiguiente = 'causacion';

            GastoRepository::actualizarEstadoAnticipo($idSolicitud, 'APROBADO', null, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
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
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'anticipo_aprobacion') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        try {
            $nodoSiguiente = 'rechazado_anticipo';

            GastoRepository::actualizarEstadoAnticipo($idSolicitud, 'RECHAZADO', $motivo, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'RECHAZAR_ANTICIPO', $motivo, $usuario['id']);

            Respuesta::ok(null, 'Anticipo rechazado.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
