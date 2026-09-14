/**
 * Módulo del estado SOPORTE_PENDIENTE_APROBACION: gerencia administrativa
 * aprueba o rechaza la factura (directa, sin-anticipo o legalización de
 * bienes/servicios) o la legalización de viáticos -- según cuál de las
 * dos tenga la solicitud. Responsable: Gerencia Administrativa.
 */
(function registrarModuloAprobacionSoporte() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esGerenciaAdministrativa) {
      contenedor.innerHTML = PlantillaEspera('Gerencia Administrativa', 'debe aprobar o rechazar la factura/legalización.');
      return;
    }

    const esViaticos = solicitud.TIPO_ANTICIPO === 'VIATICOS' && solicitud.viaticosLegalizacion;
    const detalleHtml = esViaticos ? detalleLegalizacionViaticos(solicitud) : detalleFactura(solicitud);

    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">${esViaticos ? 'Aprobación de legalización de viáticos' : 'Aprobación de factura'}</h4>
        ${detalleHtml}
        <div class="flex flex-wrap gap-2">
          <button type="button" id="btn-aprobar-soporte" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar</button>
          <button type="button" id="btn-rechazar-soporte" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar</button>
        </div>
      </div>`;

    contenedor.querySelector('#btn-aprobar-soporte').addEventListener('click', () => {
      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'aprobar_soporte', { idSolicitud: solicitud.ID })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });

    contenedor.querySelector('#btn-rechazar-soporte').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar', 'Motivo del rechazo');
      if (!motivo) return;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'rechazar_soporte', { idSolicitud: solicitud.ID, motivo })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  const detalleFactura = (solicitud) => {
    const f = solicitud.factura || {};
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
  };

  const detalleLegalizacionViaticos = (solicitud) => {
    const l = solicitud.viaticosLegalizacion || {};
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
  };

  RegistroEstados.registrar('SOPORTE_PENDIENTE_APROBACION', { render });
})();
