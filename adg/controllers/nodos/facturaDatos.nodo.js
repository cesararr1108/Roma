/**
 * Nodo "factura_datos": el dueño registra los datos de la factura del
 * tercero (rama "cotización sin anticipo"). Comparte formulario y acción
 * de backend con el nodo "legalizacion" en su variante de bienes y
 * servicios -- por eso ambos usan el mismo op 'registrar_factura'.
 */
registrarNodo(new Nodo({
  id: 'factura_datos',
  nombre: 'Datos de la factura',
  soloDueno: true,
  siguiente: 'aprobacion_soporte',

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Registrar factura</h4>
        <form data-form enctype="multipart/form-data"></form>
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
  },
}));
