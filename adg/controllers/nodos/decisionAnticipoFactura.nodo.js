/**
 * Nodo "decision_anticipo_factura": tras la cotización aprobada, el
 * dueño decide si sigue por la rama de anticipo o directo con factura.
 * Es el ejemplo más claro de reutilización del grafo: elegir "anticipo"
 * aquí manda al mismo nodo "anticipo_datos" por el que también entran
 * las solicitudes que nacieron directo como anticipo.
 */
registrarNodo(new Nodo({
  id: 'decision_anticipo_factura',
  nombre: 'Definir anticipo o factura',
  soloDueno: true,
  siguiente: (contexto) => (contexto.requiereAnticipo ? 'anticipo_datos' : 'factura_datos'),

  render(contenedor, contexto, api) {
    contenedor.innerHTML = `
      <div class="space-y-4">
        <h4 class="font-semibold text-slate-800">Cotización aprobada — defina cómo continuar</h4>
        <div class="grid sm:grid-cols-2 gap-3">
          <button type="button" data-ir-anticipo class="border-2 border-indigo-200 hover:border-indigo-500 rounded-xl p-4 text-left transition">
            <p class="font-medium text-indigo-700 text-sm">Solicitar anticipo</p>
            <p class="text-xs text-slate-500 mt-1">A mi nombre o a un tercero. Requiere aprobación de Gerencia Administrativa.</p>
          </button>
          <button type="button" data-ir-factura class="border-2 border-slate-200 hover:border-slate-400 rounded-xl p-4 text-left transition">
            <p class="font-medium text-slate-700 text-sm">Registrar factura</p>
            <p class="text-xs text-slate-500 mt-1">Ya cuento con la factura del tercero, continúo sin anticipo.</p>
          </button>
        </div>
      </div>`;

    contenedor.querySelector('[data-ir-anticipo]').addEventListener('click', () => {
      api.avanzar('elegir_rama_anticipo', { idSolicitud: contexto.idSolicitud });
    });

    contenedor.querySelector('[data-ir-factura]').addEventListener('click', () => {
      api.avanzar('elegir_rama_factura', { idSolicitud: contexto.idSolicitud });
    });
  },
}));
