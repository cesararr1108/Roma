/**
 * Módulo del estado COTIZACION_APROBADA. Responsable: el solicitante
 * (dueño de la solicitud), quien decide si requiere anticipo o si
 * registra directamente la factura del tercero. Reutiliza los mismos
 * fragmentos de formulario que la creación de solicitud (ver
 * core/fragmentosFormulario.js), porque piden exactamente los mismos
 * campos.
 */
(function registrarModuloDecisionGasto() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    const esDueno = Number(solicitud.ID_USUARIO_SOLICITA) === Number(usuario.id);

    if (!esDueno) {
      contenedor.innerHTML = PlantillaEspera('El solicitante', 'debe indicar si requiere anticipo o registrar directamente la factura.');
      return;
    }

    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Cotización aprobada — defina cómo continuar</h4>
        <div class="grid sm:grid-cols-2 gap-3">
          <button type="button" id="btn-ir-anticipo" class="border-2 border-indigo-200 hover:border-indigo-500 rounded-xl p-4 text-left transition">
            <p class="font-medium text-indigo-700 text-sm">Solicitar anticipo</p>
            <p class="text-xs text-slate-500 mt-1">A mi nombre o a un tercero. Requiere aprobación de Gerencia Administrativa.</p>
          </button>
          <button type="button" id="btn-ir-factura" class="border-2 border-slate-200 hover:border-slate-400 rounded-xl p-4 text-left transition">
            <p class="font-medium text-slate-700 text-sm">Registrar factura</p>
            <p class="text-xs text-slate-500 mt-1">Ya cuento con la factura del tercero, continúo sin anticipo.</p>
          </button>
        </div>
        <form id="form-decision-gasto" class="pt-2 space-y-3" enctype="multipart/form-data"></form>
        <button type="submit" form="form-decision-gasto" id="btn-enviar-decision" class="hidden px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Enviar</button>
      </div>`;

    contenedor.querySelector('#btn-ir-anticipo').addEventListener('click', () => activarRama(contenedor, solicitud, refrescar, 'anticipo'));
    contenedor.querySelector('#btn-ir-factura').addEventListener('click', () => activarRama(contenedor, solicitud, refrescar, 'factura'));
  };

  const activarRama = (contenedor, solicitud, refrescar, rama) => {
    const form = contenedor.querySelector('#form-decision-gasto');
    const botonEnviar = contenedor.querySelector('#btn-enviar-decision');
    botonEnviar.classList.remove('hidden');

    if (rama === 'anticipo') {
      form.innerHTML = Fragmentos.anticipoHtml();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'datos_usuario_actual', {})
        .then((resp) => Fragmentos.anticipoBind(form, resp.datos))
        .catch(() => Fragmentos.anticipoBind(form, null));

      form.onsubmit = (evento) => {
        evento.preventDefault();
        const datos = Object.fromEntries(new FormData(form).entries());
        datos.idSolicitud = solicitud.ID;
        enviar(datos, 'registrar_datos_anticipo', refrescar);
      };
    } else {
      form.innerHTML = Fragmentos.facturaHtml();
      Fragmentos.facturaBind(form);

      form.onsubmit = (evento) => {
        evento.preventDefault();
        const datos = new FormData(form);
        datos.append('idSolicitud', solicitud.ID);
        enviar(datos, 'registrar_factura', refrescar);
      };
    }
  };

  const enviar = (datos, op, refrescar) => {
    UI.mostrarCargando();
    enviarPeticion(ControlGastosApi.LINK_MODELO, op, datos)
      .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
      .catch((err) => UI.toast(err.mensaje, 'error'))
      .finally(() => UI.ocultarCargando());
  };

  RegistroEstados.registrar('COTIZACION_APROBADA', { render });
})();
