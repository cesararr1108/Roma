<?php
/**
 * Modelo del módulo de Control de Gastos.
 *
 * Sigue el mismo patrón del resto del sistema: session_start() +
 * conectar() + un switch("op") que atiende cada caso. Cada "case"
 * delega en una función que SOLO guarda datos (insert/update) o sube
 * un archivo -- el grafo del flujo (qué nodo sigue a cuál, quién puede
 * ver o actuar en cada uno) vive en el navegador
 * (controllers/core/FlowEngine.js + controllers/flujos/*.js). Este
 * archivo es apenas el enrutador HTTP; no valida secuencias ni conoce
 * el flujo completo.
 */
session_start();
require_once('funciones.php');
conectar();

require_once(__DIR__.'/gastos/Config.php');
require_once(__DIR__.'/gastos/Support/Respuesta.php');
require_once(__DIR__.'/gastos/Support/Auth.php');
require_once(__DIR__.'/gastos/Support/Db.php');
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
require_once(__DIR__.'/gastos/Handlers/ElegirRamaHandler.php');
require_once(__DIR__.'/gastos/Handlers/AnticipoHandler.php');
require_once(__DIR__.'/gastos/Handlers/FacturaHandler.php');
require_once(__DIR__.'/gastos/Handlers/ViaticosHandler.php');
require_once(__DIR__.'/gastos/Handlers/AprobacionSoporteHandler.php');
require_once(__DIR__.'/gastos/Handlers/CausacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/PagoHandler.php');
require_once(__DIR__.'/gastos/Handlers/CompensacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/CruceHandler.php');
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

    case 'obtener_contexto':
        ConsultaHandler::obtenerContexto();
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

    // ---------- Creación de solicitud (una acción por tipo de gasto) ----------
    case 'crear_solicitud_cotizacion':
        SolicitudCotizacionHandler::crear();
        break;

    case 'crear_solicitud_factura':
        SolicitudFacturaHandler::crear();
        break;

    case 'crear_solicitud_anticipo':
        SolicitudAnticipoHandler::crear();
        break;

    // ---------- Nodo: aprobacion_cotizacion ----------
    case 'aprobar_cotizacion':
        AprobacionHandler::aprobar();
        break;

    case 'rechazar_cotizacion':
        AprobacionHandler::rechazar();
        break;

    // ---------- Nodo: decision_anticipo_factura ----------
    case 'elegir_rama_anticipo':
        ElegirRamaHandler::elegirAnticipo();
        break;

    case 'elegir_rama_factura':
        ElegirRamaHandler::elegirFactura();
        break;

    // ---------- Nodo: anticipo_datos / anticipo_aprobacion ----------
    case 'registrar_datos_anticipo':
        AnticipoHandler::registrarDatos();
        break;

    case 'aprobar_anticipo':
        AnticipoHandler::aprobar();
        break;

    case 'rechazar_anticipo':
        AnticipoHandler::rechazar();
        break;

    // ---------- Nodo: factura_datos / legalizacion (rama bienes y servicios) ----------
    case 'registrar_factura':
        FacturaHandler::registrar();
        break;

    // ---------- Nodo: legalizacion (rama viáticos) ----------
    case 'registrar_legalizacion_viaticos':
        ViaticosHandler::registrarLegalizacion();
        break;

    // ---------- Nodo: aprobacion_soporte ----------
    case 'aprobar_soporte':
        AprobacionSoporteHandler::aprobar();
        break;

    case 'rechazar_soporte':
        AprobacionSoporteHandler::rechazar();
        break;

    // ---------- Nodo: causacion ----------
    case 'registrar_causacion':
        CausacionHandler::registrar();
        break;

    // ---------- Nodo: pago ----------
    case 'registrar_pago':
        PagoHandler::registrar();
        break;

    // ---------- Nodo: compensacion (rama anticipo) ----------
    case 'registrar_compensacion':
        CompensacionHandler::registrar();
        break;

    // ---------- Nodo: cruzado (rama factura) ----------
    case 'registrar_cruce':
        CruceHandler::registrar();
        break;

    // ---------- Reapertura de nodos rechazados ----------
    case 'reabrir_solicitud':
        ReaperturaHandler::reabrir();
        break;

    // ---------- Observación sin cambio de nodo ----------
    case 'agregar_observacion':
        ObservacionHandler::agregar();
        break;

    default:
        Respuesta::error('Operación no soportada.');
        break;
}
