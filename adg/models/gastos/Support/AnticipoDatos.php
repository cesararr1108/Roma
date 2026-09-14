<?php
/**
 * Lectura/validación de los datos del beneficiario de un anticipo (propio
 * o tercero). Se comparte entre la creación de una solicitud tipo
 * TIPO_GASTO_ANTICIPO y la rama "requiere anticipo" que se desprende de
 * una cotización aprobada -- ambos casos piden exactamente los mismos
 * campos.
 */
class AnticipoDatos
{
    public static function leerYValidar()
    {
        $tipoPersona        = isset($_POST['tipoPersona']) ? $_POST['tipoPersona'] : '';
        $documentoIdentidad = isset($_POST['documentoIdentidad']) ? trim($_POST['documentoIdentidad']) : '';
        $nitTercero          = isset($_POST['nitTercero']) ? trim($_POST['nitTercero']) : '';
        $nombreTercero       = isset($_POST['nombreTercero']) ? trim($_POST['nombreTercero']) : '';
        $razonSocialTercero  = isset($_POST['razonSocialTercero']) ? trim($_POST['razonSocialTercero']) : '';
        $codigoSap           = isset($_POST['codigoSap']) ? trim($_POST['codigoSap']) : '';
        $celular             = isset($_POST['celular']) ? trim($_POST['celular']) : '';
        $correo              = isset($_POST['correo']) ? trim($_POST['correo']) : '';
        $cargo               = isset($_POST['cargo']) ? trim($_POST['cargo']) : '';
        $centroCostos        = isset($_POST['centroCostos']) ? trim($_POST['centroCostos']) : '';
        $valorAnticipo       = isset($_POST['valorAnticipo']) ? (float) $_POST['valorAnticipo'] : 0;

        if (!in_array($tipoPersona, array(Config::TIPO_PERSONA_NATURAL, Config::TIPO_PERSONA_JURIDICA), true)) {
            Respuesta::error('Debe indicar el tipo de persona (natural o jurídica).');
        }
        if ($tipoPersona === Config::TIPO_PERSONA_NATURAL && $documentoIdentidad === '') {
            Respuesta::error('Debe indicar el documento de identidad.');
        }
        if ($tipoPersona === Config::TIPO_PERSONA_JURIDICA && $nitTercero === '') {
            Respuesta::error('Debe indicar el NIT.');
        }
        if ($nombreTercero === '') {
            Respuesta::error('Debe indicar el nombre o razón comercial del beneficiario.');
        }
        if ($valorAnticipo <= 0) {
            Respuesta::error('El valor del anticipo debe ser mayor a cero.');
        }

        return array(
            'tipoPersona'        => $tipoPersona,
            'documentoIdentidad' => $documentoIdentidad !== '' ? $documentoIdentidad : null,
            'nitTercero'         => $nitTercero !== '' ? $nitTercero : null,
            'nombreTercero'      => $nombreTercero,
            'razonSocialTercero' => $razonSocialTercero !== '' ? $razonSocialTercero : null,
            'codigoSap'          => $codigoSap !== '' ? $codigoSap : null,
            'celular'            => $celular !== '' ? $celular : null,
            'correo'             => $correo !== '' ? $correo : null,
            'cargo'              => $cargo !== '' ? $cargo : null,
            'centroCostos'       => $centroCostos !== '' ? $centroCostos : null,
            'valorAnticipo'      => $valorAnticipo,
        );
    }
}
