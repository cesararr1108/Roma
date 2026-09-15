/**
 * Conecta un FlowEngine con una solicitud real. No conoce el negocio de
 * ningún nodo en particular -- solo sabe: pedir el contexto al backend,
 * decidir si el usuario puede ver/actuar en el nodo actual, pintar el
 * componente correspondiente, y cuando ese nodo llama a `avanzar(op,
 * datos)`, mandar esa acción puntual y refrescar con lo que el backend
 * diga que es el nuevo nodo actual (ver la nota de seguridad en
 * FlowEngine.js sobre por qué el backend, no el navegador, tiene la
 * última palabra sobre ESE valor).
 */
class MotorFlujo {
  constructor(flujo, usuario) {
    this.flujo = flujo;
    this.usuario = usuario;
    this.contexto = null;
  }

  async iniciar(idSolicitud, contenedor, contenedorHistorial) {
    this.idSolicitud = idSolicitud;
    this.contenedor = contenedor;
    this.contenedorHistorial = contenedorHistorial || null;
    await this.refrescar();
  }

  async refrescar() {
    UI.mostrarCargando();
    try {
      const resp = await enviarPeticion(ControlGastosApi.LINK_MODELO, 'obtener_contexto', { idSolicitud: this.idSolicitud });
      this.contexto = resp.datos;
      this.pintarNodoActual();
      if (this.contenedorHistorial) this.pintarHistorial();
    } catch (err) {
      UI.toast(err.mensaje, 'error');
    } finally {
      UI.ocultarCargando();
    }
  }

  pintarNodoActual() {
    const idNodo = this.contexto.nodoActual;
    const nodo = idNodo ? this.flujo.nodo(idNodo) : null;
    this.contenedor.dataset.nodoActual = idNodo || '';

    if (!nodo) {
      this.contenedor.innerHTML = PlantillaEspera('El sistema', 'esta solicitud llegó a un punto final del flujo.');
      return;
    }

    if (!nodo.puedeVer(this.usuario)) {
      this.contenedor.innerHTML = '';
      return;
    }

    if (!nodo.puedeEditar(this.usuario, this.contexto)) {
      const quienFalta = nodo.soloDueno ? 'El solicitante' : nodo.nombre;
      this.contenedor.innerHTML = PlantillaEspera(quienFalta, 'debe actuar en este paso del flujo.');
      return;
    }

    const api = {
      avanzar: (op, datos) => this.avanzar(op, datos),
      contexto: this.contexto,
    };
    nodo.render(this.contenedor, this.contexto, api);
  }

  async avanzar(op, datos) {
    UI.mostrarCargando();
    try {
      const resp = await enviarPeticion(ControlGastosApi.LINK_MODELO, op, datos);
      UI.toast(resp.mensaje, 'exito');
      await this.refrescar();
    } catch (err) {
      UI.toast(err.mensaje, 'error');
    } finally {
      UI.ocultarCargando();
    }
  }

  pintarHistorial() {
    const historial = this.contexto.historial || [];
    if (!historial.length) {
      this.contenedorHistorial.innerHTML = '<p class="text-sm text-slate-400">Sin movimientos registrados.</p>';
      return;
    }

    this.contenedorHistorial.innerHTML = historial.map((h) => `
      <div class="border-l-2 border-indigo-200 pl-3 pb-3">
        <p class="text-xs text-slate-400">${Formato.fechaHora(h.FECHA)} · ${h.USUARIO || 'Usuario #' + h.ID_USUARIO}</p>
        <p class="text-sm text-slate-700 font-medium">${this.nombreNodo(h.NODO_ANTERIOR)} → ${this.nombreNodo(h.NODO_NUEVO)}</p>
        ${h.COMENTARIO ? `<p class="text-sm text-slate-500">${h.COMENTARIO}</p>` : ''}
      </div>`).join('');
  }

  nombreNodo(id) {
    if (!id) return 'Inicio';
    try { return this.flujo.nodo(id).nombre; } catch (e) { return id; }
  }
}
