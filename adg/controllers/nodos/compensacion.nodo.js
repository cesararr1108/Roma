/**
 * Nodo "compensacion" (solo rama anticipo, tras legalización aprobada):
 * contabilidad registra el número de compensación generado en SAP.
 */
registrarNodo(new Nodo({
  id: 'compensacion',
  nombre: 'Compensación',
  rolesEditar: ROLES.CONTABILIDAD,
  siguiente: 'finalizado',

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <form data-form class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar compensación</h4>
        <div>
          <label class="text-sm text-slate-600">Número de compensación (SAP)</label>
          <input name="numeroCompensacion" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Finalizar flujo</button>
      </form>`;

    contenedor.querySelector('[data-form]').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = contexto.idSolicitud;
      api.avanzar('registrar_compensacion', datos);
    });
  },
}));
