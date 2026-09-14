/**
 * Módulo del estado ANTICIPO_APROBADO: el solicitante debe legalizar el
 * anticipo ya aprobado. Si es de bienes/servicios, registra una factura
 * (mismo fragmento que en el resto del flujo). Si es de viáticos, debe
 * diligenciar el detalle de gastos línea por línea.
 * Responsable: el solicitante (dueño de la solicitud).
 */
(function registrarModuloLegalizarAnticipo() {

  const render = (contenedor, solicitud, usuario, refrescar) => {
    const esDueno = Number(solicitud.ID_USUARIO_SOLICITA) === Number(usuario.id);

    if (!esDueno) {
      const accion = solicitud.TIPO_ANTICIPO === 'VIATICOS' ? 'debe legalizar el anticipo (detalle de gastos del viaje).' : 'debe registrar la factura para legalizar el anticipo.';
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

  const columnasDetalle = [
    ['fechaGasto', 'Fecha', 'date'], ['centroCosto', 'C. Costo', 'text'], ['documento', 'Documento', 'text'],
    ['detalle', 'Detalle', 'text'], ['transporte', 'Transporte', 'number'], ['taxis', 'Taxis', 'number'],
    ['hotel', 'Hotel', 'number'], ['alimentacion', 'Alimentación', 'number'], ['atencion', 'Atención', 'number'],
    ['gasolina', 'Gasolina', 'number'], ['servicios', 'Servicios', 'number'], ['otros', 'Otros', 'number'],
  ];

  const renderLegalizacionViaticos = (contenedor, solicitud, refrescar) => {
    const anticipo = solicitud.anticipo || {};

    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Legalización de viáticos</h4>
        <p class="text-sm text-slate-500">Valor del anticipo aprobado: <span class="font-medium text-slate-700">${Formato.moneda(anticipo.VALOR_ANTICIPO)}</span></p>

        <div class="grid sm:grid-cols-2 gap-3">
          <div><label class="text-sm text-slate-600">Nombres y apellidos</label><input id="legal-nombres" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="text-sm text-slate-600">Identificación</label><input id="legal-identificacion" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="text-sm text-slate-600">Centro de costo</label><input id="legal-centro-costo" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="text-sm text-slate-600">Descripción</label><input id="legal-descripcion" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="text-sm text-slate-600">Fecha desde</label><input type="date" id="legal-fecha-desde" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
          <div><label class="text-sm text-slate-600">Fecha hasta</label><input type="date" id="legal-fecha-hasta" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-lg">
          <table class="w-full text-xs min-w-[900px]">
            <thead class="bg-slate-50">
              <tr>${columnasDetalle.map(([, etiqueta]) => `<th class="py-2 px-2 text-left font-semibold text-slate-500">${etiqueta}</th>`).join('')}<th></th></tr>
            </thead>
            <tbody id="legal-filas"></tbody>
          </table>
        </div>
        <div class="flex items-center justify-between">
          <button type="button" id="legal-agregar-fila" class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">+ Agregar línea</button>
          <p class="text-sm text-slate-600">Total legalizado: <span id="legal-total" class="font-medium">$0</span></p>
        </div>

        <button type="button" id="btn-guardar-legalizacion" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Guardar legalización</button>
      </div>`;

    const cuerpoFilas = contenedor.querySelector('#legal-filas');

    const recalcularTotal = () => {
      let total = 0;
      cuerpoFilas.querySelectorAll('tr').forEach((fila) => {
        columnasDetalle.forEach(([campo, , tipo]) => {
          if (tipo === 'number') {
            total += parseFloat(fila.querySelector(`[data-campo="${campo}"]`).value) || 0;
          }
        });
      });
      contenedor.querySelector('#legal-total').textContent = Formato.moneda(total);
    };

    const agregarFila = () => {
      const fila = document.createElement('tr');
      fila.className = 'border-t border-slate-100';
      fila.innerHTML = columnasDetalle.map(([campo, , tipo]) => `
        <td class="p-1">
          <input type="${tipo}" data-campo="${campo}" ${tipo === 'number' ? 'value="0" min="0" step="0.01"' : ''} class="w-full border border-slate-200 rounded px-2 py-1 text-xs">
        </td>`).join('') + `<td class="p-1"><button type="button" data-quitar class="text-red-500 text-xs px-2">&times;</button></td>`;

      fila.querySelectorAll('input[type="number"]').forEach((input) => input.addEventListener('input', recalcularTotal));
      fila.querySelector('[data-quitar]').addEventListener('click', () => { fila.remove(); recalcularTotal(); });

      cuerpoFilas.appendChild(fila);
    };

    contenedor.querySelector('#legal-agregar-fila').addEventListener('click', agregarFila);
    agregarFila();

    contenedor.querySelector('#btn-guardar-legalizacion').addEventListener('click', () => {
      const filas = Array.from(cuerpoFilas.querySelectorAll('tr')).map((fila) => {
        const datosFila = {};
        columnasDetalle.forEach(([campo, , tipo]) => {
          const valor = fila.querySelector(`[data-campo="${campo}"]`).value;
          datosFila[campo] = tipo === 'number' ? (parseFloat(valor) || 0) : valor;
        });
        return datosFila;
      });

      const datos = {
        idSolicitud: solicitud.ID,
        nombresApellidos: contenedor.querySelector('#legal-nombres').value,
        identificacion: contenedor.querySelector('#legal-identificacion').value,
        centroCosto: contenedor.querySelector('#legal-centro-costo').value,
        descripcion: contenedor.querySelector('#legal-descripcion').value,
        fechaDesde: contenedor.querySelector('#legal-fecha-desde').value,
        fechaHasta: contenedor.querySelector('#legal-fecha-hasta').value,
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
