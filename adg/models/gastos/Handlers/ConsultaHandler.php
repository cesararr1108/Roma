<?php
/**
 * Consultas de lectura: catálogos iniciales, bandeja de trabajo, detalle
 * de una solicitud (incluye viáticos cuando aplica) y datos de apoyo
 * (terceros, conceptos, oficinas, datos del usuario logueado).
 */
class ConsultaHandler
{
    public static function cargarDatos()
    {
        $usuario = Auth::usuarioActual();

        Respuesta::ok(array(
            'usuario'                  => $usuario,
            'estados'                  => Config::estados(),
            'tiposGasto'               => Config::tiposGasto(),
            'tiposAnticipo'            => Config::tiposAnticipo(),
            'organizaciones'           => Config::organizaciones(),
            'fondos'                   => Config::fondos(),
            'tarifasViaticos'          => Config::tarifasViaticos(),
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

        if ($solicitud['TIPO_ANTICIPO'] === Config::TIPO_ANTICIPO_VIATICOS) {
            $solicitud['viaticosSolicitud']    = ViaticosRepository::obtenerSolicitud($idSolicitud);
            $solicitud['viaticosLegalizacion'] = ViaticosRepository::obtenerLegalizacion($idSolicitud);
        }

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

    public static function listarConceptos()
    {
        Auth::usuarioActual();
        Respuesta::ok(GastoRepository::listarConceptos());
    }

    public static function crearConcepto()
    {
        $usuario = Auth::usuarioActual();
        $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';

        if ($concepto === '') {
            Respuesta::error('Debe indicar el nombre del concepto.');
        }

        $idConcepto = GastoRepository::crearConcepto($concepto);
        Respuesta::ok(array('id' => $idConcepto, 'concepto' => $concepto), 'Concepto creado correctamente.');
    }

    public static function listarOficinas()
    {
        Auth::usuarioActual();
        $organizacionVentas = isset($_POST['organizacionVentas']) ? trim($_POST['organizacionVentas']) : '';

        if ($organizacionVentas === '') {
            Respuesta::error('Debe indicar la organización de ventas.');
        }

        Respuesta::ok(GastoRepository::listarOficinas($organizacionVentas));
    }

    /** Datos de apoyo para autocompletar "a mi nombre" y mostrar la tarifa de viáticos del usuario. */
    public static function datosUsuarioActual()
    {
        $usuario = Auth::usuarioActual();
        $datos = GastoRepository::datosUsuarioActual($usuario['id']);

        if (!$datos) {
            Respuesta::error('No fue posible obtener los datos del usuario.', 404);
        }

        Respuesta::ok($datos);
    }
}
