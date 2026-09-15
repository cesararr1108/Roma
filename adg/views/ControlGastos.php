<?php
session_start();
if (empty($_SESSION['ses_Login'])) {
    header('Location: ../../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Gastos</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

  <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Control de Gastos</h1>
        <p class="text-sm text-slate-500">Cotización · Factura · Anticipo</p>
      </div>
      <div class="text-right">
        <p class="text-sm font-medium text-slate-700" id="usuario-actual-nombre">-</p>
        <p class="text-xs text-slate-400">Sesión activa</p>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
        <button type="button" data-filtro-bandeja="mias" class="px-4 py-2 text-sm rounded-md bg-indigo-600 text-white">Mis solicitudes</button>
        <button type="button" data-filtro-bandeja="pendientes" class="px-4 py-2 text-sm rounded-md text-slate-600">Pendientes por mí</button>
      </div>
      <button type="button" id="btn-nueva-solicitud" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shadow-sm">
        + Nueva solicitud
      </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left min-w-[640px]">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Consecutivo</th>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Concepto</th>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Tipo</th>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Solicitante</th>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Estado</th>
              <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase">Fecha</th>
            </tr>
          </thead>
          <tbody id="cuerpo-tabla-bandeja"></tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Panel de detalle -->
  <div id="panel-detalle" class="hidden fixed inset-0 bg-slate-900/40 z-40 flex justify-end">
    <div class="bg-white w-full max-w-2xl h-full overflow-y-auto shadow-xl p-6 space-y-6">
      <div class="flex items-start justify-between">
        <div>
          <h2 id="detalle-titulo" class="text-lg font-semibold text-slate-800">-</h2>
          <div id="detalle-badge-estado" class="mt-1"></div>
        </div>
        <button type="button" id="cerrar-detalle" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
      </div>

      <div class="grid grid-cols-2 gap-4 text-sm bg-slate-50 rounded-lg p-4">
        <div><p class="text-slate-500">Solicitante</p><p id="detalle-solicitante" class="font-medium">-</p></div>
        <div><p class="text-slate-500">Tipo de solicitud</p><p id="detalle-valor" class="font-medium">-</p></div>
      </div>

      <div id="detalle-accion-actual" class="border border-slate-200 rounded-xl p-4"></div>
      <div id="detalle-otras-acciones"></div>

      <div>
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Historial del flujo</h3>
        <div id="detalle-historial" class="max-h-72 overflow-y-auto"></div>
      </div>
    </div>
  </div>

  <!-- Núcleo: cliente HTTP, motor de flujo (Nodo/FlowEngine/MotorFlujo), utilidades -->
  <script src="../controllers/core/apiClient.js"></script>
  <script src="../controllers/core/roles.js"></script>
  <script src="../controllers/core/Nodo.js"></script>
  <script src="../controllers/core/FlowEngine.js"></script>
  <script src="../controllers/core/MotorFlujo.js"></script>
  <script src="../controllers/core/formatters.js"></script>
  <script src="../controllers/core/ui.js"></script>
  <script src="../controllers/core/plantillas.js"></script>
  <script src="../controllers/core/fragmentosFormulario.js"></script>

  <!-- Nodos: un componente independiente por paso del flujo (se auto-registran al cargar) -->
  <script src="../controllers/nodos/solicitud.nodo.js"></script>
  <script src="../controllers/nodos/aprobacionCotizacion.nodo.js"></script>
  <script src="../controllers/nodos/decisionAnticipoFactura.nodo.js"></script>
  <script src="../controllers/nodos/anticipoDatos.nodo.js"></script>
  <script src="../controllers/nodos/anticipoAprobacion.nodo.js"></script>
  <script src="../controllers/nodos/facturaDatos.nodo.js"></script>
  <script src="../controllers/nodos/aprobacionSoporte.nodo.js"></script>
  <script src="../controllers/nodos/causacion.nodo.js"></script>
  <script src="../controllers/nodos/pago.nodo.js"></script>
  <script src="../controllers/nodos/legalizacion.nodo.js"></script>
  <script src="../controllers/nodos/compensacion.nodo.js"></script>
  <script src="../controllers/nodos/cruzado.nodo.js"></script>

  <!-- Grafo del flujo (arma el FlowEngine con los nodos ya registrados arriba) -->
  <script src="../controllers/flujos/flujoControlGastos.js"></script>

  <!-- Orquestador -->
  <script src="../controllers/ControlGastos.js"></script>
</body>
</html>
