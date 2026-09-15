/**
 * Nodo "pago": tesorería registra el pago. El comprobante solo se pide
 * si la solicitud se marcó como REQUIERE_SOPORTE al crearla.
 */
registrarNodo(new Nodo({
  id: 'pago',
  nombre: 'Pago',
  rolesEditar: ROLES.TESORERIA,
  siguiente: (contexto) => (contexto.requiereAnticipo ? 'legalizacion' : 'cruzado'),

  render(contenedor, contexto, api) {
    const requiereSoporte = Number(contexto.requiereSoporte) === 1;

    contenedor.innerHTML = `
      <form data-form enctype="multipart/form-data" class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar pago</h4>
        <div>
          <label class="text-sm text-slate-600">Número de comprobante de pago</label>
          <input name="numeroComprobantePago" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        ${requiereSoporte ? `
          <div>
            <label class="text-sm text-slate-600">Soporte de pago (PDF, JPG o PNG)</label>
            <input type="file" name="adjuntoPago" accept="application/pdf,image/jpeg,image/png" required class="w-full text-sm">
          </div>` : `
          <p class="text-xs text-slate-400">Esta solicitud no requiere soporte de pago.</p>`}
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar pago</button>
      </form>`;

    contenedor.querySelector('[data-form]').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = new FormData(evento.target);
      datos.append('idSolicitud', contexto.idSolicitud);
      api.avanzar('registrar_pago', datos);
    });
  },
}));
