/**
 * Orquestador principal del módulo de Control de Gastos.
 *
 * Carga el contexto de sesión/catálogos, pinta la bandeja de trabajo y,
 * para el detalle de cada solicitud, delega la UI/acción del paso actual
 * al módulo registrado para ESTADO (ver core/estadoRegistry.js y
 * controllers/modules/*.module.js). Este archivo nunca contiene lógica
 * propia de un paso del flujo -- solo enruta.
 */
const ControlGastosApp = (() => {
  let usuarioContexto = null;
  let filtroActivo = 'mias';
  let solicitudActivaId = null;

  const NOMBRES_TIPO_GASTO = { 1: 'Cotización', 2: 'Factura de gasto', 3: 'Anticipo' };

  const iniciar = () => {
    UI.mostrarCargando();
    enviarPeticion(ControlGastosApi.LINK_MODELO, 'cargar_datos', {})
      .then((resp) => {
        usuarioContexto = resp.datos;
        pintarEncabezado();
        cargarBandeja();
      })
      .catch((err) => UI.toast(err.mensaje, 'error'))
      .finally(() => UI.ocultarCargando());

    document.getElementById('btn-nueva-solicitud').addEventListener('click', () => {
      ModuloSolicitud.abrirModal(usuarioContexto, cargarBandeja);
    });

    document.querySelectorAll('[data-filtro-bandeja]').forEach((boton) => {
      boton.addEventListener('click', () => {
        filtroActivo = boton.dataset.filtroBandeja;
        document.querySelectorAll('[data-filtro-bandeja]').forEach((b) => b.classList.remove('bg-indigo-600', 'text-white'));
        boton.classList.add('bg-indigo-600', 'text-white');
        cargarBandeja();
      });
    });

    document.getElementById('cerrar-detalle').addEventListener('click', cerrarDetalle);
  };

  const pintarEncabezado = () => {
    document.getElementById('usuario-actual-nombre').textContent = usuarioContexto.usuario.nombre || usuarioContexto.usuario.login;
  };

  const construirUsuarioUI = () => ({
    id: usuarioContexto.usuario.id,
    rolId: usuarioContexto.usuario.rolId,
    esGerenciaAdministrativa: usuarioContexto.esGerenciaAdministrativa,
    esContabilidad: usuarioContexto.esContabilidad,
    esTesoreria: usuarioContexto.esTesoreria,
  });

  const cargarBandeja = () => {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-bandeja');
    cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-slate-400 text-sm">Cargando...</td></tr>';

    enviarPeticion(ControlGastosApi.LINK_MODELO, 'listar_bandeja', { filtro: filtroActivo })
      .then((resp) => pintarBandeja(resp.datos))
      .catch((err) => UI.toast(err.mensaje, 'error'));
  };

  const pintarBandeja = (solicitudes) => {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-bandeja');

    if (!solicitudes.length) {
      cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-400 text-sm">No hay solicitudes para mostrar.</td></tr>';
      return;
    }

    cuerpoTabla.innerHTML = solicitudes.map((s) => `
      <tr class="hover:bg-slate-50 cursor-pointer border-b border-slate-100" data-id-solicitud="${s.ID}">
        <td class="py-3 px-4 text-sm font-medium text-slate-700">${Formato.consecutivo(s.ID)}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${s.NOMBRE_CONCEPTO || '-'}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${NOMBRES_TIPO_GASTO[s.TIPO_GASTO] || '-'}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${s.NOMBRE_SOLICITANTE || '-'}</td>
        <td class="py-3 px-4">${UI.badgeEstado(usuarioContexto.estados[s.ESTADO])}</td>
        <td class="py-3 px-4 text-sm text-slate-500">${Formato.fecha(s.FECHA_CREACION)}</td>
      </tr>`).join('');

    cuerpoTabla.querySelectorAll('[data-id-solicitud]').forEach((fila) => {
      fila.addEventListener('click', () => abrirDetalle(fila.dataset.idSolicitud));
    });
  };

  const abrirDetalle = (idSolicitud) => {
    solicitudActivaId = idSolicitud;
    document.getElementById('panel-detalle').classList.remove('hidden');
    cargarDetalle();
  };

  const cerrarDetalle = () => {
    solicitudActivaId = null;
    document.getElementById('panel-detalle').classList.add('hidden');
  };

  const cargarDetalle = () => {
    if (!solicitudActivaId) return;

    UI.mostrarCargando();
    enviarPeticion(ControlGastosApi.LINK_MODELO, 'obtener_solicitud', { idSolicitud: solicitudActivaId })
      .then((resp) => pintarDetalle(resp.datos))
      .catch((err) => UI.toast(err.mensaje, 'error'))
      .finally(() => UI.ocultarCargando());
  };

  const pintarDetalle = (solicitud) => {
    document.getElementById('detalle-titulo').textContent = `${Formato.consecutivo(solicitud.ID)} · ${solicitud.NOMBRE_CONCEPTO || ''}`;
    document.getElementById('detalle-badge-estado').innerHTML = UI.badgeEstado(solicitud.estadoInfo);
    document.getElementById('detalle-valor').textContent = NOMBRES_TIPO_GASTO[solicitud.TIPO_GASTO] || '-';
    document.getElementById('detalle-solicitante').textContent = solicitud.NOMBRE_SOLICITANTE || '-';

    pintarStepper(solicitud);
    pintarHistorial(solicitud.historial || []);

    const usuarioUI = construirUsuarioUI();
    const contenedorAccion = document.getElementById('detalle-accion-actual');
    const modulo = RegistroEstados.obtener(solicitud.ESTADO);

    if (modulo) {
      modulo.render(contenedorAccion, solicitud, usuarioUI, cargarDetalle);
    } else {
      contenedorAccion.innerHTML = PlantillaEspera('El sistema', 'el flujo ha finalizado y no requiere más acciones.');
    }

    pintarOtrasAcciones(solicitud, usuarioUI);
  };

  /** Reapertura (sobre estados *_RECHAZADA) y observación libre, disponibles según el rol/estado. */
  const pintarOtrasAcciones = (solicitud, usuarioUI) => {
    const contenedor = document.getElementById('detalle-otras-acciones');
    const puedeReabrir = usuarioUI.esGerenciaAdministrativa && solicitud.estadoInfo && solicitud.estadoInfo.reabreA;
    const puedeObservar = solicitud.estadoInfo && (solicitud.estadoInfo.rolesResponsables || []).includes(usuarioUI.rolId);

    if (!puedeReabrir && !puedeObservar) {
      contenedor.innerHTML = '';
      return;
    }

    contenedor.innerHTML = `
      <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
        ${puedeObservar ? '<button type="button" id="btn-agregar-observacion" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">Agregar observación</button>' : ''}
        ${puedeReabrir ? '<button type="button" id="btn-reabrir-solicitud" class="px-3 py-1.5 text-xs rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50">Reabrir solicitud</button>' : ''}
      </div>`;

    const btnObservacion = document.getElementById('btn-agregar-observacion');
    if (btnObservacion) {
      btnObservacion.addEventListener('click', async () => {
        const comentario = await UI.pedirTexto('Agregar observación', 'Observación');
        if (!comentario) return;

        UI.mostrarCargando();
        enviarPeticion(ControlGastosApi.LINK_MODELO, 'agregar_observacion', { idSolicitud: solicitud.ID, comentario })
          .then((resp) => { UI.toast(resp.mensaje, 'exito'); cargarDetalle(); })
          .catch((err) => UI.toast(err.mensaje, 'error'))
          .finally(() => UI.ocultarCargando());
      });
    }

    const btnReabrir = document.getElementById('btn-reabrir-solicitud');
    if (btnReabrir) {
      btnReabrir.addEventListener('click', async () => {
        const comentario = await UI.pedirTexto('Reabrir solicitud', 'Comentario (opcional)');
        UI.mostrarCargando();
        enviarPeticion(ControlGastosApi.LINK_MODELO, 'reabrir_solicitud', { idSolicitud: solicitud.ID, comentario: comentario || '' })
          .then((resp) => { UI.toast(resp.mensaje, 'exito'); cargarDetalle(); cargarBandeja(); })
          .catch((err) => UI.toast(err.mensaje, 'error'))
          .finally(() => UI.ocultarCargando());
      });
    }
  };

  const pintarStepper = (solicitud) => {
    const requiereAnticipo = Number(solicitud.REQUIERE_ANTICIPO) === 1;
    const esCotizacion = Number(solicitud.TIPO_GASTO) === 1;

    const etapas = [
      { n: 2, l: 'Aprobación cotización', mostrar: esCotizacion },
      { n: 4, l: 'Aprobación anticipo', mostrar: requiereAnticipo },
      { n: 5, l: 'Legalizar anticipo', mostrar: requiereAnticipo },
      { n: 6, l: 'Aprobación factura', mostrar: true },
      { n: 8, l: 'Causación', mostrar: true },
      { n: 9, l: 'Pago', mostrar: true },
      { n: 10, l: 'Compensación', mostrar: requiereAnticipo },
      { n: 11, l: 'Finalizado', mostrar: true },
    ];
    const etapaActual = (solicitud.estadoInfo && solicitud.estadoInfo.etapa) || 0;
    const visibles = etapas.filter((e) => e.mostrar);

    document.getElementById('detalle-stepper').innerHTML = visibles.map((e) => {
      const activo = e.n <= etapaActual;
      const clase = activo ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400';
      return `
        <div class="flex flex-col items-center gap-1 flex-1">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold ${clase}">✓</div>
          <span class="text-[11px] text-center text-slate-500">${e.l}</span>
        </div>`;
    }).join('<div class="flex-1 h-px bg-slate-200 mt-3.5"></div>');
  };

  const pintarHistorial = (historial) => {
    const contenedor = document.getElementById('detalle-historial');
    if (!historial.length) {
      contenedor.innerHTML = '<p class="text-sm text-slate-400">Sin movimientos registrados.</p>';
      return;
    }

    contenedor.innerHTML = historial.map((h) => `
      <div class="border-l-2 border-indigo-200 pl-3 pb-3">
        <p class="text-xs text-slate-400">${Formato.fechaHora(h.FECHA)} · ${h.USUARIO || 'Usuario #' + h.ID_USUARIO}</p>
        <p class="text-sm text-slate-700 font-medium">${String(h.ACCION).split('_').join(' ')}</p>
        ${h.COMENTARIO ? `<p class="text-sm text-slate-500">${h.COMENTARIO}</p>` : ''}
      </div>`).join('');
  };

  return { iniciar, cargarDetalle };
})();

document.addEventListener('DOMContentLoaded', ControlGastosApp.iniciar);
