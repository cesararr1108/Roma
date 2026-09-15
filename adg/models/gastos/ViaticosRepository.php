<?php
/**
 * Persistencia de los dos formularios estructurados propios de la rama
 * de anticipo TIPO_ANTICIPO_VIATICOS: la "solicitud de viáticos"
 * (presupuesto, se diligencia junto con los datos del anticipo) y la
 * "legalización de viáticos" (detalle de gastos por línea, se diligencia
 * cuando el anticipo ya fue aprobado).
 */
class ViaticosRepository
{
    public static function guardarSolicitud($idSolicitud, $datos)
    {
        $existente = Db::query(
            "SELECT ID FROM GTOS_VIATICOS_SOLICITUD WHERE ID_SOLICITUD = :id",
            array('id' => $idSolicitud)
        );

        $total = $datos['tiquetesAereos'] + $datos['tiquetesTerrestres'] + $datos['taxisBuses']
            + $datos['peajes'] + $datos['hospedaje'] + $datos['alimentacion']
            + $datos['flotasAcarreo'] + $datos['viaticosAdmin'] + $datos['otros'];

        $params = array(
            'idSolicitud'        => $idSolicitud,
            'nombresApellidos'   => $datos['nombresApellidos'],
            'identificacion'     => $datos['identificacion'],
            'cargo'              => $datos['cargo'],
            'telefono'           => $datos['telefono'],
            'centroCosto'        => $datos['centroCosto'],
            'email'              => $datos['email'],
            'dependencia'        => $datos['dependencia'],
            'fechaSolicitud'     => date('Y-m-d'),
            'motivo'             => $datos['motivo'],
            'fechaSalida'        => $datos['fechaSalida'],
            'fechaRegreso'       => $datos['fechaRegreso'],
            'ciudadSalida'       => $datos['ciudadSalida'],
            'salidaAereo'        => $datos['salidaAereo'] ? 1 : 0,
            'salidaTerrestre'    => $datos['salidaTerrestre'] ? 1 : 0,
            'ciudadRegreso'      => $datos['ciudadRegreso'],
            'regresoAereo'       => $datos['regresoAereo'] ? 1 : 0,
            'regresoTerrestre'   => $datos['regresoTerrestre'] ? 1 : 0,
            'tiquetesAereos'     => $datos['tiquetesAereos'],
            'tiquetesTerrestres' => $datos['tiquetesTerrestres'],
            'taxisBuses'         => $datos['taxisBuses'],
            'peajes'             => $datos['peajes'],
            'hospedaje'          => $datos['hospedaje'],
            'alimentacion'       => $datos['alimentacion'],
            'flotasAcarreo'      => $datos['flotasAcarreo'],
            'viaticosAdmin'      => $datos['viaticosAdmin'],
            'otros'              => $datos['otros'],
            'descripcionOtros'   => $datos['descripcionOtros'],
            'total'              => $total,
        );

        if (!empty($existente)) {
            $sql = "UPDATE GTOS_VIATICOS_SOLICITUD SET
                        NOMBRES_APELLIDOS = :nombresApellidos, IDENTIFICACION = :identificacion,
                        CARGO = :cargo, TELEFONO = :telefono, CENTRO_COSTO = :centroCosto,
                        EMAIL = :email, DEPENDENCIA = :dependencia, MOTIVO = :motivo,
                        FECHA_SALIDA = :fechaSalida, FECHA_REGRESO = :fechaRegreso,
                        CIUDAD_SALIDA = :ciudadSalida, SALIDA_AEREO = :salidaAereo, SALIDA_TERRESTRE = :salidaTerrestre,
                        CIUDAD_REGRESO = :ciudadRegreso, REGRESO_AEREO = :regresoAereo, REGRESO_TERRESTRE = :regresoTerrestre,
                        TIQUETES_AEREOS = :tiquetesAereos, TIQUETES_TERRESTRES = :tiquetesTerrestres,
                        TAXIS_BUSES = :taxisBuses, PEAJES = :peajes, HOSPEDAJE = :hospedaje,
                        ALIMENTACION = :alimentacion, FLOTAS_ACARREO = :flotasAcarreo,
                        VIATICOS_ADMIN = :viaticosAdmin, OTROS = :otros,
                        DESCRIPCION_OTROS = :descripcionOtros, TOTAL_SOLICITADO = :total
                    WHERE ID_SOLICITUD = :idSolicitud";
        } else {
            $sql = "INSERT INTO GTOS_VIATICOS_SOLICITUD
                        (ID_SOLICITUD, NOMBRES_APELLIDOS, IDENTIFICACION, CARGO, TELEFONO, CENTRO_COSTO,
                         EMAIL, DEPENDENCIA, FECHA_SOLICITUD, MOTIVO, FECHA_SALIDA, FECHA_REGRESO,
                         CIUDAD_SALIDA, SALIDA_AEREO, SALIDA_TERRESTRE, CIUDAD_REGRESO, REGRESO_AEREO, REGRESO_TERRESTRE,
                         TIQUETES_AEREOS, TIQUETES_TERRESTRES, TAXIS_BUSES, PEAJES, HOSPEDAJE, ALIMENTACION,
                         FLOTAS_ACARREO, VIATICOS_ADMIN, OTROS, DESCRIPCION_OTROS, TOTAL_SOLICITADO)
                    VALUES
                        (:idSolicitud, :nombresApellidos, :identificacion, :cargo, :telefono, :centroCosto,
                         :email, :dependencia, :fechaSolicitud, :motivo, :fechaSalida, :fechaRegreso,
                         :ciudadSalida, :salidaAereo, :salidaTerrestre, :ciudadRegreso, :regresoAereo, :regresoTerrestre,
                         :tiquetesAereos, :tiquetesTerrestres, :taxisBuses, :peajes, :hospedaje, :alimentacion,
                         :flotasAcarreo, :viaticosAdmin, :otros, :descripcionOtros, :total)";
        }

        Db::ejecutar($sql, $params);
        return $total;
    }

