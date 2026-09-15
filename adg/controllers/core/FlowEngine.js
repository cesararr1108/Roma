/**
 * Registro global de nodos (componentes reutilizables) + motor de grafo.
 *
 * Un mismo Nodo puede pertenecer a varios flujos distintos: cada
 * FlowEngine solo guarda a qué nodo apunta cada uno de LOS SUYOS --
 * mediante `overrides`, sin tocar el `.siguiente` del nodo original --
 * así reutilizar un nodo en un flujo nuevo, o hacer que en ese flujo
 * apunte a otro lado, nunca afecta al flujo que ya lo estaba usando.
 * Es literalmente "apuntar este nodo a otro nodo", como se pensó.
 *
 * NOTA DE SEGURIDAD (por qué esto es solo el mapa, no el candado):
 * `resolverSiguiente()` de aquí es para el navegador -- decide qué
 * componente pintar después, arma el stepper, documenta el flujo.
 * El valor que de verdad queda guardado en la solicitud lo escribe,
 * de forma independiente, la acción de backend de ese paso (p. ej.
 * registrar_pago.php siempre dice "esto sigue en legalización o en
 * cruce", nunca lo que mande el navegador). Si el backend confiara en
 * el navegador para saber "a qué nodo salto", cualquiera con acceso a
 * UNA acción del flujo (p. ej. contabilidad, que causa y compensa)
 * podría saltarse un paso de OTRO rol (p. ej. el pago de tesorería)
 * simplemente mandando el nodo que quisiera. Por eso el grafo vive acá,
 * pero cada acción del PHP es dueña de su propio "siguiente".
 */
const RegistroNodos = new Map();

function registrarNodo(nodo) {
  RegistroNodos.set(nodo.id, nodo);
  return nodo;
}

function obtenerNodo(id) {
  const nodo = RegistroNodos.get(id);
  if (!nodo) {
    throw new Error(`No existe un nodo registrado con id "${id}".`);
  }
  return nodo;
}

class FlowEngine {
  constructor(nombre, nodoInicialId) {
    this.nombre = nombre;
    this.nodoInicialId = nodoInicialId;
    this.overrides = new Map();
  }

  nodo(id) {
    return obtenerNodo(id);
  }

  /** "en cualquier momento le puedo decir que este nodo va a apuntar a otro nodo". */
  redirigir(idNodo, nuevoSiguiente) {
    this.overrides.set(idNodo, nuevoSiguiente);
    return this;
  }

  resolverSiguiente(idNodo, contexto) {
    const resolver = this.overrides.has(idNodo) ? this.overrides.get(idNodo) : this.nodo(idNodo).siguiente;
    return typeof resolver === 'function' ? resolver(contexto) : resolver;
  }

  /**
   * Crea un flujo nuevo que reutiliza EXACTAMENTE los mismos nodos (mismos
   * componentes, mismo código), pero puede arrancar en otro punto y/o
   * redirigir algunos de sus "siguiente" sin tocar el flujo original.
   * Ejemplo de uso (un flujo hipotético de mañana que reutiliza la mitad
   * del flujo de anticipo, pero termina distinto):
   *
   *   const FlujoViaticosExpress = FlujoControlGastos.derivar(
   *     'viaticos_express',
   *     'anticipo_datos',
   *     { pago: 'finalizado' } // en este flujo, tras pagar, no hay legalización
   *   );
   */
  derivar(nombre, nodoInicialId, overrides = {}) {
    const nuevo = new FlowEngine(nombre, nodoInicialId);
    Object.entries(overrides).forEach(([id, siguiente]) => nuevo.redirigir(id, siguiente));
    return nuevo;
  }
}
