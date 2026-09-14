<?php
/**
 * Modelo del módulo de Control de Gastos.
 *
 * Sigue el mismo patrón usado en el resto del sistema: session_start() +
 * conectar() + un switch("op") que atiende cada caso. La diferencia es
 * que aquí cada "case" delega en un Handler independiente (gastos/Handlers)
 * en vez de tener toda la lógica dentro del switch: así cada paso del
 * flujo (solicitud por tipo, aprobación de cotización, anticipo,
 * factura/legalización, causación, pago, compensación) se puede tocar o
 * extender sin afectar a los demás.
 */
session_start();
require_once('funciones.php');
conectar();

require_once(__DIR__.'/gastos/Config.php');
require_once(__DIR__.'/gastos/Support/Respuesta.php');
require_once(__DIR__.'/gastos/Support/Auth.php');
require_once(__DIR__.'/gastos/Support/Db.php');
require_once(__DIR__.'/gastos/Support/EstadoMachine.php');
require_once(__DIR__.'/gastos/Support/ArchivoUploader.php');
require_once(__DIR__.'/gastos/Support/SolicitudComun.php');
require_once(__DIR__.'/gastos/Support/AnticipoDatos.php');
require_once(__DIR__.'/gastos/Support/FacturaDatos.php');
require_once(__DIR__.'/gastos/Support/ViaticosDatos.php');
require_once(__DIR__.'/gastos/FlujoRepository.php');
require_once(__DIR__.'/gastos/GastoRepository.php');
require_once(__DIR__.'/gastos/ViaticosRepository.php');
require_once(__DIR__.'/gastos/Handlers/ConsultaHandler.php');
require_once(__DIR__.'/gastos/Handlers/SolicitudCotizacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/SolicitudFacturaHandler.php');
require_once(__DIR__.'/gastos/Handlers/SolicitudAnticipoHandler.php');
require_once(__DIR__.'/gastos/Handlers/AprobacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/AnticipoHandler.php');
require_once(__DIR__.'/gastos/Handlers/FacturaHandler.php');
require_once(__DIR__.'/gastos/Handlers/ViaticosHandler.php');
require_once(__DIR__.'/gastos/Handlers/AprobacionSoporteHandler.php');
require_once(__DIR__.'/gastos/Handlers/CausacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/PagoHandler.php');
require_once(__DIR__.'/gastos/Handlers/CompensacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/ReaperturaHandler.php');
require_once(__DIR__.'/gastos/Handlers/ObservacionHandler.php');

$op = isset($_POST['op']) ? $_POST['op'] : '';

switch ($op) {

    // ---------- Catálogos / consultas ----------
    case 'cargar_datos':
        ConsultaHandler::cargarDatos();
        break;

    case 'listar_bandeja':
        ConsultaHandler::listarBandeja();
        break;

    case 'obtener_solicitud':
        ConsultaHandler::obtenerSolicitud();
        break;

    case 'buscar_tercero':
        ConsultaHandler::buscarTercero();
        break;

    case 'listar_conceptos':
        ConsultaHandler::listarConceptos();
        break;

    case 'crear_concepto':
        ConsultaHandler::crearConcepto();
        break;

    case 'listar_oficinas':
        ConsultaHandler::listarOficinas();
        break;

    case 'datos_usuario_actual':
        ConsultaHandler::datosUsuarioActual();
        break;

    // ---------- Paso 1: creación de solicitud (una rama por tipo de gasto) ----------
    case 'crear_solicitud_cotizacion':
        SolicitudCotizacionHandler::crear();
        break;

    case 'crear_solicitud_factura':
        SolicitudFacturaHandler::crear();
        break;

    case 'crear_solicitud_anticipo':
        SolicitudAnticipoHandler::crear();
        break;

    // ---------- Aprobación de cotización (gerencia administrativa) ----------
    case 'aprobar_cotizacion':
        AprobacionHandler::aprobar();
        break;

    case 'rechazar_cotizacion':
        AprobacionHandler::rechazar();
        break;

    // ---------- Rama anticipo (tras cotización aprobada) ----------
    case 'registrar_datos_anticipo':
        AnticipoHandler::registrarDatos();
        break;

    case 'aprobar_anticipo':
        AnticipoHandler::aprobar();
        break;

    case 'rechazar_anticipo':
        AnticipoHandler::rechazar();
        break;

    // ---------- Rama factura (sin anticipo, o legalización bienes/servicios) ----------
    case 'registrar_factura':
        FacturaHandler::registrar();
        break;

    // ---------- Rama legalización de viáticos ----------
    case 'registrar_legalizacion_viaticos':
        ViaticosHandler::registrarLegalizacion();
        break;

    // ---------- Aprobación de factura/legalización (gerencia administrativa) ----------
    case 'aprobar_soporte':
        AprobacionSoporteHandler::aprobar();
        break;

    case 'rechazar_soporte':
        AprobacionSoporteHandler::rechazar();
        break;

    // ---------- Causación (contabilidad) ----------
    case 'registrar_causacion':
        CausacionHandler::registrar();
        break;

    // ---------- Pago (tesorería) ----------
    case 'registrar_pago':
        PagoHandler::registrar();
        break;

    // ---------- Compensación (contabilidad, solo con anticipo) ----------
    case 'registrar_compensacion':
        CompensacionHandler::registrar();
        break;

    // ---------- Reapertura (gerencia administrativa) ----------
    case 'reabrir_solicitud':
        ReaperturaHandler::reabrir();
        break;

    // ---------- Observación sin cambio de estado ----------
    case 'agregar_observacion':
        ObservacionHandler::agregar();
        break;

    default:
        Respuesta::error('Operación no soportada.');
        break;
}
