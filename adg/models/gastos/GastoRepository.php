<?php
/**
 * Repositorio de persistencia del dominio "Gastos". Cada Handler llama
 * únicamente a estos métodos -- nunca escribe SQL directamente -- de modo
 * que el esquema de base de datos se pueda ajustar en un solo archivo.
 *
 * Ajustar el nombre de la tabla de usuarios (T_USUARIOS) y de terceros
 * (T_TERCEROS) a los reales del proyecto si difieren.
 */
class GastoRepository
{
    // ---------------------------------------------------------------
    // Paso 1: solicitud + cotizaciones
    // ---------------------------------------------------------------

    public static function crearSolicitud($datos)
    {
        $sql = "INSERT INTO GTOS_SOLICITUDES
                    (DESCRIPCION, JUSTIFICACION, VALOR_ESTIMADO, ID_USUARIO_SOLICITA, ID_DPTO,
                     ESTADO, REQUIERE_ANTICIPO, FECHA_CREACION, FECHA_MODIFICACION)
                VALUES
                    (:descripcion, :justificacion, :valorEstimado, :idUsuario, :idDepartamento,
                     :estado, 0, GETDATE(), GETDATE());
                SELECT SCOPE_IDENTITY() AS ID;";

        return Db::nuevoId($sql, array(
            'descripcion'    => $datos['descripcion'],
            'justificacion'  => $datos['justificacion'],
            'valorEstimado'  => $datos['valorEstimado'],
            'idUsuario'      => $datos['idUsuario'],
            'idDepartamento' => $datos['idDepartamento'],
            'estado'         => $datos['estado'],
        ));
    }

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

    // ---------------------------------------------------------------
    // Paso 1.1: aprobación de cotización
    // ---------------------------------------------------------------

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
    // Paso 1.2 (rama anticipo)
    // ---------------------------------------------------------------

    public static function guardarAnticipo($idSolicitud, $datos)
    {
        $existente = Db::query("SELECT ID FROM GTOS_ANTICIPOS WHERE ID_SOLICITUD = :idSolicitud", array('idSolicitud' => $idSolicitud));

        if (!empty($existente)) {
            $sql = "UPDATE GTOS_ANTICIPOS SET
                        BENEFICIARIO = :beneficiario, TIPO_PERSONA = :tipoPersona, NIT = :nit,
                        NOMBRES = :nombres, TELEFONO = :telefono, EMAIL = :email,
                        ESTADO_APROBACION = 'PENDIENTE', MOTIVO_RECHAZO = NULL
                    WHERE ID_SOLICITUD = :idSolicitud";
        } else {
            $sql = "INSERT INTO GTOS_ANTICIPOS
                        (ID_SOLICITUD, BENEFICIARIO, TIPO_PERSONA, NIT, NOMBRES, TELEFONO, EMAIL, ESTADO_APROBACION)
                    VALUES
                        (:idSolicitud, :beneficiario, :tipoPersona, :nit, :nombres, :telefono, :email, 'PENDIENTE')";
        }

        Db::ejecutar($sql, array(
            'idSolicitud'  => $idSolicitud,
            'beneficiario' => $datos['beneficiario'],
            'tipoPersona'  => $datos['tipoPersona'],
            'nit'          => $datos['nit'],
            'nombres'      => $datos['nombres'],
            'telefono'     => $datos['telefono'],
            'email'        => $datos['email'],
        ));

        Db::ejecutar("UPDATE GTOS_SOLICITUDES SET REQUIERE_ANTICIPO = 1 WHERE ID = :id", array('id' => $idSolicitud));
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

    // ---------------------------------------------------------------
    // Paso 1.2 (rama factura)
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

    // ---------------------------------------------------------------
    // Paso 1.3, 1.4, 1.5: causación, pago, compensación
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

    public static function guardarPago($idSolicitud, $numeroComprobante, $idUsuario)
    {
        $sql = "INSERT INTO GTOS_PAGOS (ID_SOLICITUD, NUMERO_COMPROBANTE_PAGO, ID_USUARIO, FECHA)
                VALUES (:idSolicitud, :numero, :idUsuario, GETDATE())";

        Db::ejecutar($sql, array(
            'idSolicitud' => $idSolicitud,
            'numero'      => $numeroComprobante,
            'idUsuario'   => $idUsuario,
        ));
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

    // ---------------------------------------------------------------
    // Estado / consultas
    // ---------------------------------------------------------------

    public static function cambiarEstado($idSolicitud, $estadoNuevo)
    {
        Db::ejecutar(
            "UPDATE GTOS_SOLICITUDES SET ESTADO = :estado, FECHA_MODIFICACION = GETDATE() WHERE ID = :id",
            array('estado' => $estadoNuevo, 'id' => $idSolicitud)
        );
    }

    public static function obtenerSolicitud($idSolicitud)
    {
        $sql = "SELECT s.ID, s.DESCRIPCION, s.JUSTIFICACION, s.VALOR_ESTIMADO,
                       s.ESTADO, s.REQUIERE_ANTICIPO, s.ID_USUARIO_SOLICITA, s.ID_DPTO,
                       s.FECHA_CREACION, s.FECHA_MODIFICACION,
                       u.NOMBRES + ' ' + u.APELLIDOS AS NOMBRE_SOLICITANTE
                FROM GTOS_SOLICITUDES s
                LEFT JOIN T_USUARIOS u ON u.ID = s.ID_USUARIO_SOLICITA
                WHERE s.ID = :id";

        $filas = Db::query($sql, array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function obtenerAnticipo($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_ANTICIPOS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function obtenerFactura($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_FACTURAS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function obtenerCausacion($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_CAUSACION WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function obtenerPago($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_PAGOS WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function obtenerCompensacion($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_COMPENSACION WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    public static function buscarTercero($nit)
    {
        $sql = "SELECT NIT, CODIGO_SAP, NOMBRES FROM T_TERCEROS WHERE NIT = :nit";
        $filas = Db::query($sql, array('nit' => $nit));
        return isset($filas[0]) ? $filas[0] : null;
    }

    /**
     * Bandeja de trabajo. 'mias' devuelve las solicitudes creadas por el
     * usuario; 'pendientes' devuelve las que están en un estado cuyo rol
     * responsable coincide con el rol del usuario actual (calculado a
     * partir de Config::estados(), sin listas de estados hardcodeadas).
     */
    public static function listarBandeja($usuario, $filtro)
    {
        $condiciones = array();
        $params = array();

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

        $sql = "SELECT s.ID, s.DESCRIPCION, s.VALOR_ESTIMADO, s.ESTADO,
                       s.REQUIERE_ANTICIPO, s.FECHA_CREACION,
                       u.NOMBRES + ' ' + u.APELLIDOS AS NOMBRE_SOLICITANTE
                FROM GTOS_SOLICITUDES s
                LEFT JOIN T_USUARIOS u ON u.ID = s.ID_USUARIO_SOLICITA";

        if (!empty($condiciones)) {
            $sql .= ' WHERE '.implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY s.FECHA_CREACION DESC';

        return Db::query($sql, $params);
    }
}
