<?php
/**
 * Adaptador de almacenamiento de soportes. Valida la extensión del
 * archivo recibido y lo mueve a la ruta pública configurada en Config.php.
 */
class ArchivoUploader
{
    /** Cotizaciones y facturas: solo PDF. */
    public static function guardarPdf($campoArchivo, $rutaDestinoRelativa)
    {
        return self::guardarDocumento($campoArchivo, $rutaDestinoRelativa, array('pdf'));
    }

    /** Soportes de pago/compensación: el legacy admite además imágenes escaneadas. */
    public static function guardarDocumento($campoArchivo, $rutaDestinoRelativa, $extensionesPermitidas = array('pdf'))
    {
        if (!isset($_FILES[$campoArchivo]) || $_FILES[$campoArchivo]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('El archivo "'.$campoArchivo.'" es requerido.');
        }

        $archivo = $_FILES[$campoArchivo];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionesPermitidas, true)) {
            throw new Exception('El archivo "'.$campoArchivo.'" debe ser de tipo: '.implode(', ', $extensionesPermitidas).'.');
        }

        $rutaAbsolutaBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/').$rutaDestinoRelativa;
        if (!is_dir($rutaAbsolutaBase)) {
            mkdir($rutaAbsolutaBase, 0755, true);
        }

        $nombreFinal = date('YmdHis').'_'.uniqid().'.'.$extension;
        $rutaAbsolutaArchivo = $rutaAbsolutaBase.$nombreFinal;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsolutaArchivo)) {
            throw new Exception('No fue posible guardar el archivo "'.$campoArchivo.'".');
        }

        return array(
            'nombreOriginal' => $archivo['name'],
            'nombreArchivo'  => $nombreFinal,
            'rutaRelativa'   => $rutaDestinoRelativa.$nombreFinal,
        );
    }
}
