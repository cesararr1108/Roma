<?php
/**
 * Envelope de respuesta JSON estandarizado para todos los "case" del
 * modelo. Mantiene un contrato fijo { exito, mensaje, datos } que el
 * frontend (apiClient.js / enviarPeticion) siempre puede confiar.
 */
class Respuesta
{
    public static function ok($datos = array(), $mensaje = '')
    {
        self::enviar(array(
            'exito'   => true,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ));
    }

    public static function error($mensaje, $codigoHttp = 400)
    {
        http_response_code($codigoHttp);
        self::enviar(array(
            'exito'   => false,
            'mensaje' => $mensaje,
            'datos'   => null,
        ));
    }

    private static function enviar($payload)
    {
        header('Content-Type: application/json; charset=utf-8');

        // JSON_UNESCAPED_UNICODE existe desde PHP 5.4 (disponible siempre en este
        // proyecto). JSON_INVALID_UTF8_SUBSTITUTE solo existe desde PHP 7.2 -- se
        // agrega solo si la constante existe, para no romper en el servidor real
        // (PHP 5.4). Cuando está disponible, evita que un texto con codificación
        // inválida (fuera de Db::query, que ya corrige esto) deje la respuesta
        // entera vacía: json_encode() reemplaza ese carácter en vez de fallar.
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        echo json_encode($payload, $flags);
        exit;
    }
}
