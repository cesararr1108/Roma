<?php
/**
 * Puerto de acceso a datos (adaptador de salida) para el módulo de
 * Control de Gastos. Reutiliza la conexión que ya abre conectar()
 * (funciones.php) sin importar si expone un recurso sqlsrv/mssql o una
 * instancia PDO_SQLSRV: solo hay que ajustar getConexion() si el nombre
 * real de la variable global difiere.
 *
 * Todo el resto del módulo (Repositories) solo conoce Db::query() /
 * Db::ejecutar() / Db::nuevoId(), nunca el driver concreto -- así el
 * driver se puede cambiar sin tocar la lógica de negocio.
 */
class Db
{
    private static function getConexion()
    {
        global $conexion, $conn, $link;

        if (isset($conexion)) return $conexion;
        if (isset($conn))     return $conn;
        if (isset($link))     return $link;

        throw new Exception('No se encontró una conexión activa. Verifique que conectar() (funciones.php) exponga $conexion.');
    }

    private static function esPDO($cn)
    {
        return ($cn instanceof PDO);
    }

    /** Devuelve un arreglo asociativo por cada fila. */
    public static function query($sql, $params = array())
    {
        $cn = self::getConexion();

        if (self::esPDO($cn)) {
            $stmt = $cn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $preparado = self::interpolarSqlsrv($sql, $params);
        $stmt = sqlsrv_query($cn, $preparado['sql'], $preparado['valores']);
        if ($stmt === false) {
            throw new Exception('Error de consulta: '.print_r(sqlsrv_errors(), true));
        }

        $filas = array();
        while ($fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filas[] = $fila;
        }
        return $filas;
    }

    /** INSERT/UPDATE/DELETE. Devuelve el número de filas afectadas. */
    public static function ejecutar($sql, $params = array())
    {
        $cn = self::getConexion();

        if (self::esPDO($cn)) {
            $stmt = $cn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }

        $preparado = self::interpolarSqlsrv($sql, $params);
        $stmt = sqlsrv_query($cn, $preparado['sql'], $preparado['valores']);
        if ($stmt === false) {
            throw new Exception('Error de ejecución: '.print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_rows_affected($stmt);
    }

    /** $sql debe terminar en "...; SELECT SCOPE_IDENTITY() AS ID;" */
    public static function nuevoId($sql, $params = array())
    {
        $filas = self::query($sql, $params);
        return isset($filas[0]['ID']) ? (int) $filas[0]['ID'] : 0;
    }

    /** Traduce parámetros nombrados (:clave) a los "?" posicionales que usa sqlsrv. */
    private static function interpolarSqlsrv($sql, $params)
    {
        $valores = array();
        $sqlFinal = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function ($coincidencia) use ($params, &$valores) {
            $valores[] = isset($params[$coincidencia[1]]) ? $params[$coincidencia[1]] : null;
            return '?';
        }, $sql);

        return array('sql' => $sqlFinal, 'valores' => $valores);
    }
}
