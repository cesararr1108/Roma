<?php
/**
 * Tesorería realiza el pago. Si la solicitud tenía anticipo, queda
 * pendiente la compensación (contabilidad); si no, el flujo termina aquí.
 * El comprobante de pago solo es obligatorio si la solicitud se marcó
 * como REQUIERE_SOPORTE al crearla.
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
        if (!$solicitud || $solicitud['ESTADO'] !== 'CAUSADO') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
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
        $estadoDestino = $requiereAnticipo ? 'PAGADO' : 'FINALIZADO';

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], $estadoDestino);

            GastoRepository::guardarPago($idSolicitud, $numeroComprobante, $archivo, $usuario['id']);
            GastoRepository::cambiarEstado($idSolicitud, $estadoDestino);

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], $estadoDestino,
                'REGISTRAR_PAGO', 'Comprobante de pago '.$numeroComprobante.'.', $usuario['id']);

            $mensaje = $requiereAnticipo
                ? 'Pago registrado. Queda pendiente la compensación del anticipo.'
                : 'Pago registrado. El flujo ha finalizado.';

            Respuesta::ok(null, $mensaje);
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
