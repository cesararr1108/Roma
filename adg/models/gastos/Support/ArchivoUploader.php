<?php
/**
 * Adaptador de almacenamiento de soportes. Valida que el archivo recibido
 * sea PDF y lo mueve a la ruta pública configurada en Config.php.
 */
class ArchivoUploader
{
    public static function guardarPdf($campoArchivo, $rutaDestinoRelativa)
    {
        if (!isset($_FILES[$campoArchivo]) || $_FILES[$campoArchivo]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('El archivo "'.$campoArchivo.'" es requerido.');
        }

        $archivo = $_FILES[$campoArchivo];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            throw new Exception('El archivo "'.$campoArchivo.'" debe ser un PDF.');
        }

        $rutaAbsolutaBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/').$rutaDestinoRelativa;
        if (!is_dir($rutaAbsolutaBase)) {
            mkdir($rutaAbsolutaBase, 0755, true);
        }

        $nombreFinal = date('YmdHis').'_'.uniqid().'.pdf';
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
