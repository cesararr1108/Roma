/**
 * Fragmentos de formulario reutilizados en más de un punto del flujo:
 * los datos del beneficiario de un anticipo se piden igual al crear una
 * solicitud de anticipo y al pedir anticipo tras una cotización aprobada;
 * los datos de factura se piden igual al crear una factura directa, al
 * registrar factura tras cotización aprobada, y al legalizar un anticipo
 * de bienes/servicios; el formulario de viáticos se pide igual sin
 * importar si la solicitud nació como anticipo o viene de una cotización.
 *
 * Cada fragmento expone html() (el marcado) y bind(contenedor) (el
 * cableado de eventos). El formulario que lo usa solo necesita
 * new FormData(form) -- los "name" de los campos ya coinciden con los
 * parámetros que espera cada op del backend.
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
    <div data-frag="wrap-viaticos" class="hidden pt-4 border-t border-slate-200 mt-4"></div>`;

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

    const actualizarTipoAnticipo = () => {
      const esViaticos = selectTipoAnticipo.value === 'VIATICOS';
      wrapViaticos.classList.toggle('hidden', !esViaticos);
      if (esViaticos && wrapViaticos.innerHTML.trim() === '') {
        wrapViaticos.innerHTML = viaticosSolicitudHtml();
        viaticosSolicitudBind(wrapViaticos);
      }
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

  const viaticosSolicitudHtml = () => `
    <p class="text-sm font-medium text-slate-700 mb-2">Formulario de solicitud de viáticos</p>
    <div class="grid sm:grid-cols-2 gap-3">
      <div>
        <label class="text-sm text-slate-600">Nombres y apellidos</label>
        <input name="viaticosNombres" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Identificación</label>
        <input name="viaticosIdentificacion" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Cargo</label>
        <input name="viaticosCargo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Teléfono</label>
        <input name="viaticosTelefono" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Centro de costo</label>
        <input name="viaticosCentroCosto" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Email</label>
        <input type="email" name="viaticosEmail" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Dependencia</label>
        <input name="viaticosDependencia" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div></div>
      <div>
        <label class="text-sm text-slate-600">Fecha de salida</label>
        <input type="date" name="viaticosFechaSalida" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm text-slate-600">Fecha de regreso</label>
        <input type="date" name="viaticosFechaRegreso" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm text-slate-600">Motivo del viaje</label>
        <textarea name="viaticosMotivo" rows="2" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>
    </div>

    <p class="text-sm font-medium text-slate-700 mt-4 mb-2">Presupuesto del viaje</p>
    <div class="grid sm:grid-cols-3 gap-3">
      ${['tiquetesAereos:Tiquetes aéreos', 'tiquetesTerrestres:Tiquetes terrestres', 'taxisBuses:Taxis y buses',
         'peajes:Peajes', 'hospedaje:Hospedaje', 'alimentacion:Alimentación',
         'flotasAcarreo:Flotas y acarreo', 'viaticosAdmin:Viáticos administrativos'].map((par) => {
        const [campo, etiqueta] = par.split(':');
        return `<div>
          <label class="text-xs text-slate-500">${etiqueta}</label>
          <input type="number" min="0" step="0.01" name="${campo}" value="0" data-frag="rubro-viatico" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>`;
      }).join('')}
      <div>
        <label class="text-xs text-slate-500">Otros</label>
        <input type="number" min="0" step="0.01" name="otros" value="0" data-frag="rubro-viatico" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div class="sm:col-span-2">
        <label class="text-xs text-slate-500">Descripción de "Otros"</label>
        <input name="descripcionOtros" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-xs text-slate-500 font-medium">Total presupuestado</label>
        <input type="text" data-frag="viaticos-total" disabled class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-sm font-medium">
      </div>
    </div>`;

  const viaticosSolicitudBind = (contenedor) => {
    const recalcular = () => {
      let total = 0;
      contenedor.querySelectorAll('[data-frag="rubro-viatico"]').forEach((input) => {
        total += parseFloat(input.value) || 0;
      });
      contenedor.querySelector('[data-frag="viaticos-total"]').value = Formato.moneda(total);
    };
    contenedor.querySelectorAll('[data-frag="rubro-viatico"]').forEach((input) => {
      input.addEventListener('input', recalcular);
    });
    recalcular();
  };

  return {
    anticipoHtml, anticipoBind,
    facturaHtml, facturaBind,
    viaticosSolicitudHtml, viaticosSolicitudBind,
  };
})();
