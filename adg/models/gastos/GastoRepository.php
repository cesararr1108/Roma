<?php
/**
 * Repositorio de persistencia del dominio "Gastos". Cada Handler llama
 * únicamente a estos métodos -- nunca escribe SQL directamente -- de modo
 * que el esquema de base de datos se pueda ajustar en un solo archivo.
 *
 * Ajustar nombres/columnas de las tablas maestras compartidas
 * (T_USUARIOS, T_TERCEROS, T_DPTO, T_OFICINAS_VENTAS, T_CONCEPTOS_GASTOS,
 * T_ROLES_GASTOS_INFO) si difieren de las documentadas en el módulo
 * legacy "WorkFlow".
 */
class GastoRepository
{
    // ---------------------------------------------------------------
    // Solicitud
    // ---------------------------------------------------------------

    public static function crearSolicitud($datos)
    {
        $sql = "INSERT INTO GTOS_SOLICITUDES
                    (ORGANIZACION_VENTAS, OFICINA_VENTAS, TIPO_GASTO, TIPO_ANTICIPO, ID_CONCEPTO,
                     REQUIERE_SOPORTE, COMENTARIO_SOLICITA, ID_USUARIO_SOLICITA, ID_DPTO,
                     ESTADO, REQUIERE_ANTICIPO, FECHA_CREACION, FECHA_MODIFICACION)
                VALUES
                    (:organizacionVentas, :oficinaVentas, :tipoGasto, :tipoAnticipo, :idConcepto,
                     :requiereSoporte, :comentario, :idUsuario, :idDepartamento,
                     :estado, :requiereAnticipo, GETDATE(), GETDATE());
                SELECT SCOPE_IDENTITY() AS ID;";

        return Db::nuevoId($sql, array(
            'organizacionVentas' => $datos['organizacionVentas'],
            'oficinaVentas'      => $datos['oficinaVentas'],
            'tipoGasto'          => $datos['tipoGasto'],
            'tipoAnticipo'       => $datos['tipoAnticipo'],
            'idConcepto'         => $datos['idConcepto'],
            'requiereSoporte'    => $datos['requiereSoporte'] ? 1 : 0,
            'comentario'         => $datos['comentario'],
            'idUsuario'          => $datos['idUsuario'],
            'idDepartamento'     => $datos['idDepartamento'],
            'estado'             => $datos['estado'],
            'requiereAnticipo'   => $datos['requiereAnticipo'] ? 1 : 0,
        ));
    }

    public static function cambiarEstado($idSolicitud, $estadoNuevo)
    {
        Db::ejecutar(
            "UPDATE GTOS_SOLICITUDES SET ESTADO = :estado, FECHA_MODIFICACION = GETDATE() WHERE ID = :id",
            array('estado' => $estadoNuevo, 'id' => $idSolicitud)
        );
    }

