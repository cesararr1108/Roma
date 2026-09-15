/**
 * Nodo "anticipo_datos": el dueño registra los datos del beneficiario
 * (propio o tercero) del anticipo -- y, si es de viáticos, diligencia el
 * formato F-FR-023 en su propio modal (ver Fragmentos.anticipoHtml()).
 * A este nodo se llega tanto desde una solicitud que nació como
 * anticipo como desde "decision_anticipo_factura": es el mismo
 * componente sin importar por dónde se entró.
 */
registrarNodo(new Nodo({
  id: 'anticipo_datos',
  nombre: 'Datos del anticipo',
  soloDueno: true,
  siguiente: 'anticipo_aprobacion',

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Datos del anticipo</h4>
        <form data-form></form>
        <button type="button" data-enviar class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Enviar a aprobación</button>
      </div>`;

    const form = contenedor.querySelector('[data-form]');
    form.innerHTML = Fragmentos.anticipoHtml();

    enviarPeticion(ControlGastosApi.LINK_MODELO, 'datos_usuario_actual', {})
      .then((resp) => Fragmentos.anticipoBind(form, resp.datos))
      .catch(() => Fragmentos.anticipoBind(form, null));

    contenedor.querySelector('[data-enviar]').addEventListener('click', (evento) => {
      evento.preventDefault();
      const datos = Object.fromEntries(new FormData(form).entries());
      datos.idSolicitud = contexto.idSolicitud;
      api.avanzar('registrar_datos_anticipo', datos);
    });
  },
}));
