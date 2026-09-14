<?php
/**
 * Paso 1.4: tesorería realiza el pago. Si la solicitud tenía anticipo,
 * queda pendiente la compensación (1.5, contabilidad); si venía por
 * factura, el flujo termina aquí.
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

        $requiereAnticipo = ((int) $solicitud['REQUIERE_ANTICIPO'] === 1);
        $estadoDestino = $requiereAnticipo ? 'PAGADO' : 'FINALIZADO';

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], $estadoDestino);

            GastoRepository::guardarPago($idSolicitud, $numeroComprobante, $usuario['id']);
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
