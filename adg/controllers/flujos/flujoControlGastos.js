/**
 * Grafo del flujo "Control de Gastos". Los nodos ya se registraron (cada
 * uno declara su propio `.siguiente` al definirse, en
 * controllers/nodos/*.nodo.js); este archivo solo arma el FlowEngine que
 * los recorre, empezando en "aprobacion_cotizacion" (las ramas de
 * factura y anticipo directos arrancan en su propio nodo inicial --
 * ver SolicitudFacturaHandler.php / SolicitudAnticipoHandler.php).
 *
 * Dos nodos se reutilizan de verdad dentro de este mismo flujo, sin
 * copiar código ni duplicar formularios:
 *
 *   - "anticipo_datos" atiende tanto a una solicitud que nació como
 *     anticipo como a la rama de anticipo de una cotización aprobada
 *     (via "decision_anticipo_factura").
 *   - "aprobacion_soporte" atiende tanto la factura de la rama sin
 *     anticipo (nodo "factura_datos") como la legalización de la rama
 *     con anticipo (nodo "legalizacion") -- por eso su `.siguiente`
 *     mira si ya existe una causación guardada para decidir si el
 *     próximo paso es "causacion" (primera vez) o "compensacion"
 *     (viene de legalizar, ya causada y pagada).
 *
 * Grafo completo:
 *
 *   aprobacion_cotizacion ──► decision_anticipo_factura ──► anticipo_datos ──► anticipo_aprobacion ─┐
 *          │                          │                                                             │
 *          ▼                          └────────────────────► factura_datos ──────────────┐          ▼
 *   rechazado_cotizacion                                            │                     │      causacion
 *                                                                    ▼                     │          │
 *                                                          aprobacion_soporte ◄────────────┘          ▼
 *                                                                    │                              pago ──┐
 *                                                                    ▼                                │    │
 *                                                          rechazado_soporte      (si requiere anticipo)   │
 *                                                                                          │               │
 *                                                                                          ▼               ▼
 *                                                                                    legalizacion       cruzado
 *                                                                                          │               │
 *                                                                                          ▼               ▼
 *                                                                              aprobacion_soporte      finalizado
 *                                                                                          │
 *                                                                                          ▼
 *                                                                                    compensacion ──► finalizado
 *
 * Ejemplo de cómo se reutilizaría este mismo flujo mañana, sin tocar
 * ninguno de los nodos de arriba (ver FlowEngine.derivar()):
 *
 *   const FlujoAnticipoExpress = FlujoControlGastos.derivar(
 *     'anticipo_express',
 *     'anticipo_datos',        // arranca directo en anticipo, sin cotización
 *     { pago: 'finalizado' }   // y en ESTE flujo no hay legalización: paga y cierra
 *   );
 */
const FlujoControlGastos = new FlowEngine('control_gastos', 'aprobacion_cotizacion');
