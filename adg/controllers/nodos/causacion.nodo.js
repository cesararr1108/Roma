/**
 * Nodo "causacion": contabilidad causa la solicitud. Todas las ramas
 * confluyen aquí antes de pago.
 */
registrarNodo(new Nodo({
  id: 'causacion',
  nombre: 'Causación',
  rolesEditar: ROLES.CONTABILIDAD,
  siguiente: 'pago',

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <form data-form class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar causación</h4>
        <div>
          <label class="text-sm text-slate-600">Número de causación</label>
          <input name="numeroCausacion" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="text-sm text-slate-600">Nota</label>
          <textarea name="nota" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar causación</button>
      </form>`;

    contenedor.querySelector('[data-form]').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = contexto.idSolicitud;
      api.avanzar('registrar_causacion', datos);
    });
  },
}));
