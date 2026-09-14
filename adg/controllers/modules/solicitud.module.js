/**
 * Módulo de creación de solicitud (paso 1). No está atado a un estado del
 * backend -- es el punto de entrada del flujo -- por eso expone su propio
 * abrirModal() en vez de registrarse en RegistroEstados.
 */
const ModuloSolicitud = (() => {

  const abrirModal = (refrescarBandeja) => {
    const fondo = document.createElement('div');
    fondo.className = 'fixed inset-0 bg-slate-900/40 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto';
    fondo.innerHTML = `
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 space-y-4 my-8">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-800">Nueva solicitud de gasto</h3>
          <button type="button" id="cerrar-modal-solicitud" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-nueva-solicitud" class="space-y-4" enctype="multipart/form-data">
          <div>
            <label class="text-sm text-slate-600">Descripción del gasto</label>
            <input name="descripcion" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label class="text-sm text-slate-600">Justificación</label>
            <textarea name="justificacion" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          </div>
          <div>
            <label class="text-sm text-slate-600">Valor estimado</label>
            <input type="number" min="0" step="0.01" name="valorEstimado" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="border-t border-slate-200 pt-4">
            <p class="text-sm font-medium text-slate-700 mb-2">Cotizaciones en PDF (mínimo 2 de 3, requisito para enviar)</p>
            <div class="grid sm:grid-cols-3 gap-3">
              <div>
                <label class="text-xs text-slate-500">Cotización 1 *</label>
                <input type="file" name="cotizacion1" accept="application/pdf" class="w-full text-xs">
              </div>
              <div>
                <label class="text-xs text-slate-500">Cotización 2 *</label>
                <input type="file" name="cotizacion2" accept="application/pdf" class="w-full text-xs">
              </div>
              <div>
                <label class="text-xs text-slate-500">Cotización 3</label>
                <input type="file" name="cotizacion3" accept="application/pdf" class="w-full text-xs">
              </div>
            </div>
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

    fondo.querySelector('#form-nueva-solicitud').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const form = evento.target;

      const archivosCargados = ['cotizacion1', 'cotizacion2', 'cotizacion3']
        .filter((campo) => form[campo].files.length > 0).length;

      if (archivosCargados < 2) {
        UI.toast('Debe adjuntar al menos dos cotizaciones en PDF.', 'error');
        return;
      }

      const datos = new FormData(form);
      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'crear_solicitud', datos)
        .then((resp) => {
          UI.toast(resp.mensaje, 'exito');
          cerrar();
          refrescarBandeja();
        })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  return { abrirModal };
})();
