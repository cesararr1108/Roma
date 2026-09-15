/**
 * Nodo "cruzado" (solo rama factura, sin anticipo): contabilidad cruza
 * el pago contra la factura.
 */
registrarNodo(new Nodo({
  id: 'cruzado',
  nombre: 'Cruce contable',
  rolesEditar: ROLES.CONTABILIDAD,
  siguiente: 'finalizado',

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <form data-form class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar cruce</h4>
        <div>
          <label class="text-sm text-slate-600">Número de cruce</label>
          <input name="numeroCruce" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Finalizar flujo</button>
      </form>`;

    contenedor.querySelector('[data-form]').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = contexto.idSolicitud;
      api.avanzar('registrar_cruce', datos);
    });
  },
}));
