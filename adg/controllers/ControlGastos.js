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
      ModuloSolicitud.abrirModal(cargarBandeja);
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
        <td class="py-3 px-4 text-sm text-slate-600">${s.DESCRIPCION}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${s.NOMBRE_SOLICITANTE || '-'}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${Formato.moneda(s.VALOR_ESTIMADO)}</td>
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
    document.getElementById('detalle-titulo').textContent = `${Formato.consecutivo(solicitud.ID)} · ${solicitud.DESCRIPCION}`;
    document.getElementById('detalle-badge-estado').innerHTML = UI.badgeEstado(solicitud.estadoInfo);
    document.getElementById('detalle-valor').textContent = Formato.moneda(solicitud.VALOR_ESTIMADO);
    document.getElementById('detalle-solicitante').textContent = solicitud.NOMBRE_SOLICITANTE || '-';

    pintarStepper(solicitud);
    pintarHistorial(solicitud.historial || []);

    const contenedorAccion = document.getElementById('detalle-accion-actual');
    const modulo = RegistroEstados.obtener(solicitud.ESTADO);

    if (modulo) {
      modulo.render(contenedorAccion, solicitud, construirUsuarioUI(), cargarDetalle);
    } else {
      contenedorAccion.innerHTML = PlantillaEspera('El sistema', 'el flujo ha finalizado y no requiere más acciones.');
    }
  };

  const pintarStepper = (solicitud) => {
    const etapas = [
      { n: 2, l: 'Aprobación cotización' },
      { n: 3, l: 'Anticipo / Factura' },
      { n: 4, l: 'Aprobación anticipo' },
      { n: 5, l: 'Causación' },
      { n: 6, l: 'Pago' },
      { n: 7, l: 'Compensación' },
      { n: 9, l: 'Finalizado' },
    ];
    const etapaActual = (solicitud.estadoInfo && solicitud.estadoInfo.etapa) || 0;
    const requiereAnticipo = Number(solicitud.REQUIERE_ANTICIPO) === 1;
    const visibles = etapas.filter((e) => requiereAnticipo || (e.n !== 4 && e.n !== 7));

    document.getElementById('detalle-stepper').innerHTML = visibles.map((e) => {
      const activo = e.n <= etapaActual;
      const clase = activo ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400';
      return `
        <div class="flex flex-col items-center gap-1 flex-1">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold ${clase}">${e.n}</div>
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
