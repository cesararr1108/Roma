/**
 * Nodo "legalizacion" (solo rama anticipo, tras el pago): el dueño
 * legaliza el anticipo ya aprobado. Si es de bienes/servicios, registra
 * una factura -- mismo componente y misma acción de backend que el nodo
 * "factura_datos" (ver core/fragmentosFormulario.js y
 * Handlers/FacturaHandler.php). Si es de viáticos, diligencia el
 * formato F-FR-024 -- mismos campos, misma tabla de detalle y mismas
 * reglas de validación que el sistema de gestión de calidad.
 */
registrarNodo(new Nodo({
  id: 'legalizacion',
  nombre: 'Legalización del anticipo',
  soloDueno: true,
  siguiente: 'aprobacion_soporte',

  render(contenedor, contexto, api) {
    if (contexto.tipoAnticipo === 'VIATICOS') {
      renderLegalizacionViaticos(contenedor, contexto, api);
    } else {
      renderLegalizacionFactura(contenedor, contexto, api);
    }
  },
}));

function renderLegalizacionFactura(contenedor, contexto, api) {
  contenedor.innerHTML = `
    <div class="space-y-4">
      <h4 class="font-semibold text-slate-800">Legalizar anticipo — registrar factura</h4>
      <form data-form enctype="multipart/form-data" class="space-y-3"></form>
      <button type="button" data-enviar class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Registrar factura</button>
    </div>`;

  const form = contenedor.querySelector('[data-form]');
  form.innerHTML = Fragmentos.facturaHtml();
  Fragmentos.facturaBind(form);

  contenedor.querySelector('[data-enviar]').addEventListener('click', () => {
    const datos = new FormData(form);
    datos.append('idSolicitud', contexto.idSolicitud);
    api.avanzar('registrar_factura', datos);
  });
}

const COLUMNAS_DETALLE_LEGALIZACION = [
  ['fechaGasto', 'Fecha', 'date'], ['centroCosto', 'C. costo', 'text'], ['documento', 'Doc', 'text'],
  ['detalle', 'Ciudad y detalles', 'text'], ['transporte', 'Transp', 'number'], ['taxis', 'Bus/taxis', 'number'],
  ['hotel', 'Hotel', 'number'], ['alimentacion', 'Aliment', 'number'], ['atencion', 'Atención', 'number'],
  ['gasolina', 'Gasolina', 'number'], ['servicios', 'Servicios', 'number'], ['otros', 'Otros', 'number'],
];

const totalFilaLegalizacion = (fila) => COLUMNAS_DETALLE_LEGALIZACION.reduce((suma, [campo, , tipo]) => {
  if (tipo !== 'number') return suma;
  return suma + (parseFloat(fila.querySelector(`[data-campo="${campo}"]`).value) || 0);
}, 0);

