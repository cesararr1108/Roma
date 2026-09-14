/** Fragmentos HTML reutilizados por varios módulos de estado. */
const PlantillaEspera = (rol, accion) => `
  <div class="flex items-start gap-3 bg-slate-50 border border-slate-200 rounded-lg p-4">
    <svg class="w-5 h-5 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-sm text-slate-600"><span class="font-medium">${rol}</span> ${accion}</p>
  </div>`;
