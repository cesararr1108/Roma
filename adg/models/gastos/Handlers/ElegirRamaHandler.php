<?php
/**
 * Nodo "decision_anticipo_factura": tras la cotización aprobada, el
 * dueño de la solicitud elige si continúa por la rama de anticipo o
 * directo con factura. Esta acción no guarda datos de negocio -- solo
 * mueve el cursor al nodo donde SÍ se van a pedir esos datos
 * (anticipo_datos / factura_datos).
 */
class ElegirRamaHandler
{
    public static function elegirAnticipo()
    {
        self::elegir('anticipo_datos', 'REQUIERE_ANTICIPO', 1);
    }

    public static function elegirFactura()
    {
        self::elegir('factura_datos', null, null);
    }

    private static function elegir($nodoSiguiente, $columnaExtra, $valorExtra)
    {
        $usuario = Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        Auth::requiereDueno($solicitud['ID_USUARIO_SOLICITA']);

        if ($solicitud['NODO_ACTUAL'] !== 'decision_anticipo_factura') {
            Respuesta::error('La solicitud no se encuentra en el paso esperado para esta acción.');
        }

        if ($columnaExtra === 'REQUIERE_ANTICIPO') {
            Db::ejecutar("UPDATE GTOS_SOLICITUDES SET REQUIERE_ANTICIPO = :valor WHERE ID = :id",
                array('valor' => $valorExtra, 'id' => $idSolicitud));
        }

        GastoRepository::fijarNodo($idSolicitud, $nodoSiguiente);

        FlujoRepository::registrar($idSolicitud, $solicitud['NODO_ACTUAL'], $nodoSiguiente,
            'ELEGIR_RAMA', $nodoSiguiente === 'anticipo_datos' ? 'El solicitante eligió pedir anticipo.' : 'El solicitante eligió registrar factura directamente.',
            $usuario['id']);

        Respuesta::ok(null, 'Continúa con '.($nodoSiguiente === 'anticipo_datos' ? 'los datos del anticipo.' : 'los datos de la factura.'));
    }
}
