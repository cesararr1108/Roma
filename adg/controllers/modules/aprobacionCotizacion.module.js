/**
 * Módulo del estado EN_APROBACION_COTIZACION (paso 1.1).
 * Responsable: Gerencia Administrativa.
 */
(function registrarModuloAprobacionCotizacion() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esGerenciaAdministrativa) {
      contenedor.innerHTML = PlantillaEspera('Gerencia Administrativa', 'debe seleccionar la cotización aprobada o rechazar la solicitud.');
      return;
    }

    const cotizaciones = solicitud.cotizaciones || [];
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
        <div class="grid gap-2" id="lista-cotizaciones">${opciones}</div>
        <div class="flex flex-wrap gap-2 pt-2">
          <button type="button" id="btn-aprobar-cotizacion" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar cotización seleccionada</button>
          <button type="button" id="btn-rechazar-cotizacion" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar solicitud</button>
        </div>
      </div>`;

    contenedor.querySelector('#btn-aprobar-cotizacion').addEventListener('click', () => {
      const seleccionada = contenedor.querySelector('input[name="cotizacionAprobada"]:checked');
      if (!seleccionada) {
        UI.toast('Seleccione la cotización a aprobar.', 'error');
        return;
      }
      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'aprobar_cotizacion', {
        idSolicitud: solicitud.ID,
        idCotizacionAprobada: seleccionada.value,
      })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });

    contenedor.querySelector('#btn-rechazar-cotizacion').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar solicitud', 'Motivo del rechazo');
      if (!motivo) return;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'rechazar_cotizacion', {
        idSolicitud: solicitud.ID,
        motivo,
      })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('EN_APROBACION_COTIZACION', { render });
})();
