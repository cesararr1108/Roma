/**
 * Registro de módulos de flujo (patrón registry). Cada estado del backend
 * (ver Config::estados() en el modelo PHP) se asocia aquí a un módulo
 * independiente responsable de pintar su UI y ejecutar sus acciones.
 *
 * Agregar, quitar o modificar un paso del flujo es tan simple como crear/editar
 * un archivo en controllers/modules/*.module.js y registrarlo con el mismo
 * código de estado usado en el backend -- el orquestador (ControlGastos.js)
 * nunca necesita cambiar.
 */
const RegistroEstados = (() => {
  const modulos = {};

  const registrar = (codigoEstado, modulo) => {
    modulos[codigoEstado] = modulo;
  };

  const obtener = (codigoEstado) => modulos[codigoEstado] || null;

  return { registrar, obtener };
})();
