<?php
/**
 * Nodo "pago": tesorería registra el pago. El siguiente nodo depende de
 * REQUIERE_ANTICIPO, una bandera fijada al crear la solicitud (o al
 * elegir rama) -- dato ya persistido, no algo que mande el navegador:
 * si requiere anticipo, sigue a "legalizacion"; si no, a "cruzado".
 */
class PagoHandler
{
    public static function registrar()
    {
        $usuario = Auth::requiereRol(Config::rolesTesoreria());
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;
        $numeroComprobante = isset($_POST['numeroComprobantePago']) ? trim($_POST['numeroComprobantePago']) : '';

        if ($numeroComprobante === '') {
            Respuesta::error('Debe indicar el número de comprobante de pago.');
        }

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud || $solicitud['NODO_ACTUAL'] !== 'pago') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        $archivo = null;
        if ((int) $solicitud['REQUIERE_SOPORTE'] === 1) {
            try {
                $archivo = ArchivoUploader::guardarDocumento('adjuntoPago', Config::RUTA_FACTURAS, array('pdf', 'jpg', 'jpeg', 'png'));
            } catch (Exception $e) {
                Respuesta::error('Esta solicitud requiere soporte de pago: '.$e->getMessage());
            }
        }

        $requiereAnticipo = ((int) $solicitud['REQUIERE_ANTICIPO'] === 1);
        $nodoSiguiente = $requiereAnticipo ? 'legalizacion' : 'cruzado';

        try {
            GastoRepository::guardarPago($idSolicitud, $numeroComprobante, $archivo, $usuario['id']);
            GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

            FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
                'REGISTRAR_PAGO', 'Comprobante de pago '.$numeroComprobante.'.', $usuario['id']);

            $mensaje = $requiereAnticipo
                ? 'Pago registrado. Queda pendiente legalizar el anticipo.'
                : 'Pago registrado. Queda pendiente el cruce contable.';

            Respuesta::ok(null, $mensaje);
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
