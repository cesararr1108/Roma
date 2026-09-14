<?php
/**
 * Adaptador de acceso a datos sobre la conexión que ya expone conectar()
 * (funciones.php), basada en la extensión mssql_* de PHP 5.4.
 *
 * conectar() no deja una variable global: retorna el recurso de conexión.
 * Se cachea aquí (una sola vez por request) para no reabrir conexión en
 * cada consulta; como mssql_connect reutiliza el link cuando se llama de
 * nuevo con los mismos parámetros, esto es seguro incluso si conectar()
 * también se invoca en otro punto del script.
 *
 * La extensión mssql_* NO soporta sentencias preparadas, así que los
 * parámetros (:clave) se interpolan ya escapados -- Repositories y
 * Handlers nunca concatenan valores directamente en el SQL, por lo que
 * la protección contra inyección queda centralizada acá.
 *
 * Si el día de mañana se cambia de driver (sqlsrv, PDO_SQLSRV), solo este
 * archivo se toca: el resto del módulo únicamente conoce Db::query() /
 * Db::ejecutar() / Db::nuevoId().
 */
class Db
{
    private static function conexion()
    {
        static $link = null;
        if ($link === null) {
            $link = conectar();
        }
        return $link;
    }

    /** Devuelve un arreglo asociativo por cada fila. */
    public static function query($sql, $params = array())
    {
        $sqlFinal = self::interpolar($sql, $params);
        $resultado = mssql_query($sqlFinal, self::conexion());

        if ($resultado === false) {
            throw new Exception('Error de consulta SQL.');
        }

        $filas = array();
        while ($fila = mssql_fetch_assoc($resultado)) {
            $filas[] = $fila;
        }
        return $filas;
    }

    /** INSERT/UPDATE/DELETE. */
    public static function ejecutar($sql, $params = array())
    {
        $sqlFinal = self::interpolar($sql, $params);
        $resultado = mssql_query($sqlFinal, self::conexion());

        if ($resultado === false) {
            throw new Exception('Error al ejecutar la sentencia SQL.');
        }
        return true;
    }

    /** $sql debe terminar en "...; SELECT SCOPE_IDENTITY() AS ID;" */
    public static function nuevoId($sql, $params = array())
    {
        $filas = self::query($sql, $params);
        return isset($filas[0]['ID']) ? (int) $filas[0]['ID'] : 0;
    }

    /** Sustituye :clave por el valor ya escapado, tomado de $params. */
    private static function interpolar($sql, $params)
    {
        return preg_replace_callback('/:([a-zA-Z0-9_]+)/', function ($coincidencia) use ($params) {
            if (!array_key_exists($coincidencia[1], $params)) {
                return 'NULL';
            }
            return self::escapar($params[$coincidencia[1]]);
        }, $sql);
    }

    /** Escapa un valor para incrustarlo de forma segura en T-SQL. */
    private static function escapar($valor)
    {
        if ($valor === null) {
            return 'NULL';
        }
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }
        // Cadena: se duplican las comillas simples (estándar de escape en T-SQL).
        return "'".str_replace("'", "''", (string) $valor)."'";
    }
}
