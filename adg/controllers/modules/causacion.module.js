/**
 * Módulo de causación (paso 1.3). Cubre los estados ANTICIPO_APROBADO y
 * FACTURA_REGISTRADA -- ambas ramas confluyen en el mismo paso, por eso
 * un único módulo se registra para los dos códigos de estado.
 * Responsable: Contabilidad.
 */
(function registrarModuloCausacion() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esContabilidad) {
      contenedor.innerHTML = PlantillaEspera('Contabilidad', 'debe registrar la causación.');
      return;
    }

    contenedor.innerHTML = `
      <form id="form-causacion" class="space-y-3">
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

    contenedor.querySelector('#form-causacion').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = solicitud.ID;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_causacion', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('ANTICIPO_APROBADO', { render });
  RegistroEstados.registrar('FACTURA_REGISTRADA', { render });
})();
