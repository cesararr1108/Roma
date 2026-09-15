/**
 * Orquestador principal. Carga catálogos, pinta la bandeja de trabajo y,
 * al abrir una solicitud, delega TODO el detalle a un MotorFlujo -- este
 * archivo no sabe qué nodos existen ni qué hace cada uno.
 */
const ControlGastosApp = (() => {
  let usuarioContexto = null;
  let filtroActivo = 'mias';
  let solicitudActivaId = null;
  let motor = null;

  const NOMBRES_TIPO_GASTO = { 1: 'Cotización', 2: 'Factura de gasto', 3: 'Anticipo' };
  const NODOS_RECHAZO = ['rechazado_cotizacion', 'rechazado_anticipo', 'rechazado_soporte'];

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

  const cargarBandeja = () => {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-bandeja');
    cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-slate-400 text-sm">Cargando...</td></tr>';

    enviarPeticion(ControlGastosApi.LINK_MODELO, 'listar_bandeja', { filtro: filtroActivo })
      .then((resp) => pintarBandeja(resp.datos))
      .catch((err) => UI.toast(err.mensaje, 'error'));
  };

  /** "Pendientes por mí" se filtra en el navegador con Nodo.puedeEditar() -- el backend no sabe qué rol atiende qué nodo. */
  const filaEsPendienteParaMi = (fila) => {
    if (!RegistroNodos.has(fila.NODO_ACTUAL)) return false;
    const nodo = RegistroNodos.get(fila.NODO_ACTUAL);
    return nodo.puedeEditar(usuarioContexto.usuario, { idUsuarioSolicita: fila.ID_USUARIO_SOLICITA });
  };

  const pintarBandeja = (solicitudes) => {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-bandeja');
    const filas = filtroActivo === 'pendientes' ? solicitudes.filter(filaEsPendienteParaMi) : solicitudes;

    if (!filas.length) {
      cuerpoTabla.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-400 text-sm">No hay solicitudes para mostrar.</td></tr>';
      return;
    }

    cuerpoTabla.innerHTML = filas.map((s) => `
      <tr class="hover:bg-slate-50 cursor-pointer border-b border-slate-100" data-id-solicitud="${s.ID}">
        <td class="py-3 px-4 text-sm font-medium text-slate-700">${Formato.consecutivo(s.ID)}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${s.NOMBRE_CONCEPTO || '-'}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${NOMBRES_TIPO_GASTO[s.TIPO_GASTO] || '-'}</td>
        <td class="py-3 px-4 text-sm text-slate-600">${s.NOMBRE_SOLICITANTE || '-'}</td>
        <td class="py-3 px-4">${UI.badgeNodo(nodoOFinalizado(s.NODO_ACTUAL))}</td>
        <td class="py-3 px-4 text-sm text-slate-500">${Formato.fecha(s.FECHA_CREACION)}</td>
      </tr>`).join('');

    cuerpoTabla.querySelectorAll('[data-id-solicitud]').forEach((fila) => {
      fila.addEventListener('click', () => abrirDetalle(fila.dataset.idSolicitud));
    });
  };

  const nodoOFinalizado = (idNodo) => (RegistroNodos.has(idNodo) ? RegistroNodos.get(idNodo) : null);

  const abrirDetalle = (idSolicitud) => {
    solicitudActivaId = idSolicitud;
    document.getElementById('panel-detalle').classList.remove('hidden');

    motor = new MotorFlujo(FlujoControlGastos, usuarioContexto.usuario);
    motor.iniciar(idSolicitud, document.getElementById('detalle-accion-actual'), document.getElementById('detalle-historial'))
      .then(pintarCabeceraDetalle);
  };

  const cerrarDetalle = () => {
    solicitudActivaId = null;
    motor = null;
    document.getElementById('panel-detalle').classList.add('hidden');
  };

  const pintarCabeceraDetalle = () => {
    if (!motor || !motor.contexto) return;
    const c = motor.contexto;

    document.getElementById('detalle-titulo').textContent = `${Formato.consecutivo(c.idSolicitud)} · ${c.nombreConcepto || ''}`;
    document.getElementById('detalle-badge-estado').innerHTML = UI.badgeNodo(nodoOFinalizado(c.nodoActual));
    document.getElementById('detalle-valor').textContent = NOMBRES_TIPO_GASTO[c.tipoGasto] || '-';
    document.getElementById('detalle-solicitante').textContent = c.nombreSolicitante || '-';

    pintarOtrasAcciones(c);
  };

  /** Reapertura (sobre nodos de rechazo) y observación libre. */
  const pintarOtrasAcciones = (contexto) => {
    const contenedor = document.getElementById('detalle-otras-acciones');
    const usuario = usuarioContexto.usuario;

    const rolesConAcceso = [].concat(ROLES.GERENCIA_ADMINISTRATIVA, ROLES.CONTABILIDAD, ROLES.TESORERIA);
    const esDueno = Number(contexto.idUsuarioSolicita) === Number(usuario.id);
    const puedeObservar = esDueno || rolesConAcceso.includes(usuario.rolId);
    const puedeReabrir = ROLES.GERENCIA_ADMINISTRATIVA.includes(usuario.rolId) && NODOS_RECHAZO.includes(contexto.nodoActual);

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
        motor.avanzar('agregar_observacion', { idSolicitud: contexto.idSolicitud, comentario }).then(pintarCabeceraDetalle);
      });
    }

    const btnReabrir = document.getElementById('btn-reabrir-solicitud');
    if (btnReabrir) {
      btnReabrir.addEventListener('click', async () => {
        const comentario = await UI.pedirTexto('Reabrir solicitud', 'Comentario (opcional)');
        motor.avanzar('reabrir_solicitud', { idSolicitud: contexto.idSolicitud, comentario: comentario || '' })
          .then(() => { pintarCabeceraDetalle(); cargarBandeja(); });
      });
    }
  };

  return { iniciar };
})();

document.addEventListener('DOMContentLoaded', ControlGastosApp.iniciar);
