<?php
/**
 * Catálogo único de dominio del módulo de Control de Gastos.
 *
 * Es la pieza "hexagonal" central: estados, roles y transiciones se
 * definen UNA sola vez aquí. Agregar, quitar o reordenar un estado del
 * flujo (por ejemplo, insertar un nuevo paso de revisión) solo implica
 * editar el arreglo de estados() de este archivo — los Handlers, el
 * EstadoMachine y la bandeja de trabajo se alimentan de esta tabla y no
 * necesitan tocarse.
 */
class Config
{
    // ---- Roles relevantes para el flujo (ver tabla de roles del sistema) ----
    const ROL_ADMINISTRADOR            = 1;
    const ROL_GERENCIA_ADMINISTRATIVA  = 73;
    const ROL_CONTABILIDAD             = 26;
    const ROL_GERENTE_CONTABILIDAD     = 69;
    const ROL_TESORERIA                = 4;

    // ---- Fondos de pago (paso 1.2 - registro de factura) ----
    const FONDO_ROMA      = 1;
    const FONDO_PROVEEDOR = 2;
    const NOTA_PROVEEDOR  = 3;

    // ---- Tipos de persona para datos de anticipo ----
    const TIPO_PERSONA_NATURAL  = 'NATURAL';
    const TIPO_PERSONA_JURIDICA = 'JURIDICA';

    // ---- Rutas físicas de almacenamiento de soportes (relativas a DOCUMENT_ROOT) ----
    const RUTA_COTIZACIONES = '/uploads/gastos/cotizaciones/';
    const RUTA_FACTURAS     = '/uploads/gastos/facturas/';

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

    public static function fondos()
    {
        return array(
            self::FONDO_ROMA      => 'ROMA',
            self::FONDO_PROVEEDOR => 'Fondo proveedor',
            self::NOTA_PROVEEDOR  => 'Nota proveedor',
        );
    }

    /**
     * Máquina de estados del flujo unificado (cotización -> [anticipo | factura]
     * -> causación -> pago -> [compensación] -> finalizado).
     *
     * 'etapa'             -> número usado para pintar el stepper en el frontend.
     * 'rolesResponsables' -> roles que pueden actuar mientras la solicitud está en este estado.
     * 'dueno'             -> true si en vez de un rol, quien actúa es el solicitante original.
     * 'siguientes'        -> estados válidos a los que se puede transicionar desde aquí.
     * 'final'             -> true si el flujo termina en este estado.
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
            ),
            'COTIZACION_APROBADA' => array(
                'nombre' => 'Aprobada - definir anticipo o factura',
                'etapa' => 3, 'color' => 'blue', 'dueno' => true,
                'rolesResponsables' => array(),
                'siguientes' => array('ANTICIPO_PENDIENTE_APROBACION', 'FACTURA_REGISTRADA'),
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
            ),
            'ANTICIPO_APROBADO' => array(
                'nombre' => 'Anticipo aprobado - pendiente causación',
                'etapa' => 5, 'color' => 'indigo',
                'rolesResponsables' => self::rolesContabilidad(),
                'siguientes' => array('CAUSADO'),
            ),
            'FACTURA_REGISTRADA' => array(
                'nombre' => 'Factura registrada - pendiente causación',
                'etapa' => 5, 'color' => 'indigo',
                'rolesResponsables' => self::rolesContabilidad(),
                'siguientes' => array('CAUSADO'),
            ),
            'CAUSADO' => array(
                'nombre' => 'Causado - pendiente pago',
                'etapa' => 6, 'color' => 'indigo',
                'rolesResponsables' => self::rolesTesoreria(),
                'siguientes' => array('PAGADO', 'FINALIZADO'),
            ),
            'PAGADO' => array(
                'nombre' => 'Pagado - pendiente compensación',
                'etapa' => 7, 'color' => 'violet',
                'rolesResponsables' => self::rolesContabilidad(),
                'siguientes' => array('COMPENSADO'),
            ),
            'COMPENSADO' => array(
                'nombre' => 'Compensado',
                'etapa' => 8, 'color' => 'emerald',
                'rolesResponsables' => array(),
                'siguientes' => array('FINALIZADO'),
            ),
            'FINALIZADO' => array(
                'nombre' => 'Finalizado',
                'etapa' => 9, 'color' => 'emerald', 'final' => true,
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
}
