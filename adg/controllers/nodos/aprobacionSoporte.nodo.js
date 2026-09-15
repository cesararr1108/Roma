/**
 * Nodo "aprobacion_soporte": gerencia administrativa aprueba o rechaza
 * la factura (directa, sin-anticipo, o legalización de bienes/servicios)
 * o la legalización de viáticos -- según cuál tenga la solicitud. Es el
 * segundo ejemplo de reutilización: a este mismo nodo llegan dos ramas
 * distintas del flujo (ver flujos/flujoControlGastos.js).
 */
registrarNodo(new Nodo({
  id: 'aprobacion_soporte',
  nombre: 'Aprobación de factura/legalización',
  rolesEditar: ROLES.GERENCIA_ADMINISTRATIVA,
  siguiente: (contexto) => (contexto.soporteAprobado
    ? (contexto.yaCausado ? 'compensacion' : 'causacion')
    : 'rechazado_soporte'),

  render(contenedor, contexto, api) {
    const esViaticos = contexto.tipoAnticipo === 'VIATICOS' && contexto.viaticosLegalizacion;
    const detalleHtml = esViaticos ? detalleLegalizacionViaticos(contexto) : detalleFactura(contexto);

    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">${esViaticos ? 'Aprobación de legalización de viáticos' : 'Aprobación de factura'}</h4>
        ${detalleHtml}
        <div class="flex flex-wrap gap-2">
          <button type="button" data-aprobar class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar</button>
          <button type="button" data-rechazar class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar</button>
        </div>
      </div>`;

    contenedor.querySelector('[data-aprobar]').addEventListener('click', () => {
      api.avanzar('aprobar_soporte', { idSolicitud: contexto.idSolicitud });
    });

    contenedor.querySelector('[data-rechazar]').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar', 'Motivo del rechazo');
      if (!motivo) return;
      api.avanzar('rechazar_soporte', { idSolicitud: contexto.idSolicitud, motivo });
    });
  },
}));

function detalleFactura(contexto) {
  const f = contexto.factura || {};
  return `
    <dl class="grid sm:grid-cols-2 gap-3 text-sm bg-slate-50 rounded-lg p-4">
      <div><dt class="text-slate-500">Tercero</dt><dd class="font-medium">${f.NOMBRE_TERCERO || '-'} (NIT ${f.NIT_TERCERO || '-'})</dd></div>
      <div><dt class="text-slate-500">Factura</dt><dd class="font-medium">${f.NUMERO_FACTURA || '-'} · ${Formato.fecha(f.FECHA_FACTURA)}</dd></div>
      <div><dt class="text-slate-500">Preliminar SAP</dt><dd class="font-medium">${f.NUMERO_PRELIMINAR_SAP || '-'}</dd></div>
      <div><dt class="text-slate-500">Fondo</dt><dd class="font-medium">${f.NUMERO_FONDO || '-'}</dd></div>
      <div><dt class="text-slate-500">Subtotal / IVA</dt><dd class="font-medium">${Formato.moneda(f.SUBTOTAL)} / ${Formato.moneda(f.IVA)}</dd></div>
      <div><dt class="text-slate-500">Total</dt><dd class="font-medium">${Formato.moneda(f.TOTAL)}</dd></div>
      ${f.RUTA_ARCHIVO ? `<div class="sm:col-span-2"><a href="${f.RUTA_ARCHIVO}" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:underline">Ver factura adjunta</a></div>` : ''}
    </dl>`;
}

function detalleLegalizacionViaticos(contexto) {
  const l = contexto.viaticosLegalizacion || {};
  const filas = l.detalle || [];

  return `
    <dl class="grid sm:grid-cols-2 gap-3 text-sm bg-slate-50 rounded-lg p-4">
      <div><dt class="text-slate-500">Solicitante</dt><dd class="font-medium">${l.NOMBRES_APELLIDOS || '-'}</dd></div>
      <div><dt class="text-slate-500">Rango de viaje</dt><dd class="font-medium">${Formato.fecha(l.FECHA_DESDE)} — ${Formato.fecha(l.FECHA_HASTA)}</dd></div>
      <div><dt class="text-slate-500">Valor anticipo</dt><dd class="font-medium">${Formato.moneda(l.VALOR_ANTICIPO)}</dd></div>
      <div><dt class="text-slate-500">Valor legalizado</dt><dd class="font-medium">${Formato.moneda(l.VALOR_LEGALIZADO)}</dd></div>
      <div><dt class="text-slate-500">Saldo</dt><dd class="font-medium">${Formato.moneda(l.SALDO)}</dd></div>
    </dl>
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
      <table class="w-full text-xs min-w-[700px]">
        <thead class="bg-slate-50"><tr>
          <th class="py-2 px-2 text-left">Fecha</th><th class="py-2 px-2 text-left">Detalle</th><th class="py-2 px-2 text-right">Total línea</th>
        </tr></thead>
        <tbody>
          ${filas.map((fila) => `<tr class="border-t border-slate-100">
            <td class="py-2 px-2">${Formato.fecha(fila.FECHA_GASTO)}</td>
            <td class="py-2 px-2">${fila.DETALLE || '-'}</td>
            <td class="py-2 px-2 text-right">${Formato.moneda(fila.TOTAL_FILA)}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}
