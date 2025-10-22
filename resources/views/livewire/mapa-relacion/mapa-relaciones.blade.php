{{-- resources/views/livewire/mapa-relacion/mapa-relaciones.blade.php --}}

@assets
  <script src="https://unpkg.com/cytoscape@3.26.0/dist/cytoscape.min.js"></script>
  <script src="https://unpkg.com/dagre@0.8.5/dist/dagre.min.js"></script>
  <script src="https://unpkg.com/cytoscape-dagre@2.5.0/cytoscape-dagre.js"></script>
  <script>
    if (window.cytoscape && window.cytoscapeDagre) {
      cytoscape.use(window.cytoscapeDagre);
    }
  </script>
  <style>
    @keyframes fadeInScale {
      from {
        opacity: 0;
        transform: scale(0.95);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
    .mapa-modal {
      animation: fadeInScale 0.2s ease-out;
    }
  </style>
@endassets

<div x-data="mapaRelaciones()"
     x-init="init($wire.open, @js($graph))"
     x-show="$wire.open"
     x-cloak
     class="fixed inset-0 z-[100] flex items-center justify-center p-4">

  {{-- Backdrop Premium --}}
  <div class="absolute inset-0 bg-gradient-to-br from-slate-900/95 via-slate-800/95 to-slate-900/95 backdrop-blur-md" 
       @click="$wire.cerrar()"></div>

  {{-- Dialog Premium --}}
  <div class="mapa-modal relative z-10 w-full max-w-7xl rounded-2xl bg-white dark:bg-slate-900 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] overflow-hidden border border-slate-200/50 dark:border-slate-700/50">
    
    {{-- Header Corporativo --}}
    <div class="relative bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-slate-800 dark:via-slate-900 dark:to-slate-800 border-b border-slate-200 dark:border-slate-700">
      <div class="px-8 py-5 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/30">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Mapa de Relaciones</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Vista de documentos y dependencias</p>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <button 
            @click="resetView()"
            class="group px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600 transition-all duration-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-600 dark:text-slate-300 group-hover:rotate-180 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Recentrar</span>
          </button>
          
          <button 
            @click="$wire.cerrar()"
            class="px-4 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2">
            <span>Cerrar</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      {{-- Leyenda Premium --}}
      <div class="px-8 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
        <div class="flex items-center gap-6">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipos de documento:</span>
          
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-800 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Factura</span>
            </div>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Pago</span>
            </div>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Nota crédito</span>
            </div>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-sky-200 dark:border-sky-800 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-sky-400 to-sky-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Entrega</span>
            </div>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-yellow-200 dark:border-yellow-800 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Orden</span>
            </div>
            
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
              <div class="w-3 h-3 rounded-full bg-gradient-to-br from-slate-400 to-slate-600 shadow-sm"></div>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Cliente</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Canvas Premium --}}
    <div class="p-8 bg-gradient-to-br from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
      <div x-ref="cy" class="h-[70vh] w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 shadow-inner"></div>
    </div>

    {{-- Footer Info --}}
    <div class="px-8 py-4 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
      <div class="flex items-center gap-6 text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Usa <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-mono text-[10px]">Scroll</kbd> para zoom</span>
        </div>
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
          </svg>
          <span>Arrastra para mover el canvas</span>
        </div>
      </div>
      
      <div class="flex items-center gap-2 text-xs">
        <span class="px-2 py-1 rounded bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium" x-text="nodeCount + ' documentos'"></span>
        <span class="px-2 py-1 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium" x-text="edgeCount + ' relaciones'"></span>
      </div>
    </div>
  </div>
</div>

