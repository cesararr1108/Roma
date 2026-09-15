/**
 * Fragmentos de formulario reutilizados en más de un punto del flujo:
 * los datos del beneficiario de un anticipo se piden igual al crear una
 * solicitud de anticipo y al pedir anticipo tras una cotización aprobada;
 * los datos de factura se piden igual al crear una factura directa, al
 * registrar factura tras cotización aprobada, y al legalizar un anticipo
 * de bienes/servicios.
 *
 * El formulario de "solicitud de viáticos" (F-FR-023 del sistema de
 * gestión de calidad) se mantiene TAL COMO ESTABA en el sistema anterior
 * -- mismos campos, mismo formato visual, misma validación reactiva
 * (se revalida todo el formulario en cada tecla, sin bloquear la
 * escritura, marcando en rojo lo que falte) -- solo que ahora vive en su
 * propio modal reutilizable en vez de duplicarse en cada punto del flujo
 * donde se pide un anticipo de viáticos.
 *
 * Cada fragmento expone html() (el marcado) y bind(contenedor) (el
 * cableado de eventos). El formulario que lo usa solo necesita
 * new FormData(form) -- los "name"/"data-v" de los campos ya coinciden
 * con los parámetros que espera cada op del backend.
 */
const Fragmentos = (() => {

  const anticipoHtml = () => `
    <div class="grid sm:grid-cols-2 gap-3">
      <div class="sm:col-span-2 flex items-center gap-2">
        <input type="checkbox" id="frag-a-mi-nombre" class="rounded text-indigo-600 focus:ring-indigo-500">
        <label for="frag-a-mi-nombre" class="text-sm text-slate-600">A mi nombre</label>
      </div>
      <div>
        <label class="text-sm text-slate-600">Tipo de anticipo</label>
        <select name="tipoAnticipo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-frag="tipoAnticipo">
          <option value="BIENES_SERVICIOS">Adquisición de bienes y servicios</option>
          <option value="VIATICOS">Legalización de viáticos (viaje)</option>
        </select>
      </div>
      <div>
        <label class="text-sm text-slate-600">Valor del anticipo</label>
        <input type="number" min="0" step="0.01" name="valorAnticipo" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Tipo de persona</label>
        <select name="tipoPersona" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-frag="tipoPersona">
          <option value="NATURAL">Natural</option>
          <option value="JURIDICA">Jurídica</option>
        </select>
      </div>
      <div data-frag="wrap-documento">
        <label class="text-sm text-slate-600">Documento de identidad</label>
        <input name="documentoIdentidad" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div data-frag="wrap-nit" class="hidden">
        <label class="text-sm text-slate-600">NIT</label>
        <input name="nitTercero" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Nombres / Razón comercial</label>
        <input name="nombreTercero" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Razón social (si aplica)</label>
        <input name="razonSocialTercero" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Código SAP</label>
        <input name="codigoSap" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Cargo</label>
        <input name="cargo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Centro de costos</label>
        <input name="centroCostos" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Celular</label>
        <input name="celular" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Correo</label>
        <input type="email" name="correo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
    </div>
    <div data-frag="wrap-viaticos" class="hidden pt-4 border-t border-slate-200 mt-4">
      <div class="flex items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-lg p-3">
        <div>
          <p class="text-sm font-medium text-slate-700">Formulario de solicitud de viáticos</p>
          <p class="text-xs text-slate-500">Formato F-FR-023 del sistema de gestión de calidad.</p>
        </div>
        <button type="button" data-frag="btn-diligenciar-viaticos" class="px-3 py-2 text-sm rounded-lg border whitespace-nowrap"></button>
      </div>
      <div data-frag="viaticos-hidden-inputs"></div>
    </div>`;

  const anticipoBind = (contenedor, datosUsuario) => {
    const selectTipoPersona = contenedor.querySelector('[data-frag="tipoPersona"]');
    const wrapDocumento = contenedor.querySelector('[data-frag="wrap-documento"]');
    const wrapNit = contenedor.querySelector('[data-frag="wrap-nit"]');

    const actualizarTipoPersona = () => {
      const esJuridica = selectTipoPersona.value === 'JURIDICA';
      wrapDocumento.classList.toggle('hidden', esJuridica);
      wrapNit.classList.toggle('hidden', !esJuridica);
    };
    selectTipoPersona.addEventListener('change', actualizarTipoPersona);
    actualizarTipoPersona();

    const selectTipoAnticipo = contenedor.querySelector('[data-frag="tipoAnticipo"]');
    const wrapViaticos = contenedor.querySelector('[data-frag="wrap-viaticos"]');
    const botonDiligenciar = contenedor.querySelector('[data-frag="btn-diligenciar-viaticos"]');
    const contenedorHidden = contenedor.querySelector('[data-frag="viaticos-hidden-inputs"]');

    let datosViaticosGuardados = null;

    const actualizarBotonDiligenciar = () => {
      if (datosViaticosGuardados) {
        botonDiligenciar.innerHTML = 'Editar formulario <span class="text-emerald-600">&#10003;</span>';
        botonDiligenciar.className = 'px-3 py-2 text-sm rounded-lg border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 whitespace-nowrap';
      } else {
        botonDiligenciar.innerHTML = 'Diligenciar formulario <span class="text-amber-600">&#9888;</span>';
        botonDiligenciar.className = 'px-3 py-2 text-sm rounded-lg border border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100 whitespace-nowrap';
      }
    };
    actualizarBotonDiligenciar();

    botonDiligenciar.addEventListener('click', () => {
      abrirModalViaticosSolicitud(datosUsuario, datosViaticosGuardados, (datos) => {
        datosViaticosGuardados = datos;
        contenedorHidden.innerHTML = Object.entries(datos).map(([clave, valor]) =>
          `<input type="hidden" name="${clave}" value="${String(valor).replace(/"/g, '&quot;')}">`
        ).join('');
        actualizarBotonDiligenciar();
      });
    });

    const actualizarTipoAnticipo = () => {
      wrapViaticos.classList.toggle('hidden', selectTipoAnticipo.value !== 'VIATICOS');
    };
    selectTipoAnticipo.addEventListener('change', actualizarTipoAnticipo);
    actualizarTipoAnticipo();

    contenedor.querySelector('#frag-a-mi-nombre').addEventListener('change', (evento) => {
      if (!evento.target.checked || !datosUsuario) return;
      contenedor.querySelector('[name="documentoIdentidad"]').value = datosUsuario.IDENTIFICACION || '';
      contenedor.querySelector('[name="nombreTercero"]').value = ((datosUsuario.NOMBRES || '') + ' ' + (datosUsuario.APELLIDOS || '')).trim();
      contenedor.querySelector('[name="razonSocialTercero"]').value = datosUsuario.RAZON_COMERCIAL || '';
      contenedor.querySelector('[name="codigoSap"]').value = datosUsuario.CODIGO_SAP || '';
      contenedor.querySelector('[name="celular"]').value = datosUsuario.CELULAR || '';
      contenedor.querySelector('[name="correo"]').value = datosUsuario.EMAIL || '';
      selectTipoPersona.value = 'NATURAL';
      actualizarTipoPersona();
    });
  };

  const facturaHtml = () => `
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">NIT del tercero</label>
        <input name="nit" data-frag="factura-nit" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        <p data-frag="factura-tercero-info" class="text-xs text-slate-500 mt-1"></p>
      </div>
      <div>
        <label class="text-sm text-slate-600">Número preliminar SAP</label>
        <input name="numeroPreliminarSap" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Número de factura</label>
        <input name="numeroFactura" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Fecha de factura</label>
        <input type="date" name="fechaFactura" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Fondo</label>
        <select name="idFondo" data-frag="factura-fondo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option value="1">Fondo proveedor</option>
          <option value="2">Nota proveedor</option>
          <option value="3">ROMA</option>
        </select>
      </div>
      <div data-frag="factura-numero-fondo-wrap" class="hidden">
        <label class="text-sm text-slate-600">Número de fondo</label>
        <input name="numeroFondo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Subtotal</label>
        <input type="number" step="0.01" min="0" name="subtotal" data-frag="factura-subtotal" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">IVA</label>
        <input type="number" step="0.01" min="0" name="iva" data-frag="factura-iva" value="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Total</label>
        <input type="text" data-frag="factura-total" disabled class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-sm font-medium">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm text-slate-600">Factura (PDF)</label>
        <input type="file" name="factura" accept="application/pdf" required class="w-full text-sm">
      </div>
    </div>`;

  const facturaBind = (contenedor) => {
    const recalcularTotal = () => {
      const subtotal = parseFloat(contenedor.querySelector('[data-frag="factura-subtotal"]').value) || 0;
      const iva = parseFloat(contenedor.querySelector('[data-frag="factura-iva"]').value) || 0;
      contenedor.querySelector('[data-frag="factura-total"]').value = Formato.moneda(subtotal + iva);
    };
    contenedor.querySelector('[data-frag="factura-subtotal"]').addEventListener('input', recalcularTotal);
    contenedor.querySelector('[data-frag="factura-iva"]').addEventListener('input', recalcularTotal);

    contenedor.querySelector('[data-frag="factura-fondo"]').addEventListener('change', (evento) => {
      contenedor.querySelector('[data-frag="factura-numero-fondo-wrap"]').classList.toggle('hidden', evento.target.value === '3');
    });

    let temporizadorNit;
    contenedor.querySelector('[data-frag="factura-nit"]').addEventListener('input', (evento) => {
      clearTimeout(temporizadorNit);
      const nit = evento.target.value.trim();
      const info = contenedor.querySelector('[data-frag="factura-tercero-info"]');
      if (nit.length < 3) { info.textContent = ''; return; }

      temporizadorNit = setTimeout(() => {
        enviarPeticion(ControlGastosApi.LINK_MODELO, 'buscar_tercero', { nit })
          .then((resp) => {
            const nombre = resp.datos.RAZON_COMERCIAL || resp.datos.NOMBRES;
            info.textContent = `${nombre} · SAP ${resp.datos.CODIGO_SAP}`;
            info.className = 'text-xs text-emerald-600 mt-1';
          })
          .catch(() => {
            info.textContent = 'Tercero no encontrado en la base de terceros.';
            info.className = 'text-xs text-red-600 mt-1';
          });
      }, 400);
    });
  };

  // ------------------------------------------------------------------
  // Formato F-FR-023 · Solicitud de viáticos
  // ------------------------------------------------------------------

  const BUDGET_ITEMS = [
    ['tiquetesAereos', 'Tiquetes aéreos'],
    ['tiquetesTerrestres', 'Tiquetes terrestres'],
    ['taxisBuses', 'Taxis y buses'],
    ['peajes', 'Peajes'],
    ['hospedaje', 'Hospedaje'],
    ['alimentacion', 'Alimentación'],
    ['flotasAcarreo', 'Flotas y acarreo'],
    ['viaticosAdmin', 'Viáticos administrativos'],
  ];

  const CAMPOS_REQUERIDOS_VIATICOS = [
    ['viaticosDependencia', 'Dependencia'],
    ['viaticosNombres', 'Nombres y apellidos'],
    ['viaticosIdentificacion', 'Documento de identidad'],
    ['viaticosCargo', 'Cargo'],
    ['viaticosTelefono', 'Teléfono fijo'],
    ['viaticosCentroCosto', 'Centro de costos'],
    ['viaticosEmail', 'Correo electrónico'],
    ['viaticosFechaSalida', 'Fecha de salida'],
    ['viaticosFechaRegreso', 'Fecha de regreso'],
    ['viaticosMotivo', 'Motivo de la solicitud'],
  ];

  let _tarifasCache = null;
  const obtenerTarifas = () => {
    if (_tarifasCache) return Promise.resolve(_tarifasCache);
    return enviarPeticion(ControlGastosApi.LINK_MODELO, 'cargar_datos', {}).then((resp) => {
      _tarifasCache = resp.datos.tarifasViaticos;
      return _tarifasCache;
    });
  };

  const abrirModalTarifas = (nivel) => {
    obtenerTarifas().then((tarifas) => {
      const t = tarifas[nivel] || null;
      const fondo = document.createElement('div');
      fondo.className = 'fixed inset-0 bg-slate-900/40 z-[80] flex items-center justify-center p-4';
      fondo.innerHTML = `
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Tarifas de viáticos${t ? ' — ' + t.nombre : ''}</h3>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
          </div>
          ${t ? `
          <dl class="text-sm divide-y divide-slate-100">
            <div class="flex justify-between py-1.5"><dt class="text-slate-500">Desayuno</dt><dd class="font-medium">${Formato.moneda(t.desayuno)}</dd></div>
            <div class="flex justify-between py-1.5"><dt class="text-slate-500">Almuerzo</dt><dd class="font-medium">${Formato.moneda(t.almuerzo)}</dd></div>
            <div class="flex justify-between py-1.5"><dt class="text-slate-500">Cena</dt><dd class="font-medium">${Formato.moneda(t.cena)}</dd></div>
            <div class="flex justify-between py-1.5 font-semibold"><dt>Total día</dt><dd>${Formato.moneda(t.total_dia)}</dd></div>
            <div class="flex justify-between py-1.5"><dt class="text-slate-500">Hotel</dt><dd class="font-medium">${Formato.moneda(t.hotel)}</dd></div>
            <div class="flex justify-between py-1.5"><dt class="text-slate-500">Hotel con desayuno</dt><dd class="font-medium">${Formato.moneda(t.hotel_con_desayuno)}</dd></div>
          </dl>
          <p class="text-xs text-slate-400">Tarifas de referencia, informativas.</p>
          ` : '<p class="text-sm text-slate-400">No hay una tarifa registrada para tu nivel.</p>'}
        </div>`;
      document.body.appendChild(fondo);
      fondo.querySelector('[data-cerrar]').addEventListener('click', () => fondo.remove());
    });
  };

  /**
   * @param {Object|null} datosUsuario   Resultado de 'datos_usuario_actual' (para prefill y nivel de tarifa).
   * @param {Object|null} valoresPrevios Datos ya guardados en una edición anterior (mismas claves que se envían al backend).
   * @param {Function} onGuardar         Recibe el objeto de datos ya validado, listo para volcarse en inputs ocultos.
   */
  const abrirModalViaticosSolicitud = (datosUsuario, valoresPrevios, onGuardar) => {
    const hoy = new Date();
    const dia = String(hoy.getDate()).padStart(2, '0');
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const anio = String(hoy.getFullYear());
    const minFecha = hoy.toISOString().slice(0, 10);
    const nivel = datosUsuario ? datosUsuario.NIVEL_VIATICOS : null;

    const v = valoresPrevios || {
      viaticosNombres: datosUsuario ? ((datosUsuario.NOMBRES || '') + ' ' + (datosUsuario.APELLIDOS || '')).trim() : '',
      viaticosIdentificacion: datosUsuario ? (datosUsuario.IDENTIFICACION || '') : '',
      viaticosCargo: datosUsuario ? (datosUsuario.CARGO || '') : '',
      viaticosTelefono: datosUsuario ? (datosUsuario.CELULAR || '') : '',
      viaticosEmail: datosUsuario ? (datosUsuario.EMAIL || '') : '',
    };

    const fondo = document.createElement('div');
    fondo.className = 'fixed inset-0 bg-slate-900/50 z-[60] flex items-start justify-center p-4 overflow-y-auto';
    fondo.innerHTML = `
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl my-6 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 bg-slate-50">
          <h3 class="font-semibold text-slate-800">Formato solicitud de viáticos</h3>
          <button type="button" data-cerrar class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="max-h-[75vh] overflow-y-auto">
          <div class="border-b border-slate-200 grid grid-cols-[1fr_170px]">
            <div class="bg-teal-700 text-white flex flex-col items-center justify-center py-4 px-4 text-center">
              <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-md bg-white/20 flex items-center justify-center font-extrabold text-xs">R</div>
                <span class="text-[11px] font-bold tracking-wide">ROMA S.A.</span>
              </div>
              <h1 class="text-[15px] font-extrabold">FORMATO SOLICITUD DE VIÁTICOS</h1>
            </div>
            <div class="text-[10.5px] divide-y divide-slate-200">
              <div class="px-2 py-1 flex justify-between"><span class="font-bold text-slate-500">Código:</span><span>F-FR-023</span></div>
              <div class="px-2 py-1 flex justify-between"><span class="font-bold text-slate-500">Versión:</span><span>1</span></div>
              <div class="px-2 py-1 flex justify-between"><span class="font-bold text-slate-500">Página:</span><span>1 de 1</span></div>
              <div class="px-2 py-1 flex justify-between"><span class="font-bold text-slate-500">Fecha:</span><span>${dia}/${mes}/${anio}</span></div>
            </div>
          </div>

          <div class="grid grid-cols-2 border-b border-slate-200">
            <div class="p-3 border-r border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha de solicitud</label>
              <div class="grid grid-cols-3 gap-1">
                <input type="text" value="${dia}" readonly class="border border-slate-200 bg-slate-50 rounded px-2 py-1 text-sm text-center">
                <input type="text" value="${mes}" readonly class="border border-slate-200 bg-slate-50 rounded px-2 py-1 text-sm text-center">
                <input type="text" value="${anio}" readonly class="border border-slate-200 bg-slate-50 rounded px-2 py-1 text-sm text-center">
              </div>
            </div>
            <div class="p-3">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Dependencia</label>
              <input data-v="viaticosDependencia" value="${v.viaticosDependencia || ''}" placeholder="Ej. SISTEMAS" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
          </div>

          <div class="bg-slate-100 text-[11px] font-bold uppercase tracking-wide px-4 py-1.5 text-slate-600">Datos de quien solicita</div>
          <div class="grid sm:grid-cols-2 border-b border-slate-200">
            <div class="p-3 sm:border-r border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombres y apellidos</label>
              <input data-v="viaticosNombres" value="${v.viaticosNombres || ''}" placeholder="Nombre completo" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Documento de identidad</label>
              <input data-v="viaticosIdentificacion" value="${v.viaticosIdentificacion || ''}" placeholder="N° cédula" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3 sm:border-r border-t border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Cargo</label>
              <input data-v="viaticosCargo" value="${v.viaticosCargo || ''}" placeholder="Ej. Gte de Sistemas" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3 border-t border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Teléfono fijo</label>
              <input data-v="viaticosTelefono" value="${v.viaticosTelefono || ''}" placeholder="Teléfono" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3 sm:border-r border-t border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Centro de costos</label>
              <input data-v="viaticosCentroCosto" value="${v.viaticosCentroCosto || ''}" placeholder="Ej. 2A005" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3 border-t border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Correo electrónico</label>
              <input type="email" data-v="viaticosEmail" value="${v.viaticosEmail || ''}" placeholder="correo@dfroma.com" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
          </div>

          <div class="bg-slate-100 text-[11px] font-bold uppercase tracking-wide px-4 py-1.5 text-slate-600">Motivo de la solicitud</div>
          <div class="p-3 border-b border-slate-200">
            <textarea data-v="viaticosMotivo" rows="2" placeholder="Ej. Evento comercial Bogotá" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm resize-none">${v.viaticosMotivo || ''}</textarea>
          </div>

          <div class="grid grid-cols-2 border-b border-slate-200">
            <div class="p-3 border-r border-slate-200">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha de salida</label>
              <input type="date" data-v="viaticosFechaSalida" data-fecha="1" min="${minFecha}" value="${v.viaticosFechaSalida || ''}" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
            <div class="p-3">
              <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha de regreso</label>
              <input type="date" data-v="viaticosFechaRegreso" data-fecha="1" min="${minFecha}" value="${v.viaticosFechaRegreso || ''}" class="w-full border border-slate-300 rounded px-2 py-1.5 text-sm">
            </div>
          </div>

          <div class="bg-slate-100 text-[11px] font-bold uppercase tracking-wide px-4 py-1.5 text-slate-600">Solicitud de pasajes</div>
          <div class="p-3 border-b border-slate-200 overflow-x-auto">
            <table class="w-full text-[11.5px] min-w-[420px]">
              <thead>
                <tr class="text-slate-500 text-[10px] uppercase">
                  <th class="text-left font-bold py-1 w-20">Ruta</th>
                  <th class="text-left font-bold py-1">Ciudad</th>
                  <th class="text-center font-bold py-1 w-16">Aéreo</th>
                  <th class="text-center font-bold py-1 w-20">Terrestre</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-t border-slate-100">
                  <td class="py-1.5 font-bold">Salida</td>
                  <td class="py-1.5"><input data-v="viaticosCiudadSalida" value="${v.viaticosCiudadSalida || ''}" placeholder="Ciudad de salida" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></td>
                  <td class="py-1.5 text-center"><input type="checkbox" data-v="viaticosSalidaAereo" ${v.viaticosSalidaAereo == 1 ? 'checked' : ''} class="w-4 h-4 accent-teal-600"></td>
                  <td class="py-1.5 text-center"><input type="checkbox" data-v="viaticosSalidaTerrestre" ${v.viaticosSalidaTerrestre == 1 ? 'checked' : ''} class="w-4 h-4 accent-teal-600"></td>
                </tr>
                <tr class="border-t border-slate-100">
                  <td class="py-1.5 font-bold">Regreso</td>
                  <td class="py-1.5"><input data-v="viaticosCiudadRegreso" value="${v.viaticosCiudadRegreso || ''}" placeholder="Ciudad de regreso" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></td>
                  <td class="py-1.5 text-center"><input type="checkbox" data-v="viaticosRegresoAereo" ${v.viaticosRegresoAereo == 1 ? 'checked' : ''} class="w-4 h-4 accent-teal-600"></td>
                  <td class="py-1.5 text-center"><input type="checkbox" data-v="viaticosRegresoTerrestre" ${v.viaticosRegresoTerrestre == 1 ? 'checked' : ''} class="w-4 h-4 accent-teal-600"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="bg-slate-100 text-[11px] font-bold uppercase tracking-wide px-4 py-1.5 text-slate-600 flex items-center justify-between">
            <span>Valores presupuestados</span>
            <button type="button" data-tarifas class="text-[10.5px] normal-case font-medium text-teal-700 hover:underline">Ver tarifas de referencia</button>
          </div>
          <div class="p-3 border-b border-slate-200">
            <table class="w-full text-xs">
              <tbody data-budget-body></tbody>
              <tr class="bg-teal-50 border-t-2 border-teal-600 font-bold">
                <td class="px-2 py-2">Total solicitado</td>
                <td class="px-2 py-2 text-right text-teal-700 w-32" data-total>$0</td>
              </tr>
            </table>
          </div>

          <div class="grid sm:grid-cols-2">
            <div class="p-4 text-center sm:border-r border-slate-200">
              <p class="text-[10px] font-bold uppercase text-slate-400 mb-6">Firma del colaborador</p>
              <div class="border-b border-slate-300 mb-1 h-6"></div>
              <p class="text-[10px] text-slate-400">Nombre y fecha</p>
            </div>
            <div class="p-4 text-center">
              <p class="text-[10px] font-bold uppercase text-slate-400 mb-6">Firma del jefe inmediato</p>
              <div class="border-b border-slate-300 mb-1 h-6"></div>
              <p class="text-[10px] text-slate-400">Nombre y fecha</p>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between px-5 py-3 border-t border-slate-200 bg-slate-50">
          <button type="button" data-tarifas class="text-sm text-teal-700 hover:underline">Tabla de viáticos</button>
          <button type="button" data-guardar class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar datos</button>
        </div>
      </div>`;
    document.body.appendChild(fondo);

    const budgetBody = fondo.querySelector('[data-budget-body]');
    BUDGET_ITEMS.forEach(([campo, etiqueta]) => {
      const fila = document.createElement('tr');
      fila.className = 'border-t border-slate-100';
      fila.innerHTML = `
        <td class="px-2 py-1.5">${etiqueta}</td>
        <td class="px-2 py-1.5 w-32"><input type="number" min="0" step="1" data-v="${campo}" data-budget="1" value="${v[campo] || 0}" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></td>`;
      budgetBody.appendChild(fila);
    });
    const filaOtro = document.createElement('tr');
    filaOtro.className = 'border-t border-slate-100';
    filaOtro.innerHTML = `
      <td class="px-2 py-1.5">Otro: <input data-v="descripcionOtros" value="${v.descripcionOtros || ''}" placeholder="¿cuál?" class="border border-slate-300 rounded px-2 py-1 text-sm ml-1 w-40 inline-block"></td>
      <td class="px-2 py-1.5 w-32"><input type="number" min="0" step="1" data-v="otros" data-budget="1" value="${v.otros || 0}" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></td>`;
    budgetBody.appendChild(filaOtro);

    const marcarError = (el, esValido) => {
      el.classList.toggle('border-red-500', !esValido);
      el.classList.toggle('ring-1', !esValido);
      el.classList.toggle('ring-red-500', !esValido);
    };

    const recalcularTotal = () => {
      let total = 0;
      fondo.querySelectorAll('[data-budget]').forEach((input) => { total += parseFloat(input.value) || 0; });
      fondo.querySelector('[data-total]').textContent = Formato.moneda(total);
    };

    // Revalida TODO el formulario -- igual que validarFormularioViaticos() en el sistema anterior,
    // que se disparaba con cada tecla en cualquier input/textarea del formulario.
    const revalidar = () => {
      const errores = [];

      CAMPOS_REQUERIDOS_VIATICOS.forEach(([campo, etiqueta]) => {
        const el = fondo.querySelector(`[data-v="${campo}"]`);
        const ok = el.value.trim().length > 0;
        marcarError(el, ok);
        if (!ok) errores.push(`El campo "${etiqueta}" es obligatorio.`);
      });

      const emailEl = fondo.querySelector('[data-v="viaticosEmail"]');
      if (emailEl.value.trim()) {
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim());
        marcarError(emailEl, emailOk);
        if (!emailOk) errores.push('El correo electrónico no tiene un formato válido.');
      }

      fondo.querySelectorAll('[data-fecha]').forEach((el) => {
        const ok = el.value.trim() !== '' && !isNaN(Date.parse(el.value.trim()));
        marcarError(el, ok);
        if (!ok) errores.push('Una de las fechas no tiene un formato válido.');
      });

      let sumaSinPeajes = 0;
      fondo.querySelectorAll('[data-budget]').forEach((input) => {
        if (input.dataset.v !== 'peajes') sumaSinPeajes += parseFloat(input.value) || 0;
      });
      const tablaPresupuesto = budgetBody.closest('table');
      if (sumaSinPeajes <= 0) {
        tablaPresupuesto.classList.add('ring-1', 'ring-red-500');
        errores.push('Debe ingresar al menos un valor presupuestado mayor a 0 (los peajes no son obligatorios).');
      } else {
        tablaPresupuesto.classList.remove('ring-1', 'ring-red-500');
      }

      return errores;
    };

    fondo.querySelectorAll('input, textarea').forEach((el) => {
      el.addEventListener('input', () => {
        recalcularTotal();
        revalidar();
      });
    });
    recalcularTotal();

    fondo.querySelectorAll('[data-tarifas]').forEach((boton) => {
      boton.addEventListener('click', () => abrirModalTarifas(nivel));
    });

    const cerrar = () => fondo.remove();
    fondo.querySelector('[data-cerrar]').addEventListener('click', cerrar);

    fondo.querySelector('[data-guardar]').addEventListener('click', () => {
      const errores = revalidar();
      if (errores.length > 0) {
        UI.toast(errores[0] + (errores.length > 1 ? ` (+${errores.length - 1} más)` : ''), 'error');
        return;
      }

      const leer = (campo) => fondo.querySelector(`[data-v="${campo}"]`).value.trim();
      const leerCheckbox = (campo) => fondo.querySelector(`[data-v="${campo}"]`).checked ? 1 : 0;

      const datos = {
        viaticosDependencia: leer('viaticosDependencia'),
        viaticosNombres: leer('viaticosNombres'),
        viaticosIdentificacion: leer('viaticosIdentificacion'),
        viaticosCargo: leer('viaticosCargo'),
        viaticosTelefono: leer('viaticosTelefono'),
        viaticosCentroCosto: leer('viaticosCentroCosto'),
        viaticosEmail: leer('viaticosEmail'),
        viaticosMotivo: leer('viaticosMotivo'),
        viaticosFechaSalida: leer('viaticosFechaSalida'),
        viaticosFechaRegreso: leer('viaticosFechaRegreso'),
        viaticosCiudadSalida: leer('viaticosCiudadSalida'),
        viaticosSalidaAereo: leerCheckbox('viaticosSalidaAereo'),
        viaticosSalidaTerrestre: leerCheckbox('viaticosSalidaTerrestre'),
        viaticosCiudadRegreso: leer('viaticosCiudadRegreso'),
        viaticosRegresoAereo: leerCheckbox('viaticosRegresoAereo'),
        viaticosRegresoTerrestre: leerCheckbox('viaticosRegresoTerrestre'),
        descripcionOtros: leer('descripcionOtros'),
      };
      BUDGET_ITEMS.forEach(([campo]) => { datos[campo] = fondo.querySelector(`[data-v="${campo}"]`).value || 0; });
      datos.otros = fondo.querySelector('[data-v="otros"]').value || 0;

      cerrar();
      onGuardar(datos);
    });
  };

  return {
    anticipoHtml, anticipoBind,
    facturaHtml, facturaBind,
    abrirModalViaticosSolicitud,
    abrirModalTarifas,
  };
})();
