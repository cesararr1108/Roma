<?php
/**
 * Catálogo único de dominio del módulo de Control de Gastos.
 *
 * Es la pieza "hexagonal" central: estados, roles, tipos de solicitud y
 * transiciones se definen UNA sola vez aquí. Agregar, quitar o reordenar
 * un paso del flujo solo implica editar el arreglo de estados() -- los
 * Handlers, el EstadoMachine y la bandeja de trabajo se alimentan de esta
 * tabla y no necesitan tocarse.
 *
 * Los valores de roles y fondos se alinearon contra el módulo "WorkFlow"
 * legacy (T_WORKFLOW) para mantener compatibilidad de negocio, aunque el
 * esquema de base de datos es nuevo y normalizado (ver sql/).
 */
class Config
{
    // ---- Roles relevantes para el flujo (idénticos al legacy WorkFlow) ----
    const ROL_ADMINISTRADOR            = 1;
    const ROL_GERENCIA_ADMINISTRATIVA  = 73;
    const ROL_CONTABILIDAD             = 26;
    const ROL_GERENTE_CONTABILIDAD     = 69;
    const ROL_TESORERIA                = 4;

    // ---- Tipo de solicitud (equivale a TIPO_GASTO del legacy) ----
    const TIPO_GASTO_COTIZACION = 1; // requiere cotizaciones (2 de 3 PDF) y aprobación previa
    const TIPO_GASTO_FACTURA    = 2; // factura directa, salta cotización/aprobación previa
    const TIPO_GASTO_ANTICIPO   = 3; // anticipo (viáticos o bienes/servicios) desde el inicio

    // ---- Sub-tipo de anticipo (aplica a TIPO_GASTO_ANTICIPO y a la rama de
    //      anticipo que se puede pedir tras aprobar una cotización) ----
    const TIPO_ANTICIPO_VIATICOS         = 'VIATICOS';
    const TIPO_ANTICIPO_BIENES_SERVICIOS = 'BIENES_SERVICIOS';

    // ---- Fondos de pago (orden y significado igual al legacy) ----
    const FONDO_PROVEEDOR = 1;
    const NOTA_PROVEEDOR  = 2;
    const FONDO_ROMA      = 3;

    // ---- Tipos de persona para datos de tercero/beneficiario ----
    const TIPO_PERSONA_NATURAL  = 'NATURAL';
    const TIPO_PERSONA_JURIDICA = 'JURIDICA';

    // ---- Organizaciones de venta (multi-tenant, igual al legacy) ----
    const ORG_ROMA                     = 2000;
    const ORG_COMERCIALIZADORA_MULTI   = 1000;

    // ---- Rutas físicas de almacenamiento de soportes (relativas a DOCUMENT_ROOT) ----
    const RUTA_COTIZACIONES = '/uploads/gastos/cotizaciones/';
    const RUTA_FACTURAS     = '/uploads/gastos/facturas/';
    const RUTA_ANTICIPOS    = '/uploads/gastos/anticipos/';

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

    /** Fondos que exigen diligenciar el número de fondo (todos menos ROMA). */
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

