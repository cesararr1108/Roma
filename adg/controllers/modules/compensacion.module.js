/**
 * Módulo del estado PAGADO (paso 1.5, solo rama anticipo). Responsable: Contabilidad.
 */
(function registrarModuloCompensacion() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esContabilidad) {
      contenedor.innerHTML = PlantillaEspera('Contabilidad', 'debe registrar la compensación del anticipo en SAP.');
      return;
    }

    contenedor.innerHTML = `
      <form id="form-compensacion" class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar compensación</h4>
        <div>
          <label class="text-sm text-slate-600">Número de compensación (SAP)</label>
          <input name="numeroCompensacion" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Finalizar flujo</button>
      </form>`;

    contenedor.querySelector('#form-compensacion').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = solicitud.ID;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_compensacion', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('PAGADO', { render });
})();
