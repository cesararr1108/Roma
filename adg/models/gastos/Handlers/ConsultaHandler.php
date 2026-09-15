<?php
/**
 * Consultas de lectura: catálogos iniciales, bandeja de trabajo, y el
 * contexto completo de una solicitud (lo que MotorFlujo.js necesita
 * para decidir qué nodo pintar). Ninguna de ellas decide qué sigue --
 * solo entregan datos ya guardados, remapeados a un formato cómodo
 * para el JS (camelCase) en vez de las columnas crudas de la tabla.
 */
class ConsultaHandler
{
    public static function cargarDatos()
    {
        $usuario = Auth::usuarioActual();

        Respuesta::ok(array(
            'usuario'                  => $usuario,
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

    public static function obtenerContexto()
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

        $contexto = array(
            'idSolicitud'        => (int) $solicitud['ID'],
            'nodoActual'         => $solicitud['NODO_ACTUAL'],
            'tipoGasto'          => (int) $solicitud['TIPO_GASTO'],
            'tipoAnticipo'       => $solicitud['TIPO_ANTICIPO'],
            'idConcepto'         => (int) $solicitud['ID_CONCEPTO'],
            'nombreConcepto'     => $solicitud['NOMBRE_CONCEPTO'],
            'requiereSoporte'    => (int) $solicitud['REQUIERE_SOPORTE'],
            'requiereAnticipo'   => (int) $solicitud['REQUIERE_ANTICIPO'],
            'comentarioSolicita' => $solicitud['COMENTARIO_SOLICITA'],
            'idUsuarioSolicita'  => (int) $solicitud['ID_USUARIO_SOLICITA'],
            'nombreSolicitante'  => $solicitud['NOMBRE_SOLICITANTE'],
            'organizacionVentas' => $solicitud['ORGANIZACION_VENTAS'],
            'oficinaVentas'      => $solicitud['OFICINA_VENTAS'],
            'fechaCreacion'      => $solicitud['FECHA_CREACION'],
            'fechaModificacion'  => $solicitud['FECHA_MODIFICACION'],
            'cotizaciones'       => GastoRepository::cotizacionesDeSolicitud($idSolicitud),
            'anticipo'           => GastoRepository::obtenerAnticipo($idSolicitud),
            'factura'            => GastoRepository::obtenerFactura($idSolicitud),
            'causacion'          => GastoRepository::obtenerCausacion($idSolicitud),
            'pago'               => GastoRepository::obtenerPago($idSolicitud),
            'compensacion'       => GastoRepository::obtenerCompensacion($idSolicitud),
            'cruce'              => GastoRepository::obtenerCruce($idSolicitud),
            'historial'          => FlujoRepository::historial($idSolicitud),
        );

        // Banderas informativas para Nodo.resolverSiguiente() (stepper, ayudas de UI) -- no son
        // la autoridad de ningún avance real, eso lo decide cada acción del backend por su cuenta.
        $aprobacionCotizacion = GastoRepository::obtenerAprobacionCotizacion($idSolicitud);
        $aprobacionSoporte    = GastoRepository::obtenerAprobacionSoporte($idSolicitud);
        $contexto['cotizacionAprobada'] = $aprobacionCotizacion && $aprobacionCotizacion['ESTADO'] === 'APROBADO';
        $contexto['anticipoAprobado']   = $contexto['anticipo'] && $contexto['anticipo']['ESTADO_APROBACION'] === 'APROBADO';
        $contexto['soporteAprobado']    = $aprobacionSoporte && $aprobacionSoporte['ESTADO'] === 'APROBADO';
        $contexto['yaCausado']          = $contexto['causacion'] !== null;

        if ($solicitud['TIPO_ANTICIPO'] === Config::TIPO_ANTICIPO_VIATICOS) {
            $contexto['viaticosSolicitud']    = ViaticosRepository::obtenerSolicitud($idSolicitud);
            $contexto['viaticosLegalizacion'] = ViaticosRepository::obtenerLegalizacion($idSolicitud);
        }

        Respuesta::ok($contexto);
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
        Auth::usuarioActual();
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
