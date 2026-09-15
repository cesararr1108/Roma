/**
 * Módulo del estado ANTICIPO_APROBADO: el solicitante debe legalizar el
 * anticipo ya aprobado. Si es de bienes/servicios, registra una factura
 * (mismo fragmento que en el resto del flujo). Si es de viáticos, debe
 * diligenciar el formato de legalización (F-FR-024 del sistema de
 * gestión de calidad) -- mismos campos, misma tabla de detalle y mismas
 * reglas de validación que el sistema anterior. La única diferencia real
 * es que ahí "+ Agregar fila" y los totales nunca quedaron conectados:
 * aquí sí funcionan.
 * Responsable: el solicitante (dueño de la solicitud).
 */
(function registrarModuloLegalizarAnticipo() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    const esDueno = Number(solicitud.ID_USUARIO_SOLICITA) === Number(usuario.id);

    if (!esDueno) {
      const accion = solicitud.TIPO_ANTICIPO === 'VIATICOS' ? 'debe legalizar el anticipo (formato F-FR-024).' : 'debe registrar la factura para legalizar el anticipo.';
      contenedor.innerHTML = PlantillaEspera('El solicitante', accion);
      return;
    }

    if (solicitud.TIPO_ANTICIPO === 'VIATICOS') {
      renderLegalizacionViaticos(contenedor, solicitud, refrescar);
    } else {
      renderFactura(contenedor, solicitud, refrescar);
    }
  };

  const renderFactura = (contenedor, solicitud, refrescar) => {
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Legalizar anticipo — registrar factura</h4>
        <form id="form-legalizar-factura" enctype="multipart/form-data" class="space-y-3"></form>
        <button type="submit" form="form-legalizar-factura" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Registrar factura</button>
      </div>`;

    const form = contenedor.querySelector('#form-legalizar-factura');
    form.innerHTML = Fragmentos.facturaHtml();
    Fragmentos.facturaBind(form);

    form.addEventListener('submit', (evento) => {
      evento.preventDefault();
      const datos = new FormData(form);
      datos.append('idSolicitud', solicitud.ID);

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_factura', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  // Mismas columnas que la tabla de gastos del formato F-FR-024.
  const COLUMNAS_DETALLE = [
    ['fechaGasto', 'Fecha', 'date'], ['centroCosto', 'C. costo', 'text'], ['documento', 'Doc', 'text'],
    ['detalle', 'Ciudad y detalles', 'text'], ['transporte', 'Transp', 'number'], ['taxis', 'Bus/taxis', 'number'],
    ['hotel', 'Hotel', 'number'], ['alimentacion', 'Aliment', 'number'], ['atencion', 'Atención', 'number'],
    ['gasolina', 'Gasolina', 'number'], ['servicios', 'Servicios', 'number'], ['otros', 'Otros', 'number'],
  ];

  const totalFila = (fila) => COLUMNAS_DETALLE.reduce((suma, [campo, , tipo]) => {
    if (tipo !== 'number') return suma;
    return suma + (parseFloat(fila.querySelector(`[data-campo="${campo}"]`).value) || 0);
  }, 0);

  const renderLegalizacionViaticos = (contenedor, solicitud, refrescar) => {
    const anticipo = solicitud.anticipo || {};
    const solViaticos = solicitud.viaticosSolicitud || {};
    const hoy = new Date();
    const fechaDoc = `${String(hoy.getDate()).padStart(2, '0')}/${String(hoy.getMonth() + 1).padStart(2, '0')}/${hoy.getFullYear()}`;

    contenedor.innerHTML = `
      <div class="rounded-xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50">
          <div>
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded bg-teal-600 flex items-center justify-center text-white font-extrabold text-[10px]">R</div>
              <span class="text-[11px] font-extrabold text-slate-700">ROMA S.A.</span>
            </div>
            <h4 class="text-[15px] font-extrabold text-slate-800 mt-1">Formato legalización de viáticos</h4>
          </div>
          <div class="text-right text-[10.5px] text-slate-500 leading-relaxed">
            <p><span class="font-bold text-slate-600">Código:</span> F-FR-024</p>
            <p><span class="font-bold text-slate-600">Versión:</span> 1</p>
            <p><span class="font-bold text-slate-600">Fecha:</span> ${fechaDoc}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 px-4 py-4 border-b border-slate-200 bg-slate-50">
          <p class="sm:col-span-3 text-xs text-slate-500">Valor del anticipo aprobado: <span class="font-semibold text-slate-700">${Formato.moneda(anticipo.VALOR_ANTICIPO)}</span></p>
          <div class="sm:col-span-1">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre</label>
            <input id="legal-nombres" maxlength="20" value="${anticipo.NOMBRE_TERCERO || ''}" placeholder="Nombre completo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">C.C.</label>
            <input id="legal-identificacion" type="number" maxlength="10" value="${anticipo.DOCUMENTO_IDENTIDAD || ''}" placeholder="Cédula" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Centro de costo</label>
            <input id="legal-centro-costo" maxlength="10" value="${anticipo.CENTRO_COSTOS || solViaticos.CENTRO_COSTO || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Descripción</label>
            <input id="legal-descripcion" maxlength="150" value="${solViaticos.MOTIVO || ''}" placeholder="Ej. Evento comercial Paipa" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha desde</label>
            <input type="date" id="legal-fecha-desde" value="${solViaticos.FECHA_SALIDA || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha hasta</label>
            <input type="date" id="legal-fecha-hasta" value="${solViaticos.FECHA_REGRESO || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-[11px] min-w-[980px]">
            <thead>
              <tr class="bg-slate-800 text-white uppercase tracking-wide text-[10px]">
                ${COLUMNAS_DETALLE.map(([, etiqueta]) => `<th class="px-2 py-2 text-left font-bold">${etiqueta}</th>`).join('')}
                <th class="px-2 py-2 text-right font-bold bg-slate-900">Tot. diario</th>
                <th class="w-8"></th>
              </tr>
            </thead>
            <tbody id="legal-filas" class="divide-y divide-slate-100"></tbody>
            <tfoot>
              <tr class="bg-teal-50 border-t-2 border-teal-600 font-extrabold text-[12px]">
                <td colspan="${COLUMNAS_DETALLE.length}" class="px-3 py-2.5 text-right text-slate-800">TOTAL GENERAL</td>
                <td class="px-2 py-2.5 text-right text-teal-700" id="legal-total-general">$0</td>
                <td></td>
              </tr>
              <tr class="bg-teal-50 font-extrabold text-[12px]">
                <td colspan="${COLUMNAS_DETALLE.length}" class="px-3 py-2 text-right text-slate-800">ANTICIPO</td>
                <td class="px-2 py-2 text-right text-teal-700" id="legal-total-anticipo">$0</td>
                <td></td>
              </tr>
              <tr class="bg-teal-50 font-extrabold text-[12px]">
                <td colspan="${COLUMNAS_DETALLE.length}" class="px-3 py-2 text-right text-slate-800">SALDO A/F (+ EMPRESA) (- EMPLEADO)</td>
                <td class="px-2 py-2 text-right text-teal-700" id="legal-total-saldo">$0</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div id="legal-alerta-consignacion" class="hidden bg-red-50 text-red-700 text-sm text-center py-3 border-t border-red-200">
          Es necesario que consignes a la empresa para la aprobación de esta legalización de gastos.
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50">
          <button type="button" id="legal-agregar-fila" class="px-3 py-2 text-sm rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-medium">+ Agregar fila</button>
          <button type="button" id="legal-ver-tarifas" class="text-sm text-teal-700 hover:underline">Tabla de viáticos</button>
        </div>
      </div>

      <button type="button" id="btn-guardar-legalizacion" class="mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar datos</button>`;

    const cuerpoFilas = contenedor.querySelector('#legal-filas');
    const valorAnticipo = parseFloat(anticipo.VALOR_ANTICIPO) || 0;

    const recalcularTotales = () => {
      let total = 0;
      cuerpoFilas.querySelectorAll('tr').forEach((fila) => {
        const totalDeLaFila = totalFila(fila);
        fila.querySelector('[data-total-fila]').textContent = Formato.moneda(totalDeLaFila);
        total += totalDeLaFila;
      });

      const saldo = valorAnticipo - total;
      contenedor.querySelector('#legal-total-general').textContent = Formato.moneda(total);
      contenedor.querySelector('#legal-total-anticipo').textContent = Formato.moneda(valorAnticipo);
      contenedor.querySelector('#legal-total-saldo').textContent = Formato.moneda(saldo);
      contenedor.querySelector('#legal-alerta-consignacion').classList.toggle('hidden', saldo >= 0);
    };

    const agregarFila = () => {
      const fila = document.createElement('tr');
      fila.innerHTML = COLUMNAS_DETALLE.map(([campo, , tipo]) => `
        <td class="p-1">
          <input type="${tipo}" data-campo="${campo}" ${tipo === 'number' ? 'value="0" min="0" step="100"' : ''}
            value="${campo === 'centroCosto' ? (contenedor.querySelector('#legal-centro-costo').value || '') : ''}"
            class="w-full border border-slate-200 rounded px-2 py-1 text-[11px]">
        </td>`).join('')
        + `<td class="p-1 text-right font-medium text-slate-700" data-total-fila>$0</td>`
        + `<td class="p-1 text-center"><button type="button" data-quitar class="text-red-500 hover:text-red-700 text-sm px-1">&times;</button></td>`;

      fila.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalcularTotales));
      fila.querySelector('[data-quitar]').addEventListener('click', () => { fila.remove(); recalcularTotales(); });

      cuerpoFilas.appendChild(fila);
      recalcularTotales();
    };

    contenedor.querySelector('#legal-agregar-fila').addEventListener('click', agregarFila);
    agregarFila();

    // Al cambiar el centro de costo del encabezado, se refleja en las filas ya creadas.
    contenedor.querySelector('#legal-centro-costo').addEventListener('change', (evento) => {
      cuerpoFilas.querySelectorAll('[data-campo="centroCosto"]').forEach((input) => { input.value = evento.target.value; });
    });

    contenedor.querySelector('#legal-ver-tarifas').addEventListener('click', () => {
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'datos_usuario_actual', {})
        .then((resp) => Fragmentos.abrirModalTarifas(resp.datos.NIVEL_VIATICOS))
        .catch((err) => UI.toast(err.mensaje, 'error'));
    });

    contenedor.querySelector('#btn-guardar-legalizacion').addEventListener('click', () => {
      const nombres = contenedor.querySelector('#legal-nombres').value.trim();
      const identificacion = contenedor.querySelector('#legal-identificacion').value.trim();
      const descripcion = contenedor.querySelector('#legal-descripcion').value.trim();
      const fechaDesde = contenedor.querySelector('#legal-fecha-desde').value;
      const fechaHasta = contenedor.querySelector('#legal-fecha-hasta').value;
      const centroCosto = contenedor.querySelector('#legal-centro-costo').value.trim();

      // Mismas validaciones que validarFormularioLegalizacionViaticos() en el sistema anterior.
      if (!nombres) { UI.toast('Debe ingresar el nombre del tercero.', 'error'); return; }
      if (!identificacion) { UI.toast('Debe ingresar la identificación del tercero.', 'error'); return; }
      if (!descripcion) { UI.toast('Debe ingresar la descripción del gasto.', 'error'); return; }
      if (!fechaDesde) { UI.toast('Debe ingresar la fecha desde.', 'error'); return; }
      if (!fechaHasta) { UI.toast('Debe ingresar la fecha hasta.', 'error'); return; }
      if (!centroCosto) { UI.toast('Debe ingresar el centro de costo.', 'error'); return; }

      const filasDom = Array.from(cuerpoFilas.querySelectorAll('tr'));
      if (filasDom.length === 0) { UI.toast('Debe agregar al menos una línea de gasto.', 'error'); return; }

      const filas = [];
      for (let indice = 0; indice < filasDom.length; indice++) {
        const fila = filasDom[indice];
        const numeroFila = indice + 1;
        const datosFila = {};
        COLUMNAS_DETALLE.forEach(([campo, , tipo]) => {
          const valor = fila.querySelector(`[data-campo="${campo}"]`).value.trim();
          datosFila[campo] = tipo === 'number' ? (parseFloat(valor) || 0) : valor;
        });

        if (!datosFila.fechaGasto) { UI.toast(`Debe ingresar la fecha del gasto en la fila ${numeroFila}.`, 'error'); return; }
        if (!datosFila.centroCosto) { UI.toast(`Debe ingresar el centro de costo en la fila ${numeroFila}.`, 'error'); return; }
        if (!datosFila.detalle) { UI.toast(`Debe ingresar la descripción del gasto en la fila ${numeroFila}.`, 'error'); return; }
        if (totalFila(fila) <= 0) { UI.toast(`Debe especificar al menos un valor de gasto en la fila ${numeroFila}.`, 'error'); return; }

        filas.push(datosFila);
      }

      const datos = {
        idSolicitud: solicitud.ID,
        nombresApellidos: nombres,
        identificacion,
        centroCosto,
        descripcion,
        fechaDesde,
        fechaHasta,
        filas: JSON.stringify(filas),
      };

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'registrar_legalizacion_viaticos', datos)
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('ANTICIPO_APROBADO', { render });
})();