    /**
     * Tarifas de referencia de viáticos por nivel de cargo (T_ROLES_GASTOS_INFO.nivel).
     * Son puramente informativas -- se muestran al solicitante, no se usan
     * para calcular ni validar los valores que él mismo diligencia.
     */
    public static function tarifasViaticos()
    {
        return array(
            1 => array('nombre' => 'Gerentes de proceso', 'desayuno' => 10000, 'almuerzo' => 31000, 'cena' => 25000, 'total_dia' => 66000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
            2 => array('nombre' => 'Coordinadores',        'desayuno' => 10000, 'almuerzo' => 25000, 'cena' => 20000, 'total_dia' => 55000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
            3 => array('nombre' => 'Auxiliares',            'desayuno' => 10000, 'almuerzo' => 21000, 'cena' => 17000, 'total_dia' => 48000, 'hotel' => 110000, 'hotel_con_desayuno' => 120000),
        );
    }

    /**
     * Máquina de estados del flujo unificado. Las 3 rutas de entrada
     * (cotización, factura directa, anticipo) confluyen en los mismos
     * estados de causación / pago / compensación:
     *
     *   TIPO_GASTO_COTIZACION -> EN_APROBACION_COTIZACION -> COTIZACION_APROBADA
     *       -> (dueño decide) -> ANTICIPO_PENDIENTE_APROBACION | SOPORTE_PENDIENTE_APROBACION
     *   TIPO_GASTO_FACTURA    -> SOPORTE_PENDIENTE_APROBACION directo (sin cotización)
     *   TIPO_GASTO_ANTICIPO   -> ANTICIPO_PENDIENTE_APROBACION directo (sin cotización)
     *       -> ANTICIPO_APROBADO -> (dueño legaliza: factura o formulario de viáticos)
     *       -> SOPORTE_PENDIENTE_APROBACION
     *   SOPORTE_PENDIENTE_APROBACION -> SOPORTE_APROBADO -> CAUSADO -> PAGADO
     *       -> (si requiere anticipo) COMPENSADO -> FINALIZADO
     *       -> (si no)                FINALIZADO directo
     *
     * 'etapa'             -> número usado para pintar el stepper en el frontend.
     * 'rolesResponsables' -> roles que pueden actuar mientras la solicitud está en este estado.
     * 'dueno'             -> true si en vez de un rol, quien actúa es el solicitante original.
     * 'siguientes'        -> estados válidos a los que se puede transicionar desde aquí.
     * 'final'             -> true si el flujo termina en este estado.
     * 'reabreA'            -> (solo estados de rechazo) a qué estado vuelve si gerencia
     *                          administrativa reabre la solicitud.
     */
    public static function estados()
    {
        return array(
            'EN_APROBACION_COTIZACION' => array(
                'nombre' => 'En aprobación de cotización',
                'etapa' => 2, 'color' => 'amber',
                'rolesResponsables' => self::rolesGerenciaAdministrativa(),
                'siguientes' => array('COTIZACION_APROBADA', 'COTIZACION_RECHAZADA'),
            ),
            'COTIZACION_RECHAZADA' => array(
                'nombre' => 'Cotización rechazada',
                'etapa' => 2, 'color' => 'red', 'final' => true,
                'rolesResponsables' => array(),
                'siguientes' => array(),
                'reabreA' => 'EN_APROBACION_COTIZACION',
            ),
            'COTIZACION_APROBADA' => array(
                'nombre' => 'Aprobada - definir anticipo o factura',
                'etapa' => 3, 'color' => 'blue', 'dueno' => true,
                'rolesResponsables' => array(),
                'siguientes' => array('ANTICIPO_PENDIENTE_APROBACION', 'SOPORTE_PENDIENTE_APROBACION'),
            ),
            'ANTICIPO_PENDIENTE_APROBACION' => array(
                'nombre' => 'Anticipo pendiente de aprobación',
                'etapa' => 4, 'color' => 'amber',
                'rolesResponsables' => self::rolesGerenciaAdministrativa(),
                'siguientes' => array('ANTICIPO_APROBADO', 'ANTICIPO_RECHAZADO'),
            ),
            'ANTICIPO_RECHAZADO' => array(
                'nombre' => 'Anticipo rechazado',
                'etapa' => 4, 'color' => 'red', 'final' => true,
                'rolesResponsables' => array(),
                'siguientes' => array(),
                'reabreA' => 'ANTICIPO_PENDIENTE_APROBACION',
            ),
            'ANTICIPO_APROBADO' => array(
                'nombre' => 'Anticipo aprobado - pendiente legalizar',
                'etapa' => 5, 'color' => 'blue', 'dueno' => true,
                'rolesResponsables' => array(),
                'siguientes' => array('SOPORTE_PENDIENTE_APROBACION'),
            ),
            'SOPORTE_PENDIENTE_APROBACION' => array(
                'nombre' => 'Factura/legalización pendiente de aprobación',
                'etapa' => 6, 'color' => 'amber',
                'rolesResponsables' => self::rolesGerenciaAdministrativa(),
                'siguientes' => array('SOPORTE_APROBADO', 'SOPORTE_RECHAZADO'),
            ),
            'SOPORTE_RECHAZADO' => array(
                'nombre' => 'Factura/legalización rechazada',
                'etapa' => 6, 'color' => 'red', 'final' => true,
                'rolesResponsables' => array(),
                'siguientes' => array(),
                'reabreA' => 'SOPORTE_PENDIENTE_APROBACION',
            ),
            'SOPORTE_APROBADO' => array(
                'nombre' => 'Aprobada - pendiente causación',
                'etapa' => 7, 'color' => 'indigo',
                'rolesResponsables' => self::rolesContabilidad(),
                'siguientes' => array('CAUSADO'),
            ),
            'CAUSADO' => array(
                'nombre' => 'Causado - pendiente pago',
                'etapa' => 8, 'color' => 'indigo',
                'rolesResponsables' => self::rolesTesoreria(),
                'siguientes' => array('PAGADO'),
            ),
            'PAGADO' => array(
                'nombre' => 'Pagado - pendiente compensación',
                'etapa' => 9, 'color' => 'violet',
                'rolesResponsables' => self::rolesContabilidad(),
                'siguientes' => array('COMPENSADO', 'FINALIZADO'),
            ),
            'COMPENSADO' => array(
                'nombre' => 'Compensado',
                'etapa' => 10, 'color' => 'emerald',
                'rolesResponsables' => array(),
                'siguientes' => array('FINALIZADO'),
            ),
            'FINALIZADO' => array(
                'nombre' => 'Finalizado',
                'etapa' => 11, 'color' => 'emerald', 'final' => true,
                'rolesResponsables' => array(),
                'siguientes' => array(),
            ),
        );
    }

    public static function estado($codigo)
    {
        $estados = self::estados();
        return isset($estados[$codigo]) ? $estados[$codigo] : null;
    }

    /** Estado inicial de una solicitud según el tipo de gasto elegido. */
    public static function estadoInicial($tipoGasto)
    {
        switch ((int) $tipoGasto) {
            case self::TIPO_GASTO_FACTURA:
                return 'SOPORTE_PENDIENTE_APROBACION';
            case self::TIPO_GASTO_ANTICIPO:
                return 'ANTICIPO_PENDIENTE_APROBACION';
            case self::TIPO_GASTO_COTIZACION:
            default:
                return 'EN_APROBACION_COTIZACION';
        }
    }
}
