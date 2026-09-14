<?php
/**
 * Creación de una solicitud tipo ANTICIPO: no requiere cotización previa,
 * queda pendiente de aprobación de gerencia administrativa directamente.
 * Si el sub-tipo es VIATICOS, exige además el formulario estructurado de
 * solicitud de viáticos (presupuesto por rubro).
 */
class SolicitudAnticipoHandler
{
    public static function crear()
    {
        $usuario = Auth::usuarioActual();
        $comunes = SolicitudComun::leerDatosComunes($usuario);

        $tipoAnticipo = isset($_POST['tipoAnticipo']) ? $_POST['tipoAnticipo'] : '';
        if (!array_key_exists($tipoAnticipo, Config::tiposAnticipo())) {
            Respuesta::error('Debe indicar el tipo de anticipo (viáticos o bienes y servicios).');
        }

        $datosAnticipo = AnticipoDatos::leerYValidar();
        $datosViaticos = ($tipoAnticipo === Config::TIPO_ANTICIPO_VIATICOS) ? ViaticosDatos::leerSolicitudYValidar() : null;

        try {
            $estadoInicial = Config::estadoInicial(Config::TIPO_GASTO_ANTICIPO);

            $idSolicitud = GastoRepository::crearSolicitud(array_merge($comunes, array(
                'tipoGasto'        => Config::TIPO_GASTO_ANTICIPO,
                'tipoAnticipo'     => $tipoAnticipo,
                'estado'           => $estadoInicial,
                'requiereAnticipo' => 1,
            )));

            GastoRepository::guardarAnticipo($idSolicitud, $datosAnticipo);

            if ($datosViaticos) {
                ViaticosRepository::guardarSolicitud($idSolicitud, $datosViaticos);
            }

            FlujoRepository::registrar($idSolicitud, null, $estadoInicial,
                'SOLICITUD_CREADA', 'Solicitud de anticipo creada para '.$datosAnticipo['nombreTercero'].'.', $usuario['id']);

            Respuesta::ok(array('idSolicitud' => $idSolicitud), 'Solicitud de anticipo enviada a aprobación.');
        } catch (Exception $e) {
            Respuesta::error('Error al crear la solicitud: '.$e->getMessage());
        }
    }
}
