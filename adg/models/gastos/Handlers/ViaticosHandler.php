<?php
/**
 * Legalización de viáticos: cuando el anticipo (TIPO_ANTICIPO=VIATICOS)
 * ya fue aprobado, el solicitante debe reportar el detalle real de gastos
 * por línea. El total legalizado y el saldo se calculan en el servidor.
 */
class ViaticosHandler
{
    public static function registrarLegalizacion()
    {
        $usuario = Auth::usuarioActual();
        $idSolicitud = isset($_POST['idSolicitud']) ? (int) $_POST['idSolicitud'] : 0;

        $solicitud = GastoRepository::obtenerSolicitud($idSolicitud);
        if (!$solicitud) {
            Respuesta::error('La solicitud no existe.', 404);
        }
        Auth::requiereDueno($solicitud['ID_USUARIO_SOLICITA']);

        if ($solicitud['ESTADO'] !== 'ANTICIPO_APROBADO') {
            Respuesta::error('La solicitud no se encuentra en el estado esperado para esta acción.');
        }
        if ($solicitud['TIPO_ANTICIPO'] !== Config::TIPO_ANTICIPO_VIATICOS) {
            Respuesta::error('Esta solicitud no corresponde a un anticipo de viáticos.');
        }

        $anticipo = GastoRepository::obtenerAnticipo($idSolicitud);

        $cabecera = array(
            'nombresApellidos' => isset($_POST['nombresApellidos']) ? trim($_POST['nombresApellidos']) : '',
            'identificacion'   => isset($_POST['identificacion']) ? trim($_POST['identificacion']) : '',
            'descripcion'      => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '',
            'fechaDesde'       => isset($_POST['fechaDesde']) ? trim($_POST['fechaDesde']) : '',
            'fechaHasta'       => isset($_POST['fechaHasta']) ? trim($_POST['fechaHasta']) : '',
            'centroCosto'      => isset($_POST['centroCosto']) ? trim($_POST['centroCosto']) : '',
        );

        // Mismos 6 campos obligatorios que validarFormularioLegalizacionViaticos() en el formato F-FR-024.
        if ($cabecera['nombresApellidos'] === '') {
            Respuesta::error('Debe ingresar el nombre del tercero.');
        }
        if ($cabecera['identificacion'] === '') {
            Respuesta::error('Debe ingresar la identificación del tercero.');
        }
        if ($cabecera['descripcion'] === '') {
            Respuesta::error('Debe ingresar la descripción del gasto.');
        }
        if ($cabecera['fechaDesde'] === '') {
            Respuesta::error('Debe ingresar la fecha desde.');
        }
        if ($cabecera['fechaHasta'] === '') {
            Respuesta::error('Debe ingresar la fecha hasta.');
        }
        if ($cabecera['centroCosto'] === '') {
            Respuesta::error('Debe ingresar el centro de costo.');
        }

        $filas = json_decode(isset($_POST['filas']) ? $_POST['filas'] : '[]', true);
        if (!is_array($filas) || count($filas) === 0) {
            Respuesta::error('Debe agregar al menos una línea de gasto a la legalización.');
        }

        // Mismas 3 validaciones por fila que validarFormularioLegalizacionViaticos(): fecha, centro de
        // costo y detalle no vacíos, y que la fila sume algo (evita filas "en blanco" con 0 en todo).
        $filasValidadas = array();
        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 1;

            $fechaGasto  = isset($fila['fechaGasto']) ? trim($fila['fechaGasto']) : '';
            $centroCosto = isset($fila['centroCosto']) ? trim($fila['centroCosto']) : '';
            $detalle     = isset($fila['detalle']) ? trim($fila['detalle']) : '';

            if ($fechaGasto === '') {
                Respuesta::error('Debe ingresar la fecha del gasto en la fila '.$numeroFila.'.');
            }
            if ($centroCosto === '') {
                Respuesta::error('Debe ingresar el centro de costo en la fila '.$numeroFila.'.');
            }
            if ($detalle === '') {
                Respuesta::error('Debe ingresar la descripción del gasto en la fila '.$numeroFila.'.');
            }

            $filaValidada = array(
                'fechaGasto'   => $fechaGasto,
                'centroCosto'  => $centroCosto,
                'documento'    => isset($fila['documento']) ? trim($fila['documento']) : null,
                'detalle'      => $detalle,
                'transporte'   => isset($fila['transporte']) ? (float) $fila['transporte'] : 0,
                'taxis'        => isset($fila['taxis']) ? (float) $fila['taxis'] : 0,
                'hotel'        => isset($fila['hotel']) ? (float) $fila['hotel'] : 0,
                'alimentacion' => isset($fila['alimentacion']) ? (float) $fila['alimentacion'] : 0,
                'atencion'     => isset($fila['atencion']) ? (float) $fila['atencion'] : 0,
                'gasolina'     => isset($fila['gasolina']) ? (float) $fila['gasolina'] : 0,
                'servicios'    => isset($fila['servicios']) ? (float) $fila['servicios'] : 0,
                'otros'        => isset($fila['otros']) ? (float) $fila['otros'] : 0,
            );

            $totalFila = $filaValidada['transporte'] + $filaValidada['taxis'] + $filaValidada['hotel']
                + $filaValidada['alimentacion'] + $filaValidada['atencion'] + $filaValidada['gasolina']
                + $filaValidada['servicios'] + $filaValidada['otros'];

            if ($totalFila <= 0) {
                Respuesta::error('Debe especificar al menos un valor de gasto en la fila '.$numeroFila.'.');
            }

            $filasValidadas[] = $filaValidada;
        }

        try {
            EstadoMachine::validarTransicion($solicitud['ESTADO'], 'SOPORTE_PENDIENTE_APROBACION');

            $resultado = ViaticosRepository::guardarLegalizacion(
                $idSolicitud, $cabecera, $filasValidadas, (float) $anticipo['VALOR_ANTICIPO']
            );
            GastoRepository::cambiarEstado($idSolicitud, 'SOPORTE_PENDIENTE_APROBACION');

            FlujoRepository::registrar($idSolicitud, $solicitud['ESTADO'], 'SOPORTE_PENDIENTE_APROBACION',
                'REGISTRAR_LEGALIZACION_VIATICOS',
                'Legalización registrada por '.$resultado['valorLegalizado'].' (saldo '.$resultado['saldo'].').', $usuario['id']);

            Respuesta::ok($resultado, 'Legalización registrada correctamente.');
        } catch (Exception $e) {
            Respuesta::error($e->getMessage());
        }
    }
}