    public static function obtenerSolicitud($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_VIATICOS_SOLICITUD WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        return isset($filas[0]) ? $filas[0] : null;
    }

    /**
     * Guarda cabecera + líneas de la legalización. El total legalizado y el
     * saldo se calculan aquí, en el servidor, nunca a partir de lo que
     * envíe el cliente.
     */
    public static function guardarLegalizacion($idSolicitud, $cabecera, $filas, $valorAnticipo)
    {
        $valorLegalizado = 0;
        foreach ($filas as $fila) {
            $valorLegalizado += $fila['transporte'] + $fila['taxis'] + $fila['hotel'] + $fila['alimentacion']
                + $fila['atencion'] + $fila['gasolina'] + $fila['servicios'] + $fila['otros'];
        }
        $saldo = $valorAnticipo - $valorLegalizado;

        $idLegalizacion = Db::nuevoId(
            "INSERT INTO GTOS_VIATICOS_LEGALIZACION
                (ID_SOLICITUD, NOMBRES_APELLIDOS, IDENTIFICACION, DESCRIPCION, FECHA_DESDE, FECHA_HASTA,
                 CENTRO_COSTO, VALOR_ANTICIPO, VALOR_LEGALIZADO, SALDO, FECHA_CREACION)
             VALUES
                (:idSolicitud, :nombresApellidos, :identificacion, :descripcion, :fechaDesde, :fechaHasta,
                 :centroCosto, :valorAnticipo, :valorLegalizado, :saldo, GETDATE());
             SELECT SCOPE_IDENTITY() AS ID;",
            array(
                'idSolicitud'      => $idSolicitud,
                'nombresApellidos' => $cabecera['nombresApellidos'],
                'identificacion'   => $cabecera['identificacion'],
                'descripcion'      => $cabecera['descripcion'],
                'fechaDesde'       => $cabecera['fechaDesde'],
                'fechaHasta'       => $cabecera['fechaHasta'],
                'centroCosto'      => $cabecera['centroCosto'],
                'valorAnticipo'    => $valorAnticipo,
                'valorLegalizado'  => $valorLegalizado,
                'saldo'            => $saldo,
            )
        );

        foreach ($filas as $fila) {
            $totalFila = $fila['transporte'] + $fila['taxis'] + $fila['hotel'] + $fila['alimentacion']
                + $fila['atencion'] + $fila['gasolina'] + $fila['servicios'] + $fila['otros'];

            Db::ejecutar(
                "INSERT INTO GTOS_VIATICOS_LEGALIZACION_DETALLE
                    (ID_LEGALIZACION, FECHA_GASTO, CENTRO_COSTO, DOCUMENTO, DETALLE, TRANSPORTE, TAXIS,
                     HOTEL, ALIMENTACION, ATENCION, GASOLINA, SERVICIOS, OTROS, TOTAL_FILA)
                 VALUES
                    (:idLegalizacion, :fechaGasto, :centroCosto, :documento, :detalle, :transporte, :taxis,
                     :hotel, :alimentacion, :atencion, :gasolina, :servicios, :otros, :totalFila)",
                array(
                    'idLegalizacion' => $idLegalizacion,
                    'fechaGasto'     => $fila['fechaGasto'],
                    'centroCosto'    => $fila['centroCosto'],
                    'documento'      => $fila['documento'],
                    'detalle'        => $fila['detalle'],
                    'transporte'     => $fila['transporte'],
                    'taxis'          => $fila['taxis'],
                    'hotel'          => $fila['hotel'],
                    'alimentacion'   => $fila['alimentacion'],
                    'atencion'       => $fila['atencion'],
                    'gasolina'       => $fila['gasolina'],
                    'servicios'      => $fila['servicios'],
                    'otros'          => $fila['otros'],
                    'totalFila'      => $totalFila,
                )
            );
        }

        return array('idLegalizacion' => $idLegalizacion, 'valorLegalizado' => $valorLegalizado, 'saldo' => $saldo);
    }

    public static function obtenerLegalizacion($idSolicitud)
    {
        $filas = Db::query("SELECT * FROM GTOS_VIATICOS_LEGALIZACION WHERE ID_SOLICITUD = :id", array('id' => $idSolicitud));
        $legalizacion = isset($filas[0]) ? $filas[0] : null;

        if ($legalizacion) {
            $legalizacion['detalle'] = Db::query(
                "SELECT * FROM GTOS_VIATICOS_LEGALIZACION_DETALLE WHERE ID_LEGALIZACION = :id ORDER BY FECHA_GASTO ASC",
                array('id' => $legalizacion['ID'])
            );
        }

        return $legalizacion;
    }
}
