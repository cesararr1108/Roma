/**
 * Un Nodo es un paso del flujo: un componente independiente con su propio
 * render, su propia validación implícita (la que traiga su formulario) y
 * sus propios roles de acceso. Un nodo no sabe nada de los demás nodos --
 * solo sabe cómo dibujarse y a qué nodo debería ir después (su
 * "siguiente"), que puede ser un id fijo o una función que decide según
 * el contexto de la solicitud (ej: si requiere anticipo o no).
 *
 * El id de un Nodo es el mismo valor que el backend guarda como
 * "nodoActual" de la solicitud -- así el navegador y la base de datos
 * hablan del mismo vocabulario sin traducciones intermedias.
 */
class Nodo {
  /**
   * @param {Object} definicion
   * @param {string} definicion.id
   * @param {string} definicion.nombre            Título legible del paso (para el stepper/bitácora).
   * @param {string[]} [definicion.rolesVer]       Roles que pueden ver este nodo aunque no puedan actuar. [] = cualquiera.
   * @param {string[]} [definicion.rolesEditar]    Roles que pueden actuar (enviar datos) en este nodo. [] = ninguna restricción de rol.
   * @param {boolean}  [definicion.soloDueno]      Si es true, solo el solicitante original puede actuar en este nodo.
   * @param {(contenedor: HTMLElement, contexto: Object, api: Object) => void} definicion.render
   *        Pinta el paso dentro de `contenedor`. Recibe el contexto de la solicitud y una `api`
   *        con `api.avanzar(op, datos)` para enviar la acción de este paso al backend.
   * @param {string|null|((contexto: Object) => string|null)} [definicion.siguiente]
   *        Id del nodo al que se pasa una vez completado este paso, o una función que lo decide
   *        con el contexto ya actualizado. Es solo informativo/orientativo para el navegador --
   *        el valor real que queda guardado lo determina, de forma independiente, la acción de
   *        backend correspondiente (ver nota de seguridad en FlowEngine.js).
   */
  constructor(definicion) {
    this.id = definicion.id;
    this.nombre = definicion.nombre || definicion.id;
    this.rolesVer = definicion.rolesVer || [];
    this.rolesEditar = definicion.rolesEditar || [];
    this.soloDueno = Boolean(definicion.soloDueno);
    this.render = definicion.render;
    this.siguiente = definicion.siguiente !== undefined ? definicion.siguiente : null;
  }

  puedeVer(usuario) {
    if (this.rolesVer.length === 0) return true;
    return this.rolesVer.includes(usuario.rolId);
  }

  puedeEditar(usuario, contexto) {
    if (this.soloDueno && Number(contexto.idUsuarioSolicita) !== Number(usuario.id)) {
      return false;
    }
    if (this.rolesEditar.length === 0) {
      return true;
    }
    return this.rolesEditar.includes(usuario.rolId);
  }

  /** Solo informativo: no persiste nada, ayuda a pintar el stepper o a decidir qué mostrar en el navegador. */
  resolverSiguiente(contexto) {
    return typeof this.siguiente === 'function' ? this.siguiente(contexto) : this.siguiente;
  }
}
