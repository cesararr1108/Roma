/**
 * Módulo del estado ANTICIPO_PENDIENTE_APROBACION.
 * Responsable: Gerencia Administrativa.
 */
(function registrarModuloAprobacionAnticipo() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    if (!usuario.esGerenciaAdministrativa) {
      contenedor.innerHTML = PlantillaEspera('Gerencia Administrativa', 'debe aprobar o rechazar el anticipo solicitado.');
      return;
    }

    const a = solicitud.anticipo || {};
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Aprobación de anticipo</h4>
        <dl class="grid sm:grid-cols-2 gap-3 text-sm bg-slate-50 rounded-lg p-4">
          <div><dt class="text-slate-500">Tipo de anticipo</dt><dd class="font-medium">${solicitud.TIPO_ANTICIPO === 'VIATICOS' ? 'Viáticos (viaje)' : 'Adquisición de bienes y servicios'}</dd></div>
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
          <button type="button" id="btn-aprobar-anticipo" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Aprobar anticipo</button>
          <button type="button" id="btn-rechazar-anticipo" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Rechazar anticipo</button>
        </div>
      </div>`;

    contenedor.querySelector('#btn-aprobar-anticipo').addEventListener('click', () => {
      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'aprobar_anticipo', { idSolicitud: solicitud.ID })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });

    contenedor.querySelector('#btn-rechazar-anticipo').addEventListener('click', async () => {
      const motivo = await UI.pedirTexto('Rechazar anticipo', 'Motivo del rechazo');
      if (!motivo) return;

      UI.mostrarCargando();
      enviarPeticion(ControlGastosApi.LINK_MODELO, 'rechazar_anticipo', { idSolicitud: solicitud.ID, motivo })
        .then((resp) => { UI.toast(resp.mensaje, 'exito'); refrescar(); })
        .catch((err) => UI.toast(err.mensaje, 'error'))
        .finally(() => UI.ocultarCargando());
    });
  };

  RegistroEstados.registrar('ANTICIPO_PENDIENTE_APROBACION', { render });
})();