    public static function obtenerSolicitud($idSolicitud)
    {
        $sql = "SELECT s.ID, s.ORGANIZACION_VENTAS, s.OFICINA_VENTAS, s.TIPO_GASTO, s.TIPO_ANTICIPO,
                       s.ID_CONCEPTO, s.REQUIERE_SOPORTE, s.COMENTARIO_SOLICITA,
                       s.ESTADO, s.REQUIERE_ANTICIPO, s.ID_USUARIO_SOLICITA, s.ID_DPTO,
                       s.FECHA_CREACION, s.FECHA_MODIFICACION,
                       u.NOMBRES + ' ' + u.APELLIDOS AS NOMBRE_SOLICITANTE,
                       c.CONCEPTO AS NOMBRE_CONCEPTO
                FROM GTOS_SOLICITUDES s
                LEFT JOIN T_USUARIOS u ON u.ID = s.ID_USUARIO_SOLICITA
                LEFT JOIN T_CONCEPTOS_GASTOS c ON c.ID = s.ID_CONCEPTO
                WHERE s.ID = :id";

        $filas = Db::query($sql, array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    /**
     * Bandeja de trabajo. 'mias' devuelve las solicitudes creadas por el
     * usuario; 'pendientes' devuelve las que están en un estado cuyo rol
     * responsable coincide con el rol del usuario actual (calculado a
     * partir de Config::estados(), sin listas de estados hardcodeadas).
     * Ambos filtros se acotan, además, a la organización de venta del
     * usuario (salvo administrador, que ve todas).
     */
    public static function listarBandeja($usuario, $filtro)
    {
        $condiciones = array();
        $params = array();

        if ((int) $usuario['rolId'] !== Config::ROL_ADMINISTRADOR && !empty($usuario['organizacionVentas'])) {
            $condiciones[] = 's.ORGANIZACION_VENTAS = :organizacionVentas';
            $params['organizacionVentas'] = $usuario['organizacionVentas'];
        }

        if ($filtro === 'mias') {
            $condiciones[] = 's.ID_USUARIO_SOLICITA = :idUsuario';
            $params['idUsuario'] = $usuario['id'];
        } else {
            $estadosPorRol = array();
            foreach (Config::estados() as $codigo => $definicion) {
                if (in_array($usuario['rolId'], $definicion['rolesResponsables'], true)) {
                    $estadosPorRol[] = $codigo;
                }
            }
            if (empty($estadosPorRol)) {
                return array();
            }

            $marcadores = array();
            foreach ($estadosPorRol as $indice => $codigo) {
                $clave = 'estado'.$indice;
                $marcadores[] = ':'.$clave;
                $params[$clave] = $codigo;
            }
            $condiciones[] = 's.ESTADO IN ('.implode(',', $marcadores).')';
        }

        $sql = "SELECT s.ID, s.TIPO_GASTO, s.TIPO_ANTICIPO, s.ESTADO, s.REQUIERE_ANTICIPO,
                       s.FECHA_CREACION, c.CONCEPTO AS NOMBRE_CONCEPTO,
                       u.NOMBRES + ' ' + u.APELLIDOS AS NOMBRE_SOLICITANTE
                FROM GTOS_SOLICITUDES s
                LEFT JOIN T_USUARIOS u ON u.ID = s.ID_USUARIO_SOLICITA
                LEFT JOIN T_CONCEPTOS_GASTOS c ON c.ID = s.ID_CONCEPTO";

        if (!empty($condiciones)) {
            $sql .= ' WHERE '.implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY s.FECHA_CREACION DESC';

        return Db::query($sql, $params);
    }

    // ---------------------------------------------------------------
    // Cotizaciones + aprobación (TIPO_GASTO_COTIZACION)
    // ---------------------------------------------------------------

    public static function guardarCotizacion($idSolicitud, $consecutivo, $archivo)
    {
        $sql = "INSERT INTO GTOS_COTIZACIONES
                    (ID_SOLICITUD, CONSECUTIVO, NOMBRE_ARCHIVO, RUTA_ARCHIVO, FECHA_CARGA)
                VALUES
                    (:idSolicitud, :consecutivo, :nombreArchivo, :rutaArchivo, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud'   => $idSolicitud,
            'consecutivo'   => $consecutivo,
            'nombreArchivo' => $archivo['nombreOriginal'],
            'rutaArchivo'   => $archivo['rutaRelativa'],
        ));
    }

    public static function cotizacionesDeSolicitud($idSolicitud)
    {
        $sql = "SELECT ID, CONSECUTIVO, NOMBRE_ARCHIVO, RUTA_ARCHIVO, ES_APROBADA, FECHA_CARGA
                FROM GTOS_COTIZACIONES
                WHERE ID_SOLICITUD = :idSolicitud
                ORDER BY CONSECUTIVO ASC";

        return Db::query($sql, array('idSolicitud' => $idSolicitud));
    }

    public static function guardarAprobacionCotizacion($idSolicitud, $idCotizacionAprobada, $estado, $motivo, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_APROBACION_COTIZACION
                    (ID_SOLICITUD, ID_COTIZACION_APROBADA, ESTADO, MOTIVO, ID_USUARIO, FECHA)
                VALUES
                    (:idSolicitud, :idCotizacionAprobada, :estado, :motivo, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud'          => $idSolicitud,
            'idCotizacionAprobada' => $idCotizacionAprobada,
            'estado'               => $estado,
            'motivo'               => $motivo,
            'idUsuario'            => $idUsuario,
        ));

        if ($idCotizacionAprobada) {
            Db::ejecutar(
                "UPDATE GTOS_COTIZACIONES SET ES_APROBADA = 1 WHERE ID = :id",
                array('id' => $idCotizacionAprobada)
            );
        }
    }

    // ---------------------------------------------------------------
    // Anticipo (beneficiario propio o tercero)
    // ---------------------------------------------------------------

    public static function guardarAnticipo($idSolicitud, $datos)
    {
        $existente = Db::query("SELECT ID FROM GTOS_ANTICIPOS WHERE ID_SOLICITUD = :idSolicitud", array('idSolicitud' => $idSolicitud));

        $params = array(
            'idSolicitud'         => $idSolicitud,
            'tipoPersona'         => $datos['tipoPersona'],
            'documentoIdentidad'  => $datos['documentoIdentidad'],
            'nitTercero'          => $datos['nitTercero'],
            'nombreTercero'       => $datos['nombreTercero'],
            'razonSocialTercero'  => $datos['razonSocialTercero'],
            'codigoSap'           => $datos['codigoSap'],
            'celular'             => $datos['celular'],
            'correo'              => $datos['correo'],
            'cargo'               => $datos['cargo'],
            'centroCostos'        => $datos['centroCostos'],
            'valorAnticipo'       => $datos['valorAnticipo'],
        );

        if (!empty($existente)) {
            $sql = "UPDATE GTOS_ANTICIPOS SET
                        TIPO_PERSONA = :tipoPersona, DOCUMENTO_IDENTIDAD = :documentoIdentidad,
                        NIT_TERCERO = :nitTercero, NOMBRE_TERCERO = :nombreTercero,
                        RAZON_SOCIAL_TERCERO = :razonSocialTercero, CODIGO_SAP = :codigoSap,
                        CELULAR = :celular, CORREO = :correo, CARGO = :cargo,
                        CENTRO_COSTOS = :centroCostos, VALOR_ANTICIPO = :valorAnticipo,
                        ESTADO_APROBACION = 'PENDIENTE', MOTIVO_RECHAZO = NULL
                    WHERE ID_SOLICITUD = :idSolicitud";
        } else {
            $sql = "INSERT INTO GTOS_ANTICIPOS
                        (ID_SOLICITUD, TIPO_PERSONA, DOCUMENTO_IDENTIDAD, NIT_TERCERO, NOMBRE_TERCERO,
                         RAZON_SOCIAL_TERCERO, CODIGO_SAP, CELULAR, CORREO, CARGO, CENTRO_COSTOS,
                         VALOR_ANTICIPO, ESTADO_APROBACION)
                    VALUES
                        (:idSolicitud, :tipoPersona, :documentoIdentidad, :nitTercero, :nombreTercero,
                         :razonSocialTercero, :codigoSap, :celular, :correo, :cargo, :centroCostos,
                         :valorAnticipo, 'PENDIENTE')";
        }

        Db::ejecutar($sql, $params);
    }

    public static function actualizarEstadoAnticipo($idSolicitud, $estado, $motivo, $idUsuario)
    {
        $sql = "UPDATE GTOS_ANTICIPOS SET
                    ESTADO_APROBACION = :estado, MOTIVO_RECHAZO = :motivo,
                    ID_USUARIO_APRUEBA = :idUsuario, FECHA_APROBACION = GETDATE()
                WHERE ID_SOLICITUD = :idSolicitud";

        Db::ejecutar($sql, array(
            'estado'      => $estado,
            'motivo'      => $motivo,
            'idUsuario'   => $idUsuario,
            'idSolicitud' => $idSolicitud,
        ));
    }

    public static function fijarTipoAnticipo($idSolicitud, $tipoAnticipo)
    {
        Db::ejecutar(
            "UPDATE GTOS_SOLICITUDES SET TIPO_ANTICIPO = :tipoAnticipo, REQUIERE_ANTICIPO = 1 WHERE ID = :id",
            array('tipoAnticipo' => $tipoAnticipo, 'id' => $idSolicitud)
        );
    }

    public static function obtenerAnticipo($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_ANTICIPOS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    // ---------------------------------------------------------------
    // Factura / documento soporte (factura directa, sin-anticipo, o
    // legalización de anticipo de bienes y servicios)
    // ---------------------------------------------------------------

    public static function guardarFactura($idSolicitud, $datos)
    {
        $sql = "INSERT INTO GTOS_FACTURAS
                    (ID_SOLICITUD, NIT_TERCERO, CODIGO_SAP, NOMBRE_TERCERO, NUMERO_PRELIMINAR_SAP,
                     NUMERO_FACTURA, FECHA_FACTURA, NOMBRE_ARCHIVO, RUTA_ARCHIVO, ID_FONDO,
                     NUMERO_FONDO, SUBTOTAL, IVA, TOTAL)
                VALUES
                    (:idSolicitud, :nit, :codigoSap, :nombreTercero, :numeroPreliminar,
                     :numeroFactura, :fechaFactura, :nombreArchivo, :rutaArchivo, :idFondo,
                     :numeroFondo, :subtotal, :iva, :total)";

        Db::ejecutar($sql, array(
            'idSolicitud'      => $idSolicitud,
            'nit'              => $datos['nit'],
            'codigoSap'        => $datos['codigoSap'],
            'nombreTercero'    => $datos['nombreTercero'],
            'numeroPreliminar' => $datos['numeroPreliminar'],
            'numeroFactura'    => $datos['numeroFactura'],
            'fechaFactura'     => $datos['fechaFactura'],
            'nombreArchivo'    => $datos['archivo']['nombreOriginal'],
            'rutaArchivo'      => $datos['archivo']['rutaRelativa'],
            'idFondo'          => $datos['idFondo'],
            'numeroFondo'      => $datos['numeroFondo'],
            'subtotal'         => $datos['subtotal'],
            'iva'              => $datos['iva'],
            'total'            => $datos['total'],
        ));
    }

    public static function obtenerFactura($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_FACTURAS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function guardarAprobacionSoporte($idSolicitud, $estado, $motivo, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_APROBACION_SOPORTE (ID_SOLICITUD, ESTADO, MOTIVO, ID_USUARIO, FECHA)
                VALUES (:idSolicitud, :estado, :motivo, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud' => $idSolicitud,
            'estado'      => $estado,
            'motivo'      => $motivo,
            'idUsuario'   => $idUsuario,
        ));
    }

    // ---------------------------------------------------------------
    // Causación, pago, compensación
    // ---------------------------------------------------------------

    public static function guardarCausacion($idSolicitud, $numero, $nota, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_CAUSACION (ID_SOLICITUD, NUMERO_CAUSACION, NOTA, ID_USUARIO, FECHA)
                VALUES (:idSolicitud, :numero, :nota, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud' => $idSolicitud,
            'numero'      => $numero,
            'nota'        => $nota,
            'idUsuario'   => $idUsuario,
        ));
    }

    public static function obtenerCausacion($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_CAUSACION WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function guardarPago($idSolicitud, $numeroComprobante, $archivo, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_PAGOS (ID_SOLICITUD, NUMERO_COMPROBANTE_PAGO, NOMBRE_ARCHIVO, RUTA_ARCHIVO, ID_USUARIO, FECHA)
                VALUES (:idSolicitud, :numero, :nombreArchivo, :rutaArchivo, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud'   => $idSolicitud,
            'numero'        => $numeroComprobante,
            'nombreArchivo' => $archivo ? $archivo['nombreOriginal'] : null,
            'rutaArchivo'   => $archivo ? $archivo['rutaRelativa'] : null,
            'idUsuario'     => $idUsuario,
        ));
    }

    public static function obtenerPago($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_PAGOS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function guardarCompensacion($idSolicitud, $numeroCompensacion, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_COMPENSACION (ID_SOLICITUD, NUMERO_COMPENSACION, ID_USUARIO, FECHA)
                VALUES (:idSolicitud, :numero, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud' => $idSolicitud,
            'numero'      => $numeroCompensacion,
            'idUsuario'   => $idUsuario,
        ));
    }

    public static function obtenerCompensacion($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_COMPENSACION WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    // ---------------------------------------------------------------
    // Catálogos y datos de apoyo
    // ---------------------------------------------------------------

    public static function buscarTercero($nit)
    {
        $sql = "SELECT NIT, CODIGO_SAP, RAZON_COMERCIAL, NOMBRES FROM T_TERCEROS WHERE NIT = :nit";
        $filas = Db::query($sql, array('nit' => $nit));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function listarConceptos()
    {
        return Db::query("SELECT ID, CONCEPTO FROM T_CONCEPTOS_GASTOS ORDER BY CONCEPTO ASC");
    }

    public static function crearConcepto($concepto)
    {
        return Db::nuevoId(
            "INSERT INTO T_CONCEPTOS_GASTOS (CONCEPTO) VALUES (:concepto); SELECT SCOPE_IDENTITY() AS ID;",
            array('concepto' => $concepto)
        );
    }

    public static function listarOficinas($organizacionVentas)
    {
        $sql = "SELECT OFICINA_VENTAS, DESCRIPCION FROM T_OFICINAS_VENTAS
                WHERE ORGANIZACION_VENTAS = :organizacionVentas ORDER BY DESCRIPCION ASC";
        return Db::query($sql, array('organizacionVentas' => $organizacionVentas));
    }

    /** Datos fiscales/personales del usuario logueado, para autocompletar "a mi nombre". */
    public static function datosUsuarioActual($idUsuario)
    {
        $sql = "SELECT u.ID, u.NOMBRES, u.APELLIDOS, u.IDENTIFICACION, u.CELULAR, u.EMAIL, u.CODIGO_SAP,
                       t.RAZON_COMERCIAL, t.NIT,
                       r.TITULO AS CARGO,
                       rgi.nivel AS NIVEL_VIATICOS
                FROM T_USUARIOS u
                LEFT JOIN T_TERCEROS t ON t.CODIGO_SAP = u.CODIGO_SAP
                LEFT JOIN T_ROLES r ON r.ID = u.ROLES_ID
                LEFT JOIN T_ROLES_GASTOS_INFO rgi ON rgi.ROL = u.ROLES_ID
                WHERE u.ID = :idUsuario";

        $filas = Db::query($sql, array('idUsuario' => $idUsuario));
        return isset($filas[0]) ? $filas[0] : null;
    }
}
