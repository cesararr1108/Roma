<?php
/**
 * Creación de una solicitud tipo COTIZACIÓN: sube 2 o 3 cotizaciones en
 * PDF (cualquier rol) y queda pendiente de que gerencia administrativa
 * elija la ganadora.
 */
class SolicitudCotizacionHandler
{
    public static function crear()
    {
        $usuario = Auth::usuarioActual();
        $comunes = SolicitudComun::leerDatosComunes($usuario);

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
            $estadoInicial = Config::estadoInicial(Config::TIPO_GASTO_COTIZACION);

            $idSolicitud = GastoRepository::crearSolicitud(array_merge($comunes, array(
                'tipoGasto'        => Config::TIPO_GASTO_COTIZACION,
                'tipoAnticipo'     => null,
                'estado'           => $estadoInicial,
                'requiereAnticipo' => 0,
            )));

            foreach ($archivosSubidos as $consecutivo => $archivo) {
                GastoRepository::guardarCotizacion($idSolicitud, $consecutivo, $archivo);
            }

            FlujoRepository::registrar($idSolicitud, null, $estadoInicial,
                'SOLICITUD_CREADA', 'Solicitud de cotización creada con '.$cargadas.' cotización(es) adjunta(s).', $usuario['id']);

            Respuesta::ok(array('idSolicitud' => $idSolicitud), 'Solicitud enviada a aprobación.');
        } catch (Exception $e) {
            Respuesta::error('Error al crear la solicitud: '.$e->getMessage());
        }
    }
}
