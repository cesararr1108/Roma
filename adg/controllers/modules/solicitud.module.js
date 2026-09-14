/**
 * Módulo de creación de solicitud (los 3 tipos: cotización, factura,
 * anticipo). No está atado a un estado del backend -- es el punto de
 * entrada del flujo -- por eso expone su propio abrirModal() en vez de
 * registrarse en RegistroEstados. Reutiliza los fragmentos de factura y
 * anticipo (ver core/fragmentosFormulario.js) porque son los mismos
 * campos que se piden más adelante en el flujo.
 */
const ModuloSolicitud = (() => {

  const abrirModal = (contexto, refrescarBandeja) => {
    const fondo = document.createElement('div');
    fondo.className = 'fixed inset-0 bg-slate-900/40 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto';
    fondo.innerHTML = `
      <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-800">Nueva solicitud de gasto</h3>
          <button type="button" id="cerrar-modal-solicitud" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1" id="selector-tipo-gasto">
          <button type="button" data-tipo-gasto="1" class="px-4 py-2 text-sm rounded-md font-medium">Cotización</button>
          <button type="button" data-tipo-gasto="2" class="px-4 py-2 text-sm rounded-md font-medium">Factura de gasto</button>
          <button type="button" data-tipo-gasto="3" class="px-4 py-2 text-sm rounded-md font-medium">Anticipo</button>
        </div>

        <form id="form-nueva-solicitud" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="organizacionVentas" value="${contexto.usuario.organizacionVentas || ''}">
          <input type="hidden" name="oficinaVentas" value="${contexto.usuario.oficinaVentas || ''}">

          <div class="grid sm:grid-cols-2 gap-3">
            <div>
              <label class="text-sm text-slate-600">Concepto del gasto</label>
              <div class="flex gap-2">
                <select name="idConcepto" id="select-concepto" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></select>
                <button type="button" id="btn-nuevo-concepto" class="px-3 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">+</button>
              </div>
            </div>
            <div>
              <label class="text-sm text-slate-600">¿Requiere soporte de pago?</label>
              <select name="requiereSoporte" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          <div id="seccion-tipo-gasto" class="border-t border-slate-200 pt-4"></div>

          <div>
            <label class="text-sm text-slate-600">Comentario</label>
            <textarea name="comentario" rows="2" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" id="cancelar-nueva-solicitud" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Enviar solicitud</button>
          </div>
        </form>
      </div>`;
    document.body.appendChild(fondo);

    const cerrar = () => fondo.remove();
    fondo.querySelector('#cerrar-modal-solicitud').addEventListener('click', cerrar);
    fondo.querySelector('#cancelar-nueva-solicitud').addEventListener('click', cerrar);

    cargarConceptos(fondo);
    fondo.querySelector('#btn-nuevo-concepto').addEventListener('click', () => crearConcepto(fondo));

    let tipoGastoActivo = '1';
    const botones = fondo.querySelectorAll('[data-tipo-gasto]');
    const marcarBotonActivo = () => {
      botones.forEach((b) => {
        const activo = b.dataset.tipoGasto === tipoGastoActivo;
        b.classList.toggle('bg-indigo-600', activo);
        b.classList.toggle('text-white', activo);
        b.classList.toggle('text-slate-600', !activo);
      });
    };
    botones.forEach((boton) => {
      boton.addEventListener('click', () => {
        tipoGastoActivo = boton.dataset.tipoGasto;
        marcarBotonActivo();
        renderSeccionTipoGasto(fondo, tipoGastoActivo);
      });
    });
    marcarBotonActivo();
    renderSeccionTipoGasto(fondo, tipoGastoActivo);

    fondo.querySelector('#form-nueva-solicitud').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const form = evento.target;

      if (tipoGastoActivo === '1') {
        const cargadas = ['cotizacion1', 'cotizacion2', 'cotizacion3']
          .filter((campo) => form[campo] && form[campo].files.length > 0).length;
        if (cargadas < 2) {
          UI.toast('Debe adjuntar al menos dos cotizaciones en PDF.', 'error');
          return;
        }
      }

      const op = { '1': 'crear_solicitud_cotizacion', '2': 'crear_solicitud_factura', '3': 'crear_solicitud_anticipo' }[tipoGastoActivo];
      const datos = new FormData(form);

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, op, datos)
        .then((resp) => {
          UI.toast(resp.mensaje, 'exito');
          cerrar();
          refrescarBandeja();
        })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  const renderSeccionTipoGasto = (fondo, tipoGasto) => {
    const seccion = fondo.querySelector('#seccion-tipo-gasto');

    if (tipoGasto === '1') {
      seccion.innerHTML = `
        <p class="text-sm font-medium text-slate-700 mb-2">Cotizaciones en PDF (mínimo 2 de 3)</p>
        <div class="grid sm:grid-cols-3 gap-3">
          <div><label class="text-xs text-slate-500">Cotización 1 *</label><input type="file" name="cotizacion1" accept="application/pdf" class="w-full text-xs"></div>
          <div><label class="text-xs text-slate-500">Cotización 2 *</label><input type="file" name="cotizacion2" accept="application/pdf" class="w-full text-xs"></div>
          <div><label class="text-xs text-slate-500">Cotización 3</label><input type="file" name="cotizacion3" accept="application/pdf" class="w-full text-xs"></div>
        </div>`;
      return;
    }

    if (tipoGasto === '2') {
      seccion.innerHTML = Fragmentos.facturaHtml();
      Fragmentos.facturaBind(seccion);
      return;
    }

    seccion.innerHTML = Fragmentos.anticipoHtml();
    enviarPeticion(ControlGastosApi.LINK_MODELO, 'datos_usuario_actual', {})
      .then((resp) => Fragmentos.anticipoBind(seccion, resp.datos))
      .catch(() => Fragmentos.anticipoBind(seccion, null));
  };

  const cargarConceptos = (fondo) => {
    enviarPeticion(ControlGastosApi.LINK_MODELO, 'listar_conceptos', {})
      .then((resp) => pintarConceptos(fondo, resp.datos))
      .catch((err) => UI.toast(err.mensaje, 'error'));
  };

  const pintarConceptos = (fondo, conceptos) => {
    const select = fondo.querySelector('#select-concepto');
    select.innerHTML = conceptos.map((c) => `<option value="${c.ID}">${c.CONCEPTO}</option>`).join('');
  };

  const crearConcepto = async (fondo) => {
    const nombre = await UI.pedirTexto('Nuevo concepto de gasto', 'Nombre del concepto');
    if (!nombre) return;

    enviarPeticion(ControlGastosApi.LINK_MODELO, 'crear_concepto', { concepto: nombre })
      .then(() => { UI.toast('Concepto creado.', 'exito'); cargarConceptos(fondo); })
      .catch((err) => UI.toast(err.mensaje, 'error'));
  };

  return { abrirModal };
})();