<script>
  function mapaRelaciones() {
    let cy = null;

    const style = [
      // Nodos estilo SAP - Ultra profesional
      {
        selector: 'node',
        style: {
          'shape': 'roundrectangle',
          'background-color': '#ffffff',
          'background-opacity': 1,
          'border-width': 2,
          'border-color': '#CBD5E1',
          'border-opacity': 1,
          'padding': '18px',
          'label': 'data(label)',
          'text-wrap': 'wrap',
          'text-max-width': '160px',
          'text-valign': 'center',
          'text-halign': 'center',
          'font-size': 13,
          'font-weight': 600,
          'font-family': '"Inter", "Segoe UI", system-ui, -apple-system, sans-serif',
          'color': '#1e293b',
          'width': 180,
          'height': 85,
          'overlay-opacity': 0,
          'shadow-blur': 16,
          'shadow-color': '#00000012',
          'shadow-offset-x': 0,
          'shadow-offset-y': 4
        }
      },
      // Hover universal
      {
        selector: 'node:active',
        style: {
          'shadow-blur': 24,
          'shadow-color': '#00000025',
          'border-width': 3,
          'overlay-opacity': 0
        }
      },
      // FACTURA - Azul corporativo
      {
        selector: 'node[type="factura"]',
        style: {
          'background-color': '#EFF6FF',
          'border-color': '#3B82F6',
          'border-width': 3,
          'color': '#1E40AF',
          'background-gradient-stop-colors': '#EFF6FF #DBEAFE'
        }
      },
      // PAGO - Verde éxito
      {
        selector: 'node[type="pago"]',
        style: {
          'background-color': '#ECFDF5',
          'border-color': '#10B981',
          'border-width': 3,
          'color': '#047857'
        }
      },
      // NOTA CRÉDITO - Ámbar advertencia
      {
        selector: 'node[type="nc"]',
        style: {
          'background-color': '#FFFBEB',
          'border-color': '#F59E0B',
          'border-width': 3,
          'color': '#B45309'
        }
      },
      // ENTREGA - Celeste
      {
        selector: 'node[type="entrega"]',
        style: {
          'background-color': '#F0F9FF',
          'border-color': '#0EA5E9',
          'border-width': 3,
          'color': '#0369A1'
        }
      },
      // ORDEN - Amarillo
      {
        selector: 'node[type="orden"]',
        style: {
          'background-color': '#FEFCE8',
          'border-color': '#EAB308',
          'border-width': 3,
          'color': '#A16207'
        }
      },
      // CLIENTE - Gris neutro
      {
        selector: 'node[type="cliente"]',
        style: {
          'background-color': '#F8FAFC',
          'border-color': '#64748B',
          'border-width': 3,
          'color': '#334155'
        }
      },
      // Aristas estilo SAP
      {
        selector: 'edge',
        style: {
          'curve-style': 'bezier',
          'width': 2.5,
          'line-color': '#94A3B8',
          'line-opacity': 0.6,
          'target-arrow-shape': 'triangle',
          'target-arrow-color': '#94A3B8',
          'arrow-scale': 1.4,
          'label': 'data(label)',
          'font-size': 11,
          'font-weight': 600,
          'font-family': '"Inter", "Segoe UI", system-ui, sans-serif',
          'color': '#475569',
          'text-background-color': '#ffffff',
          'text-background-opacity': 0.95,
          'text-background-padding': 5,
          'text-background-shape': 'roundrectangle',
          'text-border-color': '#E2E8F0',
          'text-border-width': 1.5,
          'text-border-opacity': 1,
          'edge-text-rotation': 'autorotate',
          'text-margin-y': -8
        }
      },
      // Hover en aristas
      {
        selector: 'edge:active',
        style: {
          'line-color': '#64748B',
          'target-arrow-color': '#64748B',
          'width': 3.5,
          'line-opacity': 0.9
        }
      }
    ];

    function toElements(graph) {
      const els = [];
      (graph?.nodes || []).forEach(n => {
        const data = { ...(n.data ?? n) };
        if (data.sub) {
          data.label = data.label + '\n' + data.sub;
          delete data.sub;
        }
        els.push({ data });
      });
      (graph?.edges || []).forEach(e => els.push({ data: e.data ?? e }));
      return els;
    }

    function doRender(el, graph) {
      if (!window.cytoscape || !el) return;
      if (!graph?.nodes || graph.nodes.length === 0) return;

      if (cy) {
        try { cy.destroy(); } catch (e) {}
      }

      const elements = toElements(graph);
      
      cy = cytoscape({
        container: el,
        elements,
        style,
        layout: {
          name: 'dagre',
          rankDir: 'LR',
          nodeSep: 80,
          edgeSep: 40,
          rankSep: 140,
          padding: 50,
          animate: true,
          animationDuration: 400,
          animationEasing: 'ease-out-cubic'
        },
        wheelSensitivity: 0.12,
        minZoom: 0.3,
        maxZoom: 2.5
      });

      setTimeout(() => cy.fit(null, 60), 100);

      // Interacciones premium
      cy.on('mouseover', 'node', (e) => {
        e.target.style({
          'shadow-blur': 24,
          'shadow-color': '#00000025',
          'border-width': 3
        });
        e.target.connectedEdges().style({
          'line-color': '#3B82F6',
          'target-arrow-color': '#3B82F6',
          'width': 3.5,
          'line-opacity': 0.9
        });
      });

      cy.on('mouseout', 'node', (e) => {
        e.target.style({
          'shadow-blur': 16,
          'shadow-color': '#00000012',
          'border-width': 3
        });
        e.target.connectedEdges().style({
          'line-color': '#94A3B8',
          'target-arrow-color': '#94A3B8',
          'width': 2.5,
          'line-opacity': 0.6
        });
      });

      cy.on('mouseover', 'edge', (e) => {
        e.target.style({
          'line-color': '#3B82F6',
          'target-arrow-color': '#3B82F6',
          'width': 3.5,
          'line-opacity': 0.9
        });
      });

      cy.on('mouseout', 'edge', (e) => {
        e.target.style({
          'line-color': '#94A3B8',
          'target-arrow-color': '#94A3B8',
          'width': 2.5,
          'line-opacity': 0.6
        });
      });
    }

    return {
      nodeCount: 0,
      edgeCount: 0,
      
      init(open, graph) {
        this.nodeCount = graph?.nodes?.length || 0;
        this.edgeCount = graph?.edges?.length || 0;

        if (open) {
          this.$nextTick(() => doRender(this.$refs.cy, graph));
        }

        this.$watch('$wire.open', (isOpen) => {
          if (isOpen) {
            this.$nextTick(() => {
              const g = this.$wire.graph;
              this.nodeCount = g?.nodes?.length || 0;
              this.edgeCount = g?.edges?.length || 0;
              doRender(this.$refs.cy, g);
            });
          } else {
            if (cy) {
              try { cy.destroy(); } catch (e) {}
              cy = null;
            }
          }
        });
      },
      
      resetView() {
        if (cy) {
          cy.animate({
            fit: { padding: 60 },
            duration: 400,
            easing: 'ease-out-cubic'
          });
        }
      }
    };
  }
</script>