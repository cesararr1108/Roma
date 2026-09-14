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
        echo json_encode($payload);
        exit;
    }
}
