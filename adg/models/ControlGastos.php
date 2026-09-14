<?php
/**
 * Modelo del módulo de Control de Gastos.
 *
 * Sigue el mismo patrón usado en el resto del sistema: session_start() +
 * conectar() + un switch("op") que atiende cada caso. La diferencia es
 * que aquí cada "case" delega en un Handler independiente (gastos/Handlers)
 * en vez de tener toda la lógica dentro del switch: así cada paso del
 * flujo (solicitud, aprobación, anticipo, factura, causación, pago,
 * compensación) se puede tocar o extender sin afectar a los demás.
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
require_once(__DIR__.'/gastos/FlujoRepository.php');
require_once(__DIR__.'/gastos/GastoRepository.php');
require_once(__DIR__.'/gastos/Handlers/ConsultaHandler.php');
require_once(__DIR__.'/gastos/Handlers/SolicitudHandler.php');
require_once(__DIR__.'/gastos/Handlers/AprobacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/AnticipoHandler.php');
require_once(__DIR__.'/gastos/Handlers/FacturaHandler.php');
require_once(__DIR__.'/gastos/Handlers/CausacionHandler.php');
require_once(__DIR__.'/gastos/Handlers/PagoHandler.php');
require_once(__DIR__.'/gastos/Handlers/CompensacionHandler.php');

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

    // ---------- Paso 1: solicitud + cotizaciones (cualquier rol) ----------
    case 'crear_solicitud':
        SolicitudHandler::crear();
        break;

    // ---------- Paso 1.1: aprobación de cotización (gerencia administrativa) ----------
    case 'aprobar_cotizacion':
        AprobacionHandler::aprobar();
        break;

    case 'rechazar_cotizacion':
        AprobacionHandler::rechazar();
        break;

    // ---------- Paso 1.2 (rama anticipo) ----------
    case 'registrar_datos_anticipo':
        AnticipoHandler::registrarDatos();
        break;

    case 'aprobar_anticipo':
        AnticipoHandler::aprobar();
        break;

    case 'rechazar_anticipo':
        AnticipoHandler::rechazar();
        break;

    // ---------- Paso 1.2 (rama factura) ----------
    case 'registrar_factura':
        FacturaHandler::registrar();
        break;

    // ---------- Paso 1.3: causación (contabilidad) ----------
    case 'registrar_causacion':
        CausacionHandler::registrar();
        break;

    // ---------- Paso 1.4: pago (tesorería) ----------
    case 'registrar_pago':
        PagoHandler::registrar();
        break;

    // ---------- Paso 1.5: compensación (contabilidad, solo con anticipo) ----------
    case 'registrar_compensacion':
        CompensacionHandler::registrar();
        break;

    default:
        Respuesta::error('Operación no soportada.');
        break;
}
