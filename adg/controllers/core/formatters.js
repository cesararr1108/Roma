/** Formateadores compartidos por todos los módulos de la vista. */
const Formato = {
  moneda: (valor) => new Intl.NumberFormat('es-CO', {
    style: 'currency', currency: 'COP', maximumFractionDigits: 0,
  }).format(Number(valor) || 0),

  fecha: (valor) => {
    if (!valor) return '-';
    const f = new Date(valor);
    if (isNaN(f.getTime())) return valor;
    return f.toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: '2-digit' });
  },

  fechaHora: (valor) => {
    if (!valor) return '-';
    const f = new Date(valor);
    if (isNaN(f.getTime())) return valor;
    return f.toLocaleString('es-CO', {
      year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit',
    });
  },

  consecutivo: (id) => 'GTO-' + String(id).padStart(6, '0'),
};
