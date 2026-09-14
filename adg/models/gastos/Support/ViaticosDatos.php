<?php
/**
 * Lectura/validación del formulario de "solicitud de viáticos"
 * (presupuesto por rubro). Se diligencia junto con los datos del
 * anticipo cuando TIPO_ANTICIPO = VIATICOS, sin importar si la solicitud
 * nació directamente como anticipo o como rama de una cotización aprobada.
 */
class ViaticosDatos
{
    public static function leerSolicitudYValidar()
    {
        $nombresApellidos = isset($_POST['viaticosNombres']) ? trim($_POST['viaticosNombres']) : '';
        $identificacion    = isset($_POST['viaticosIdentificacion']) ? trim($_POST['viaticosIdentificacion']) : '';
        $cargo             = isset($_POST['viaticosCargo']) ? trim($_POST['viaticosCargo']) : '';
        $telefono          = isset($_POST['viaticosTelefono']) ? trim($_POST['viaticosTelefono']) : '';
        $centroCosto       = isset($_POST['viaticosCentroCosto']) ? trim($_POST['viaticosCentroCosto']) : '';
        $email             = isset($_POST['viaticosEmail']) ? trim($_POST['viaticosEmail']) : '';
        $dependencia       = isset($_POST['viaticosDependencia']) ? trim($_POST['viaticosDependencia']) : '';
        $motivo            = isset($_POST['viaticosMotivo']) ? trim($_POST['viaticosMotivo']) : '';
        $fechaSalida       = isset($_POST['viaticosFechaSalida']) ? trim($_POST['viaticosFechaSalida']) : '';
        $fechaRegreso      = isset($_POST['viaticosFechaRegreso']) ? trim($_POST['viaticosFechaRegreso']) : '';
        $descripcionOtros  = isset($_POST['descripcionOtros']) ? trim($_POST['descripcionOtros']) : '';

        $rubros = array(
            'tiquetesAereos', 'tiquetesTerrestres', 'taxisBuses', 'peajes', 'hospedaje',
            'alimentacion', 'flotasAcarreo', 'viaticosAdmin', 'otros',
        );
        $valores = array();
        $sumaSinPeajes = 0;
        foreach ($rubros as $rubro) {
            $valores[$rubro] = isset($_POST[$rubro]) ? (float) $_POST[$rubro] : 0;
            if ($rubro !== 'peajes') {
                $sumaSinPeajes += $valores[$rubro];
            }
        }

        if ($nombresApellidos === '' || $identificacion === '' || $motivo === '') {
            Respuesta::error('Debe diligenciar el formulario de solicitud de viáticos (nombres, identificación y motivo).');
        }
        if ($fechaSalida === '' || $fechaRegreso === '') {
            Respuesta::error('Debe indicar las fechas de salida y regreso del viaje.');
        }
        if ($sumaSinPeajes <= 0) {
            Respuesta::error('Debe presupuestar al menos un rubro del viaje (además de peajes).');
        }

        return array_merge(array(
            'nombresApellidos' => $nombresApellidos,
            'identificacion'   => $identificacion,
            'cargo'            => $cargo !== '' ? $cargo : null,
            'telefono'         => $telefono !== '' ? $telefono : null,
            'centroCosto'      => $centroCosto !== '' ? $centroCosto : null,
            'email'            => $email !== '' ? $email : null,
            'dependencia'      => $dependencia !== '' ? $dependencia : null,
            'motivo'           => $motivo,
            'fechaSalida'      => $fechaSalida,
            'fechaRegreso'     => $fechaRegreso,
            'descripcionOtros' => $descripcionOtros !== '' ? $descripcionOtros : null,
        ), $valores);
    }
}
