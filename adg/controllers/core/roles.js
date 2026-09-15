/**
 * Roles que participan del flujo, en el navegador. Mantener en sincronía
 * con las constantes de rol de adg/models/gastos/Config.php -- el
 * backend sigue siendo quien valida de verdad (Auth::requiereRol en
 * cada acción); esto solo evita pintar un formulario que el backend
 * igual va a rechazar.
 */
const ROLES = {
  ADMINISTRADOR: 1,
  GERENCIA_ADMINISTRATIVA: [73, 1],
  CONTABILIDAD: [26, 69, 1],
  TESORERIA: [4, 1],
};
