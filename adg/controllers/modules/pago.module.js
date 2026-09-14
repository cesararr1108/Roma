/**
 * Módulo del estado CAUSADO (paso 1.4). Responsable: Tesorería.
 */
(function registrarModuloPago() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esTesoreria) {
      contenedor.innerHTML = PlantillaEspera('Tesorería', 'debe registrar el pago.');
      return;
    }

    contenedor.innerHTML = `
      <form id="form-pago" class="space-y-3">
        <h4 class="font-semibold text-slate-800">Registrar pago</h4>
        <div>
          <label class="text-sm text-slate-600">Número de comprobante de pago</label>
          <input name="numeroComprobantePago" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar pago</button>
      </form>`;

    contenedor.querySelector('#form-pago').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = solicitud.ID;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_pago', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('CAUSADO', { render });
})();
