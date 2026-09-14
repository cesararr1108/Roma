<?php
/**
 * Guarda de transiciones de estado. Se apoya únicamente en la tabla
 * Config::estados() -- así el flujo completo (incluyendo qué transiciones
 * son válidas) vive en un solo lugar y cada Handler queda desacoplado de
 * los demás.
 */
class EstadoMachine
{
    public static function validarTransicion($estadoActual, $estadoNuevo)
    {
        $definicion = Config::estado($estadoActual);
        if ($definicion === null) {
            throw new Exception('Estado actual desconocido: '.$estadoActual);
        }
        if (!in_array($estadoNuevo, $definicion['siguientes'], true)) {
            throw new Exception('No es posible pasar de "'.$estadoActual.'" a "'.$estadoNuevo.'".');
        }
        return true;
    }

    public static function esResponsable($estadoCodigo, $rolId)
    {
        $definicion = Config::estado($estadoCodigo);
        if ($definicion === null) {
            return false;
        }
        return in_array((int) $rolId, $definicion['rolesResponsables'], true);
    }
}
