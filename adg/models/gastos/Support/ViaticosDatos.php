<?php
/**
 * Lectura/validación del formato "Solicitud de viáticos" (F-FR-023 en el
 * sistema de gestión de calidad). Replica exactamente las reglas de
 * validarFormularioViaticos() del sistema anterior:
 *
 *   - todos los campos de datos del solicitante son obligatorios,
 *   - el correo debe tener formato válido,
 *   - las fechas deben ser parseables,
 *   - debe haber al menos un rubro presupuestado > 0 (los peajes no cuentan).
 *
 * Se diligencia junto con los datos del anticipo cuando TIPO_ANTICIPO =
 * VIATICOS, sin importar si la solicitud nació directamente como anticipo
 * o como rama de una cotización aprobada.
 */
class ViaticosDatos
{
    public static function leerSolicitudYValidar()
    {
        $dependencia       = isset($_POST['viaticosDependencia']) ? trim($_POST['viaticosDependencia']) : '';
        $nombresApellidos  = isset($_POST['viaticosNombres']) ? trim($_POST['viaticosNombres']) : '';
        $identificacion    = isset($_POST['viaticosIdentificacion']) ? trim($_POST['viaticosIdentificacion']) : '';
        $cargo             = isset($_POST['viaticosCargo']) ? trim($_POST['viaticosCargo']) : '';
        $telefono          = isset($_POST['viaticosTelefono']) ? trim($_POST['viaticosTelefono']) : '';
        $centroCosto       = isset($_POST['viaticosCentroCosto']) ? trim($_POST['viaticosCentroCosto']) : '';
        $email             = isset($_POST['viaticosEmail']) ? trim($_POST['viaticosEmail']) : '';
        $motivo            = isset($_POST['viaticosMotivo']) ? trim($_POST['viaticosMotivo']) : '';
        $fechaSalida       = isset($_POST['viaticosFechaSalida']) ? trim($_POST['viaticosFechaSalida']) : '';
        $fechaRegreso      = isset($_POST['viaticosFechaRegreso']) ? trim($_POST['viaticosFechaRegreso']) : '';
        $descripcionOtros  = isset($_POST['descripcionOtros']) ? trim($_POST['descripcionOtros']) : '';

        // Datos de la "Solicitud de pasajes" (Salida/Regreso): visuales en el formato original,
        // nunca fueron obligatorios -- se guardan si vienen, sin bloquear el envío si faltan.
        $ciudadSalida     = isset($_POST['viaticosCiudadSalida']) ? trim($_POST['viaticosCiudadSalida']) : '';
        $salidaAereo      = !empty($_POST['viaticosSalidaAereo']);
        $salidaTerrestre  = !empty($_POST['viaticosSalidaTerrestre']);
        $ciudadRegreso    = isset($_POST['viaticosCiudadRegreso']) ? trim($_POST['viaticosCiudadRegreso']) : '';
        $regresoAereo     = !empty($_POST['viaticosRegresoAereo']);
        $regresoTerrestre = !empty($_POST['viaticosRegresoTerrestre']);

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

        // Los mismos campos obligatorios que camposRequeridos en validarFormularioViaticos().
        $requeridos = array(
            'Dependencia'               => $dependencia,
            'Nombres y apellidos'       => $nombresApellidos,
            'Documento de identidad'    => $identificacion,
            'Cargo'                     => $cargo,
            'Teléfono fijo'             => $telefono,
            'Centro de costos'          => $centroCosto,
            'Correo electrónico'        => $email,
            'Fecha de salida'           => $fechaSalida,
            'Fecha de regreso'          => $fechaRegreso,
            'Motivo de la solicitud'    => $motivo,
        );
        foreach ($requeridos as $etiqueta => $valor) {
            if ($valor === '') {
                Respuesta::error('El campo "'.$etiqueta.'" es obligatorio.');
            }
        }

        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            Respuesta::error('El correo electrónico no tiene un formato válido.');
        }
        if (strtotime($fechaSalida) === false) {
            Respuesta::error('La fecha de salida no tiene un formato de fecha válido.');
        }
        if (strtotime($fechaRegreso) === false) {
            Respuesta::error('La fecha de regreso no tiene un formato de fecha válido.');
        }
        if ($sumaSinPeajes <= 0) {
            Respuesta::error('Debe ingresar al menos un valor presupuestado mayor a 0 (los peajes no son obligatorios).');
        }

        return array_merge(array(
            'dependencia'       => $dependencia,
            'nombresApellidos'  => $nombresApellidos,
            'identificacion'    => $identificacion,
            'cargo'             => $cargo,
            'telefono'          => $telefono,
            'centroCosto'       => $centroCosto,
            'email'             => $email,
            'motivo'            => $motivo,
            'fechaSalida'       => $fechaSalida,
            'fechaRegreso'      => $fechaRegreso,
            'descripcionOtros'  => $descripcionOtros !== '' ? $descripcionOtros : null,
            'ciudadSalida'      => $ciudadSalida !== '' ? $ciudadSalida : null,
            'salidaAereo'       => $salidaAereo,
            'salidaTerrestre'   => $salidaTerrestre,
            'ciudadRegreso'     => $ciudadRegreso !== '' ? $ciudadRegreso : null,
            'regresoAereo'      => $regresoAereo,
            'regresoTerrestre'  => $regresoTerrestre,
        ), $valores);
    }
}
