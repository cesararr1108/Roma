/**
 * Nodo "anticipo_aprobacion": gerencia administrativa aprueba o
 * rechaza el anticipo solicitado.
 */
registrarNodo(new Nodo({
  id: 'anticipo_aprobacion',
  nombre: 'Aprobación de anticipo',
  rolesEditar: ROLES.GERENCIA_ADMINISTRATIVA,
  siguiente: (contexto) => (contexto.anticipoAprobado ? 'causacion' : 'rechazado_anticipo'),

  render(contenedor, contexto, api) {
    const a = contexto.anticipo || {};
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Aprobación de anticipo</h4>
        <dl class="grid sm:grid-cols-2 gap-3 text-sm bg-slate-50 rounded-lg p-4">
          <div><dt class="text-slate-500">Tipo de anticipo</dt><dd class="font-medium">${contexto.tipoAnticipo === 'VIATICOS' ? 'Viáticos (viaje)' : 'Adquisición de bienes y servicios'}</dd></div>
          <div><dt class="text-slate-500">Valor solicitado</dt><dd class="font-medium">${Formato.moneda(a.VALOR_ANTICIPO)}</dd></div>
          <div><dt class="text-slate-500">Tipo de persona</dt><dd class="font-medium">${a.TIPO_PERSONA || '-'}</dd></div>
          <div><dt class="text-slate-500">Documento / NIT</dt><dd class="font-medium">${a.DOCUMENTO_IDENTIDAD || a.NIT_TERCERO || '-'}</dd></div>
          <div><dt class="text-slate-500">Nombres / Razón comercial</dt><dd class="font-medium">${a.NOMBRE_TERCERO || '-'}</dd></div>
          <div><dt class="text-slate-500">Razón social</dt><dd class="font-medium">${a.RAZON_SOCIAL_TERCERO || '-'}</dd></div>
          <div><dt class="text-slate-500">Código SAP</dt><dd class="font-medium">${a.CODIGO_SAP || '-'}</dd></div>
          <div><dt class="text-slate-500">Cargo</dt><dd class="font-medium">${a.CARGO || '-'}</dd></div>
          <div><dt class="text-slate-500">Centro de costos</dt><dd class="font-medium">${a.CENTRO_COSTOS || '-'}</dd></div>
          <div><dt class="text-slate-500">Teléfono</dt><dd class="font-medium">${a.CELULAR || '-'}</dd></div>
          <div><dt class="text-slate-500">Email</dt><dd class="font-medium">${a.CORREO || '-'}</dd></div>
        </dl>
        <div class="flex flex-wrap gap-2">
          <button type="button" data-aprobar class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar anticipo</button>
          <button type="button" data-rechazar class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar anticipo</button>
        </div>
      </div>`;

    contenedor.querySelector('[data-aprobar]').addEventListener('click', () => {
      api.avanzar('aprobar_anticipo', { idSolicitud: contexto.idSolicitud });
    });

    contenedor.querySelector('[data-rechazar]').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar anticipo', 'Motivo del rechazo');
      if (!motivo) return;
      api.avanzar('rechazar_anticipo', { idSolicitud: contexto.idSolicitud, motivo });
    });
  },
}));