function renderLegalizacionViaticos(contenedor, contexto, api) {
  const anticipo = contexto.anticipo || {};
  const solViaticos = contexto.viaticosSolicitud || {};
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
          <input data-v="nombres" maxlength="20" value="${anticipo.NOMBRE_TERCERO || ''}" placeholder="Nombre completo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">C.C.</label>
          <input data-v="identificacion" type="number" maxlength="10" value="${anticipo.DOCUMENTO_IDENTIDAD || ''}" placeholder="Cédula" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Centro de costo</label>
          <input data-v="centroCosto" maxlength="10" value="${anticipo.CENTRO_COSTOS || solViaticos.CENTRO_COSTO || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-3">
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Descripción</label>
          <input data-v="descripcion" maxlength="150" value="${solViaticos.MOTIVO || ''}" placeholder="Ej. Evento comercial Paipa" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha desde</label>
          <input type="date" data-v="fechaDesde" value="${solViaticos.FECHA_SALIDA || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fecha hasta</label>
          <input type="date" data-v="fechaHasta" value="${solViaticos.FECHA_REGRESO || ''}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-[11px] min-w-[980px]">
          <thead>
            <tr class="bg-slate-800 text-white uppercase tracking-wide text-[10px]">
              ${COLUMNAS_DETALLE_LEGALIZACION.map(([, etiqueta]) => `<th class="px-2 py-2 text-left font-bold">${etiqueta}</th>`).join('')}
              <th class="px-2 py-2 text-right font-bold bg-slate-900">Tot. diario</th>
              <th class="w-8"></th>
            </tr>
          </thead>
          <tbody data-filas class="divide-y divide-slate-100"></tbody>
          <tfoot>
            <tr class="bg-teal-50 border-t-2 border-teal-600 font-extrabold text-[12px]">
              <td colspan="${COLUMNAS_DETALLE_LEGALIZACION.length}" class="px-3 py-2.5 text-right text-slate-800">TOTAL GENERAL</td>
              <td class="px-2 py-2.5 text-right text-teal-700" data-total-general>$0</td>
              <td></td>
            </tr>
            <tr class="bg-teal-50 font-extrabold text-[12px]">
              <td colspan="${COLUMNAS_DETALLE_LEGALIZACION.length}" class="px-3 py-2 text-right text-slate-800">ANTICIPO</td>
              <td class="px-2 py-2 text-right text-teal-700" data-total-anticipo>$0</td>
              <td></td>
            </tr>
            <tr class="bg-teal-50 font-extrabold text-[12px]">
              <td colspan="${COLUMNAS_DETALLE_LEGALIZACION.length}" class="px-3 py-2 text-right text-slate-800">SALDO A/F (+ EMPRESA) (- EMPLEADO)</td>
              <td class="px-2 py-2 text-right text-teal-700" data-total-saldo>$0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div data-alerta-consignacion class="hidden bg-red-50 text-red-700 text-sm text-center py-3 border-t border-red-200">
        Es necesario que consignes a la empresa para la aprobación de esta legalización de gastos.
      </div>

      <div class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50">
        <button type="button" data-agregar-fila class="px-3 py-2 text-sm rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-medium">+ Agregar fila</button>
        <button type="button" data-ver-tarifas class="text-sm text-teal-700 hover:underline">Tabla de viáticos</button>
      </div>
    </div>

    <button type="button" data-guardar class="mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar datos</button>`;

  const cuerpoFilas = contenedor.querySelector('[data-filas]');
  const valorAnticipo = parseFloat(anticipo.VALOR_ANTICIPO) || 0;

  const recalcularTotales = () => {
    let total = 0;
    cuerpoFilas.querySelectorAll('tr').forEach((fila) => {
      const totalDeLaFila = totalFilaLegalizacion(fila);
      fila.querySelector('[data-total-fila]').textContent = Formato.moneda(totalDeLaFila);
      total += totalDeLaFila;
    });

    const saldo = valorAnticipo - total;
    contenedor.querySelector('[data-total-general]').textContent = Formato.moneda(total);
    contenedor.querySelector('[data-total-anticipo]').textContent = Formato.moneda(valorAnticipo);
    contenedor.querySelector('[data-total-saldo]').textContent = Formato.moneda(saldo);
    contenedor.querySelector('[data-alerta-consignacion]').classList.toggle('hidden', saldo >= 0);
  };

  const agregarFila = () => {
    const fila = document.createElement('tr');
    fila.innerHTML = COLUMNAS_DETALLE_LEGALIZACION.map(([campo, , tipo]) => `
      <td class="p-1">
        <input type="${tipo}" data-campo="${campo}" ${tipo === 'number' ? 'value="0" min="0" step="100"' : ''}
          value="${campo === 'centroCosto' ? (contenedor.querySelector('[data-v="centroCosto"]').value || '') : ''}"
          class="w-full border border-slate-200 rounded px-2 py-1 text-[11px]">
      </td>`).join('')
      + `<td class="p-1 text-right font-medium text-slate-700" data-total-fila>$0</td>`
      + `<td class="p-1 text-center"><button type="button" data-quitar class="text-red-500 hover:text-red-700 text-sm px-1">&times;</button></td>`;

    fila.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalcularTotales));
    fila.querySelector('[data-quitar]').addEventListener('click', () => { fila.remove(); recalcularTotales(); });

    cuerpoFilas.appendChild(fila);
    recalcularTotales();
  };

  contenedor.querySelector('[data-agregar-fila]').addEventListener('click', agregarFila);
  agregarFila();

  // Al cambiar el centro de costo del encabezado, se refleja en las filas ya creadas.
  contenedor.querySelector('[data-v="centroCosto"]').addEventListener('change', (evento) => {
    cuerpoFilas.querySelectorAll('[data-campo="centroCosto"]').forEach((input) => { input.value = evento.target.value; });
  });

  contenedor.querySelector('[data-ver-tarifas]').addEventListener('click', () => {
    enviarPeticion(ControlGastosApi.LINK_MODELO, 'datos_usuario_actual', {})
      .then((resp) => Fragmentos.abrirModalTarifas(resp.datos.NIVEL_VIATICOS))
      .catch((err) => UI.toast(err.mensaje, 'error'));
  });

  contenedor.querySelector('[data-guardar]').addEventListener('click', () => {
    const nombres = contenedor.querySelector('[data-v="nombres"]').value.trim();
    const identificacion = contenedor.querySelector('[data-v="identificacion"]').value.trim();
    const descripcion = contenedor.querySelector('[data-v="descripcion"]').value.trim();
    const fechaDesde = contenedor.querySelector('[data-v="fechaDesde"]').value;
    const fechaHasta = contenedor.querySelector('[data-v="fechaHasta"]').value;
    const centroCosto = contenedor.querySelector('[data-v="centroCosto"]').value.trim();

    // Mismas validaciones que validarFormularioLegalizacionViaticos() en el formato original.
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
      COLUMNAS_DETALLE_LEGALIZACION.forEach(([campo, , tipo]) => {
        const valor = fila.querySelector(`[data-campo="${campo}"]`).value.trim();
        datosFila[campo] = tipo === 'number' ? (parseFloat(valor) || 0) : valor;
      });

      if (!datosFila.fechaGasto) { UI.toast(`Debe ingresar la fecha del gasto en la fila ${numeroFila}.`, 'error'); return; }
      if (!datosFila.centroCosto) { UI.toast(`Debe ingresar el centro de costo en la fila ${numeroFila}.`, 'error'); return; }
      if (!datosFila.detalle) { UI.toast(`Debe ingresar la descripción del gasto en la fila ${numeroFila}.`, 'error'); return; }
      if (totalFilaLegalizacion(fila) <= 0) { UI.toast(`Debe especificar al menos un valor de gasto en la fila ${numeroFila}.`, 'error'); return; }

      filas.push(datosFila);
    }

    api.avanzar('registrar_legalizacion_viaticos', {
      idSolicitud: contexto.idSolicitud,
      nombresApellidos: nombres,
      identificacion,
      centroCosto,
      descripcion,
      fechaDesde,
      fechaHasta,
      filas: JSON.stringify(filas),
    });
  });
}
