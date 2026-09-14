<?php
/**
 * Consultas de lectura: catálogos iniciales, bandeja de trabajo, detalle
 * de una solicitud y autocompletado de terceros.
 */
class ConsultaHandler
{
    public static function cargarDatos()
    {
        $usuario = Auth::usuarioActual();

        Respuesta::ok(array(
            'usuario'                  => $usuario,
            'estados'                  => Config::estados(),
            'fondos'                   => Config::fondos(),
            'esGerenciaAdministrativa' => in_array($usuario['rolId'], Config::rolesGerenciaAdministrativa(), true),
            'esContabilidad'           => in_array($usuario['rolId'], Config::rolesContabilidad(), true),
            'esTesoreria'              => in_array($usuario['rolId'], Config::rolesTesoreria(), true),
        ));
    }

    public static function listarBandeja()
    {
        $usuario = Auth::usuarioActual();
        $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : 'mias';

        Respuesta::ok(GastoRepository::listarBandeja($usuario, $filtro));
    }

    public static function obtenerSolicitud()
    {
        Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        if ($idSolicitud <= 0) {
            Respuesta::error('Debe indicar la solicitud a consultar.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }

        $solicitud['cotizaciones'] = GastoRepository::cotizacionesDeSolicitud($idSolicitud);
        $solicitud['anticipo']     = GastoRepository::obtenerAnticipo($idSolicitud);
        $solicitud['factura']      = GastoRepository::obtenerFactura($idSolicitud);
        $solicitud['causacion']    = GastoRepository::obtenerCausacion($idSolicitud);
        $solicitud['pago']         = GastoRepository::obtenerPago($idSolicitud);
        $solicitud['compensacion'] = GastoRepository::obtenerCompensacion($idSolicitud);
        $solicitud['historial']    = FlujoRepository::historial($idSolicitud);
        $solicitud['estadoInfo']   = Config::estado($solicitud['ESTADO']);

        Respuesta::ok($solicitud);
    }

    public static function buscarTercero()
    {
        Auth::usuarioActual();
        $nit = isset($_POST['nit']) ? trim($_POST['nit']) : '';

        if ($nit === '') {
            Respuesta::error('Debe indicar el NIT a buscar.');
        }

        $tercero = GastoRepository::buscarTercero($nit);
        if (!$tercero) {
            Respuesta::error('No se encontró un tercero con ese NIT.', 404);
        }

        Respuesta::ok($tercero);
    }
}
