/** Utilidades de UI reutilizables: toasts, overlay de carga, badges y modal de texto. */
const UI = (() => {

  const contenedorToast = () => {
    let el = document.getElementById('gastos-toast-contenedor');
    if (!el) {
      el = document.createElement('div');
      el.id = 'gastos-toast-contenedor';
      el.className = 'fixed top-4 right-4 z-[60] flex flex-col gap-2 w-80';
      document.body.appendChild(el);
    }
    return el;
  };

  const toast = (mensaje, tipo = 'info') => {
    const estilos = {
      exito: 'bg-emerald-50 border-emerald-300 text-emerald-800',
      error: 'bg-red-50 border-red-300 text-red-800',
      info: 'bg-blue-50 border-blue-300 text-blue-800',
    };
    const div = document.createElement('div');
    div.className = `border rounded-lg shadow-sm px-4 py-3 text-sm ${estilos[tipo] || estilos.info}`;
    div.style.animation = 'fadeIn .2s ease-out';
    div.textContent = mensaje;
    contenedorToast().appendChild(div);
    setTimeout(() => div.remove(), 4500);
  };

  let contadorCargas = 0;
  const mostrarCargando = () => {
    contadorCargas++;
    let overlay = document.getElementById('gastos-loading-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'gastos-loading-overlay';
      overlay.className = 'fixed inset-0 bg-slate-900/30 z-50 flex items-center justify-center hidden';
      overlay.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg px-6 py-4 flex items-center gap-3">
          <div class="w-5 h-5 border-2 border-slate-300 border-t-indigo-600 rounded-full animate-spin"></div>
          <span class="text-sm text-slate-600">Procesando...</span>
        </div>`;
      document.body.appendChild(overlay);
    }
    overlay.classList.remove('hidden');
  };

  const ocultarCargando = () => {
    contadorCargas = Math.max(0, contadorCargas - 1);
    if (contadorCargas === 0) {
      const overlay = document.getElementById('gastos-loading-overlay');
      if (overlay) overlay.classList.add('hidden');
    }
  };

  const badgeEstado = (estadoInfo) => {
    const color = (estadoInfo && estadoInfo.color) || 'slate';
    const nombre = estadoInfo ? estadoInfo.nombre : 'Desconocido';
    return `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-${color}-100 text-${color}-700 border border-${color}-200">${nombre}</span>`;
  };

  /** Modal simple para pedir un texto obligatorio (p. ej. motivo de rechazo). */
  const pedirTexto = (titulo, etiqueta = 'Motivo') => new Promise((resolve) => {
    const fondo = document.createElement('div');
    fondo.className = 'fixed inset-0 bg-slate-900/40 z-[70] flex items-center justify-center p-4';
    fondo.innerHTML = `
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
        <h3 class="font-semibold text-slate-800">${titulo}</h3>
        <div>
          <label class="text-sm text-slate-600 mb-1 block">${etiqueta}</label>
          <textarea id="ui-texto-motivo" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="ui-texto-cancelar" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">Cancelar</button>
          <button type="button" id="ui-texto-aceptar" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Confirmar</button>
        </div>
      </div>`;
    document.body.appendChild(fondo);

    fondo.querySelector('#ui-texto-cancelar').addEventListener('click', () => { fondo.remove(); resolve(null); });
    fondo.querySelector('#ui-texto-aceptar').addEventListener('click', () => {
      const valor = fondo.querySelector('#ui-texto-motivo').value.trim();
      if (!valor) return;
      fondo.remove();
      resolve(valor);
    });
  });

  return { toast, mostrarCargando, ocultarCargando, badgeEstado, pedirTexto };
})();
