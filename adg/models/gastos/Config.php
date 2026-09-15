<?php
/**
 * Catálogo de dominio que SÍ necesita vivir en el servidor: roles,
 * tipos de solicitud/anticipo, fondos y tarifas de referencia. No hay
 * aquí ningún catálogo de "estados" ni de transiciones -- ese grafo
 * vive en el navegador (controllers/core/FlowEngine.js +
 * controllers/flujos/flujoControlGastos.js). Cada acción de
 * models/gastos/Handlers/*.php es dueña de su propio nodo siguiente;
 * ninguna tiene ni necesita una tabla central de "a dónde va cada cosa".
 */
class Config
{
    // ---- Roles relevantes para el flujo ----
    const ROL_ADMINISTRADOR            = 1;
    const ROL_GERENCIA_ADMINISTRATIVA  = 73;
    const ROL_CONTABILIDAD             = 26;
    const ROL_GERENTE_CONTABILIDAD     = 69;
    const ROL_TESORERIA                = 4;

    // ---- Tipo de solicitud ----
    const TIPO_GASTO_COTIZACION = 1; // requiere cotizaciones (2 de 3 PDF) y aprobación previa
    const TIPO_GASTO_FACTURA    = 2; // factura directa, salta cotización/aprobación previa
    const TIPO_GASTO_ANTICIPO   = 3; // anticipo (viáticos o bienes/servicios) desde el inicio

    // ---- Sub-tipo de anticipo ----
    const TIPO_ANTICIPO_VIATICOS         = 'VIATICOS';
    const TIPO_ANTICIPO_BIENES_SERVICIOS = 'BIENES_SERVICIOS';

    // ---- Fondos de pago ----
    const FONDO_PROVEEDOR = 1;
    const NOTA_PROVEEDOR  = 2;
    const FONDO_ROMA      = 3;

    // ---- Tipos de persona para datos de tercero/beneficiario ----
    const TIPO_PERSONA_NATURAL  = 'NATURAL';
    const TIPO_PERSONA_JURIDICA = 'JURIDICA';

    // ---- Organizaciones de venta (multi-tenant) ----
    const ORG_ROMA                   = 2000;
    const ORG_COMERCIALIZADORA_MULTI = 1000;

    // ---- Rutas físicas de almacenamiento de soportes (relativas a DOCUMENT_ROOT) ----
    const RUTA_COTIZACIONES = '/uploads/gastos/cotizaciones/';
    const RUTA_FACTURAS     = '/uploads/gastos/facturas/';
    const RUTA_ANTICIPOS    = '/uploads/gastos/anticipos/';

    // ---- Nodos finales: no admiten más acciones, se excluyen de "pendientes" ----
    const NODOS_TERMINALES = array('rechazado_cotizacion', 'rechazado_anticipo', 'rechazado_soporte', 'finalizado');

    public static function rolesGerenciaAdministrativa()
    {
        return array(self::ROL_GERENCIA_ADMINISTRATIVA, self::ROL_ADMINISTRADOR);
    }

    public static function rolesContabilidad()
    {
        return array(self::ROL_CONTABILIDAD, self::ROL_GERENTE_CONTABILIDAD, self::ROL_ADMINISTRADOR);
    }

    public static function rolesTesoreria()
    {
        return array(self::ROL_TESORERIA, self::ROL_ADMINISTRADOR);
    }

    public static function tiposGasto()
    {
        return array(
            self::TIPO_GASTO_COTIZACION => 'Cotización',
            self::TIPO_GASTO_FACTURA    => 'Factura de gasto',
            self::TIPO_GASTO_ANTICIPO   => 'Anticipo',
        );
    }

    public static function tiposAnticipo()
    {
        return array(
            self::TIPO_ANTICIPO_VIATICOS         => 'Legalización de viáticos (viaje)',
            self::TIPO_ANTICIPO_BIENES_SERVICIOS  => 'Adquisición de bienes y servicios',
        );
    }

    public static function fondos()
    {
        return array(
            self::FONDO_PROVEEDOR => 'Fondo proveedor',
            self::NOTA_PROVEEDOR  => 'Nota proveedor',
            self::FONDO_ROMA      => 'ROMA',
        );
    }

    public static function fondosQueRequierenNumero()
    {
        return array(self::FONDO_PROVEEDOR, self::NOTA_PROVEEDOR);
    }

    public static function organizaciones()
    {
        return array(
            self::ORG_ROMA                   => 'ROMA',
            self::ORG_COMERCIALIZADORA_MULTI => 'Comercializadora Multidrogas',
        );
    }

    /** Tarifas de referencia de viáticos por nivel de cargo. Puramente informativas. */
    public static function tarifasViaticos()
    {
        return array(
            1 => array('nombre' => 'Gerentes de proceso', 'desayuno' => 10000, 'almuerzo' => 31000, 'cena' => 25000, 'total_dia' => 66000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
            2 => array('nombre' => 'Coordinadores',        'desayuno' => 10000, 'almuerzo' => 25000, 'cena' => 20000, 'total_dia' => 55000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
            3 => array('nombre' => 'Auxiliares',            'desayuno' => 10000, 'almuerzo' => 21000, 'cena' => 17000, 'total_dia' => 48000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
        );
    }
}
