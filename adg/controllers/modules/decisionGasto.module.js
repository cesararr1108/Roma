/**
 * Módulo del estado COTIZACION_APROBADA (paso 1.2).
 * Responsable: el solicitante (dueño de la solicitud), quien decide si
 * requiere anticipo o si registra directamente la factura del tercero.
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
        <div id="panel-decision-gasto" class="pt-2"></div>
      </div>`;

    contenedor.querySelector('#btn-ir-anticipo').addEventListener('click', () => renderFormularioAnticipo(contenedor, solicitud, refrescar));
    contenedor.querySelector('#btn-ir-factura').addEventListener('click', () => renderFormularioFactura(contenedor, solicitud, refrescar));
  };

  const renderFormularioAnticipo = (contenedor, solicitud, refrescar) => {
    const panel = contenedor.querySelector('#panel-decision-gasto');
    panel.innerHTML = `
      <form id="form-anticipo" class="space-y-3 border-t border-slate-200 pt-4">
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-slate-600">¿A nombre de quién?</label>
            <select name="beneficiario" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
              <option value="PROPIO">Propio (solicitante)</option>
              <option value="TERCERO">Un tercero</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-slate-600">Tipo de persona</label>
            <select name="tipoPersona" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
              <option value="NATURAL">Natural</option>
              <option value="JURIDICA">Jurídica</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-slate-600">NIT</label>
            <input name="nit" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">Nombres / Razón comercial</label>
            <input name="nombres" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">Teléfono</label>
            <input name="telefono" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">Email</label>
            <input type="email" name="email" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Enviar a aprobación</button>
      </form>`;

    panel.querySelector('#form-anticipo').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(evento.target).entries());
      datos.idSolicitud = solicitud.ID;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_datos_anticipo', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  const renderFormularioFactura = (contenedor, solicitud, refrescar) => {
    const panel = contenedor.querySelector('#panel-decision-gasto');
    panel.innerHTML = `
      <form id="form-factura" class="space-y-3 border-t border-slate-200 pt-4" enctype="multipart/form-data">
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-slate-600">NIT del tercero</label>
            <input name="nit" id="factura-nit" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <p id="factura-tercero-info" class="text-xs text-slate-500 mt-1"></p>
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
            <select name="idFondo" id="factura-fondo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
              <option value="1">ROMA</option>
              <option value="2">Fondo proveedor</option>
              <option value="3">Nota proveedor</option>
            </select>
          </div>
          <div id="factura-numero-fondo-wrap" class="hidden">
            <label class="text-sm text-slate-600">Número de fondo</label>
            <input name="numeroFondo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">Subtotal</label>
            <input type="number" step="0.01" min="0" name="subtotal" id="factura-subtotal" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">IVA</label>
            <input type="number" step="0.01" min="0" name="iva" id="factura-iva" value="0" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="text-sm text-slate-600">Total</label>
            <input type="text" id="factura-total" disabled class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-sm font-medium">
          </div>
          <div class="sm:col-span-2">
            <label class="text-sm text-slate-600">Factura (PDF)</label>
            <input type="file" name="factura" accept="application/pdf" required class="w-full text-sm">
          </div>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Registrar factura</button>
      </form>`;

    const recalcularTotal = () => {
      const subtotal = parseFloat(panel.querySelector('#factura-subtotal').value) || 0;
      const iva = parseFloat(panel.querySelector('#factura-iva').value) || 0;
      panel.querySelector('#factura-total').value = Formato.moneda(subtotal + iva);
    };
    panel.querySelector('#factura-subtotal').addEventListener('input', recalcularTotal);
    panel.querySelector('#factura-iva').addEventListener('input', recalcularTotal);

    panel.querySelector('#factura-fondo').addEventListener('change', (evento) => {
      panel.querySelector('#factura-numero-fondo-wrap').classList.toggle('hidden', evento.target.value === '1');
    });

    let temporizadorNit;
    panel.querySelector('#factura-nit').addEventListener('input', (evento) => {
      clearTimeout(temporizadorNit);
      const nit = evento.target.value.trim();
      const info = panel.querySelector('#factura-tercero-info');
      if (nit.length < 3) { info.textContent = ''; return; }

      temporizadorNit = setTimeout(() => {
        enviarPeticion(ControlGastosApi.LINK_MODELO, 'buscar_tercero', { nit })
          .then((resp) => {
            info.textContent = `${resp.datos.NOMBRES} · SAP ${resp.datos.CODIGO_SAP}`;
            info.className = 'text-xs text-emerald-600 mt-1';
          })
          .catch(() => {
            info.textContent = 'Tercero no encontrado en la base de terceros.';
            info.className = 'text-xs text-red-600 mt-1';
          });
      }, 400);
    });

    panel.querySelector('#form-factura').addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = new FormData(evento.target);
      datos.append('idSolicitud', solicitud.ID);

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_factura', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('COTIZACION_APROBADA', { render });
})();
