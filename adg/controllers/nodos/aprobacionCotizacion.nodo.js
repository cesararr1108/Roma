/**
 * Nodo "aprobacion_cotizacion": gerencia administrativa elige la
 * cotización ganadora o rechaza la solicitud.
 */
registrarNodo(new Nodo({
  id: 'aprobacion_cotizacion',
  nombre: 'Aprobación de cotización',
  rolesEditar: ROLES.GERENCIA_ADMINISTRATIVA,
  siguiente: (contexto) => (contexto.cotizacionAprobada ? 'decision_anticipo_factura' : 'rechazado_cotizacion'),

  render(contenedor, contexto, api) {
    const cotizaciones = contexto.cotizaciones || [];
    const opciones = cotizaciones.map((c) => `
      <label class="flex items-center gap-3 border border-slate-200 rounded-lg p-3 hover:border-indigo-400 cursor-pointer">
        <input type="radio" name="cotizacionAprobada" value="${c.ID}" class="text-indigo-600 focus:ring-indigo-500">
        <div class="flex-1">
          <p class="font-medium text-slate-700 text-sm">Cotización ${c.CONSECUTIVO}</p>
          <a href="${c.RUTA_ARCHIVO}" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:underline">${c.NOMBRE_ARCHIVO}</a>
        </div>
      </label>`).join('');

    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Aprobación de cotización</h4>
        <div class="grid gap-2">${opciones}</div>
        <div class="flex flex-wrap gap-2 pt-2">
          <button type="button" data-aprobar class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar cotización seleccionada</button>
          <button type="button" data-rechazar class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar solicitud</button>
        </div>
      </div>`;

    contenedor.querySelector('[data-aprobar]').addEventListener('click', () => {
      const seleccionada = contenedor.querySelector('input[name="cotizacionAprobada"]:checked');
      if (!seleccionada) { UI.toast('Seleccione la cotización a aprobar.', 'error'); return; }
      api.avanzar('aprobar_cotizacion', { idSolicitud: contexto.idSolicitud, idCotizacionAprobada: seleccionada.value });
    });

    contenedor.querySelector('[data-rechazar]').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar solicitud', 'Motivo del rechazo');
      if (!motivo) return;
      api.avanzar('rechazar_cotizacion', { idSolicitud: contexto.idSolicitud, motivo });
    });
  },
}));
