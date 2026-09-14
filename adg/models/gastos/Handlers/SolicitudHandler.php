<?php
/**
 * Paso 1: creación de la solicitud junto con sus cotizaciones (mínimo 2
 * de 3, solo PDF). Cualquier rol puede solicitar.
 */
class SolicitudHandler
{
    public static function crear()
    {
        $usuario = Auth::usuarioActual();

        $descripcion   = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $justificacion = isset($_POST['justificacion']) ? trim($_POST['justificacion']) : '';
        $valorEstimado = isset($_POST['valorEstimado']) ? (float) $_POST['valorEstimado'] : 0;

        if ($descripcion === '') {
            Respuesta::error('La descripción del gasto es obligatoria.');
        }
        if ($valorEstimado <= 0) {
            Respuesta::error('El valor estimado debe ser mayor a cero.');
        }

        $archivosSubidos = array();
        $camposCotizacion = array('cotizacion1', 'cotizacion2', 'cotizacion3');
        $cargadas = 0;

        foreach ($camposCotizacion as $indice => $campo) {
            if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                try {
                    $archivosSubidos[$indice + 1] = ArchivoUploader::guardarPdf($campo, Config::RUTA_COTIZACIONES);
                    $cargadas++;
                } catch (Exception $e) {
                    Respuesta::error($e->getMessage());
                }
            }
        }

        if ($cargadas < 2) {
            Respuesta::error('Debe adjuntar al menos dos cotizaciones en PDF.');
        }

        try {
            $idSolicitud = GastoRepository::crearSolicitud(array(
                'descripcion'    => $descripcion,
                'justificacion'  => $justificacion,
                'valorEstimado'  => $valorEstimado,
                'idUsuario'      => $usuario['id'],
                'idDepartamento' => $usuario['depId'],
                'estado'         => 'EN_APROBACION_COTIZACION',
            ));

            foreach ($archivosSubidos as $consecutivo => $archivo) {
                GastoRepository::guardarCotizacion($idSolicitud, $consecutivo, $archivo);
            }

            FlujoRepository::registrar($idSolicitud, null, 'EN_APROBACION_COTIZACION',
                'SOLICITUD_CREADA', 'Solicitud creada con '.$cargadas.' cotización(es) adjunta(s).', $usuario['id']);

            Respuesta::ok(array('idSolicitud' => $idSolicitud), 'Solicitud enviada a aprobación.');
        } catch (Exception $e) {
            Respuesta::error('Error al crear la solicitud: '.$e->getMessage());
        }
    }
}
