<?php
/**
 * Datos comunes a los 3 tipos de solicitud (cotización, factura, anticipo):
 * organización/oficina, concepto del gasto, comentario y si requiere
 * soporte de pago. Cada Handler de creación llama a esto antes de leer
 * sus propios campos específicos.
 */
class SolicitudComun
{
    public static function leerDatosComunes($usuario)
    {
        $idConcepto = isset($_POST['idConcepto']) ? (int) $_POST['idConcepto'] : 0;
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
        $requiereSoporte = isset($_POST['requiereSoporte']) ? (int) $_POST['requiereSoporte'] : 1;
        $organizacionVentas = isset($_POST['organizacionVentas']) ? trim($_POST['organizacionVentas']) : '';
        $oficinaVentas = isset($_POST['oficinaVentas']) ? trim($_POST['oficinaVentas']) : '';

        if ($idConcepto <= 0) {
            Respuesta::error('Debe seleccionar el concepto del gasto.');
        }
        if ($comentario === '') {
            Respuesta::error('El comentario de la solicitud es obligatorio.');
        }
        if ($organizacionVentas === '' || $oficinaVentas === '') {
            Respuesta::error('Debe indicar la organización y oficina de ventas.');
        }

        return array(
            'idConcepto'         => $idConcepto,
            'comentario'         => $comentario,
            'requiereSoporte'    => $requiereSoporte ? 1 : 0,
            'organizacionVentas' => $organizacionVentas,
            'oficinaVentas'      => $oficinaVentas,
            'idUsuario'          => $usuario['id'],
            'idDepartamento'     => $usuario['depId'],
        );
    }
}
