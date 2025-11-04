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
    :root{
      --grad-1: linear-gradient(135deg,#111827 0%,#0b1020 35%,#141a33 70%,#0f172a 100%);
      --panel-grad: linear-gradient(180deg, rgba(255,255,255,.85) 0%, rgba(255,255,255,.75) 100%);
      --panel-grad-dark: linear-gradient(180deg, rgba(2,6,23,.7) 0%, rgba(2,6,23,.6) 100%);
    }
    @keyframes floaty {
      0% { transform: translateY(0) translateX(0) scale(1); opacity: .5; }
      50% { transform: translateY(-14px) translateX(6px) scale(1.03); opacity: .8; }
      100% { transform: translateY(0) translateX(0) scale(1); opacity: .5; }
    }
    @keyframes fadeInScale {
      from { opacity:0; transform: scale(.98) translateY(8px) }
      to   { opacity:1; transform: scale(1) translateY(0) }
    }
    .mapa-modal { animation: fadeInScale .3s ease-out; }
    .glass {
      background: var(--panel-grad);
      -webkit-backdrop-filter: blur(10px);
      backdrop-filter: blur(10px);
    }
    .dark .glass{
      background: var(--panel-grad-dark);
      border-color: rgba(255,255,255,.06) !important;
    }
    .legend-chip { transition: transform .15s ease, box-shadow .15s ease }
    .legend-chip:hover { transform: translateY(-1px) scale(1.02) }
    .vignette:before{
      content:""; position:absolute; inset:-20%;
      pointer-events:none;
      background:
        radial-gradient(60% 60% at 50% 50%, rgba(255,255,255,.08) 0%, transparent 60%),
        radial-gradient(120% 90% at 60% -10%, rgba(99,102,241,.12) 0%, transparent 60%),
        radial-gradient(100% 100% at -10% 60%, rgba(236,72,153,.12) 0%, transparent 60%);
      filter: blur(20px);
      animation: floaty 10s ease-in-out infinite;
    }
    .vignette:after{
      content:""; position:absolute; inset:0;
      background: radial-gradient(80% 80% at 50% 50%, transparent 60%, rgba(0,0,0,.45) 100%);
      pointer-events:none;
    }
    .toolbar-btn{
      @apply px-3 py-2 rounded-xl text-sm font-semibold shadow-md border border-white/20 transition;
    }
    .toolbar-btn:hover{ transform: translateY(-1px); }
  </style>
@endassets

<div x-data="mapaRelaciones()"
     x-init="init($wire.open, @js($graph))"
     x-show="$wire.open"
     x-cloak
     class="fixed inset-0 z-[100]">

  {{-- Backdrop con viñeta y blobs --}}
  <div class="absolute inset-0 vignette" style="background: var(--grad-1);" @click="$wire.cerrar()"></div>

  {{-- Dialog --}}
  <div class="relative z-10 w-full max-w-[95vw] h-[95vh] mx-auto mt-[2.5vh] rounded-3xl overflow-hidden mapa-modal border border-white/10">
    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-white/5 dark:from-white/5 dark:to-white/0 pointer-events-none"></div>

   


    {{-- Canvas Cytoscape --}}
    <div class="p-6 h-[calc(95vh-220px)] bg-gradient-to-br from-white/70 to-slate-50/70 dark:from-slate-950/60 dark:to-slate-900/60">
      <div class="relative h-full">
        {{-- Grid decorativo --}}
        <div class="absolute inset-0 rounded-2xl opacity-25 dark:opacity-15"
             style="background-image: radial-gradient(circle, #94a3b8 1px, transparent 1px); background-size: 38px 38px;"></div>

        {{-- Toolbar flotante --}}
        <div class="absolute top-4 right-4 z-20 flex gap-2 glass border border-white/20 p-2 rounded-2xl shadow-xl">
          <button class="toolbar-btn bg-white/30 text-white hover:bg-white/40" @click="zoomIn()">＋</button>
          <button class="toolbar-btn bg-white/30 text-white hover:bg-white/40" @click="zoomOut()">－</button>
          <button class="toolbar-btn bg-indigo-600 text-white hover:bg-indigo-700" @click="resetView()">🎯</button>
          <button class="toolbar-btn bg-purple-600 text-white hover:bg-purple-700" @click="toggleLayout()">↕︎ Layout</button>
          <button class="toolbar-btn bg-emerald-600 text-white hover:bg-emerald-700" @click="downloadPNG()">⬇ PNG</button>
        </div>

        <div x-ref="cy"
             class="relative h-full w-full rounded-2xl border-2 border-white/40 dark:border-white/10 bg-gradient-to-br from-white to-slate-50 dark:from-slate-950 dark:to-slate-900 shadow-2xl overflow-hidden"></div>
      </div>
    </div>

   
  </div>
</div>

<script>
  function mapaRelaciones() {
    let cy = null;
    let currentLayout = 'dagre';

    const style = [
      /* ====== Base de nodos (tarjeta “glass”) ====== */
      {
        selector: 'node',
        style: {
          'shape': 'round-rectangle',
          'background-color': '#ffffff',
          'background-opacity': 0.9,
          'border-width': 2,
          'border-color': '#e5e7eb',
          'padding': '24px',
          'label': ele => {
            const icon  = ele.data('icon')   ?? '';
            const tipo  = ele.data('tipo')   ?? '';
            const num   = ele.data('numero') ?? '';
            const monto = ele.data('monto')  ?? '';
            const fecha = ele.data('fecha')  ?? '';
            let label = `${icon}\n\n${(tipo||'').toUpperCase()}`;
            if (num)   label += `\n━━━━━━━━━\n${num}`;
            if (monto) label += `\n${monto}`;
            if (fecha) label += `\n📅 ${fecha}`;
            return label;
          },
          'text-wrap': 'wrap',
          'text-max-width': '220px',
          'text-valign': 'center',
          'text-halign': 'center',
          'font-size': 11,
          'font-weight': 800,
          'font-family': 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto',
          'color': '#0f172a',
          'width': '240px',
          'height': '300px',
          'text-margin-y': 0,
          'overlay-opacity': 0,
          'shadow-blur': 28,
          'shadow-color': '#00000030',
          'shadow-offset-x': 0,
          'shadow-offset-y': 14,
          'transition-property': 'shadow-blur, shadow-offset-y, background-color, border-color, width, height',
          'transition-duration': '400ms',
          /* brillo superior sutil */
          'background-gradient-stop-colors': '#ffffff #f8fafc #ffffff',
          'background-gradient-stop-positions': '0% 92% 100%',
        }
      },
      { selector: 'node:selected',
        style: { 'border-color': '#6366f1', 'border-width': 4, 'shadow-blur': 40, 'shadow-color':'#6366f155' } },
      { selector: 'node:active',
        style: { 'shadow-blur': 46, 'shadow-color': '#00000045', 'shadow-offset-y': 4 } },

      /* ====== Estilos por tipo ====== */
      { selector: 'node[type="factura"]',
        style: {
          'background-gradient-stop-colors': '#eef2ff #e0e7ff #eef2ff',
          'border-color': '#6366f1','border-width': 8,'font-size': 12,'font-weight': 900,
          'color': '#111827','shadow-color': '#6366f14f','width':'252px','height':'312px'
        }},
      { selector: 'node[type="pago"]',
        style: {'background-gradient-stop-colors': '#ecfdf5 #d1fae5 #ecfdf5','border-color':'#10b981','border-width':6,'color':'#064e3b'}},
      { selector: 'node[type="nc"]',
        style: {'background-gradient-stop-colors': '#fffbeb #fef3c7 #fffbeb','border-color':'#f59e0b','border-width':6,'color':'#78350f'}},
      { selector: 'node[type="entrega"]',
        style: {'background-gradient-stop-colors': '#f0f9ff #e0f2fe #f0f9ff','border-color':'#0ea5e9','border-width':6,'color':'#0c4a6e'}},
      { selector: 'node[type="orden"]',
        style: {'background-gradient-stop-colors': '#fefce8 #fef9c3 #fefce8','border-color':'#eab308','border-width':6,'color':'#713f12'}},
      { selector: 'node[type="cliente"]',
        style: { 'shape':'ellipse','width':'210px','height':'210px',
                 'background-gradient-stop-colors':'#f9fafb #f3f4f6 #f9fafb',
                 'border-color':'#6b7280','border-width':6,'color':'#111827'}},

      /* ====== Aristas con gradiente y etiquetas ====== */
      {
        selector: 'edge',
        style: {
          'curve-style': 'unbundled-bezier',
          'control-point-distances': [50, -50],
          'control-point-weights': [0.2, 0.8],
          'width': 4,
          'line-color': '#94a3b8',
          'line-opacity': 0.9,
          'line-gradient-stop-colors': '#94a3b8 #64748b',
          'line-gradient-stop-positions': '0 100%',
          'target-arrow-shape': 'triangle',
          'target-arrow-color': '#64748b',
          'arrow-scale': 2.2,
          'label': 'data(label)',
          'font-size': 11,
          'font-weight': 900,
          'color': '#0f172a',
          'text-background-color': '#ffffff',
          'text-background-opacity': 0.96,
          'text-background-padding': 8,
          'text-background-shape': 'round-rectangle',
          'text-border-color': '#cbd5e1',
          'text-border-width': 2,
          'text-border-opacity': 1,
          'edge-text-rotation': 'autorotate',
          'text-margin-y': -10,
        }
      },
      { selector: 'edge:active',
        style: { 'line-color': '#334155', 'target-arrow-color': '#334155', 'width': 6 } },

      /* Atenuar (para filtros) */
      { selector: '.dim', style: { 'opacity': 0.18 } }
    ];

    const legend = [
      { type:'factura', label:'Factura',   icon:'📄', bg:['#6366f1','#8b5cf6'], active:true },
      { type:'pago',    label:'Pago',      icon:'💰', bg:['#10b981','#34d399'], active:true },
      { type:'nc',      label:'Nota Crédito', icon:'🔄', bg:['#f59e0b','#fbbf24'], active:true },
      { type:'entrega', label:'Entrega',   icon:'📦', bg:['#0ea5e9','#38bdf8'], active:true },
      { type:'orden',   label:'Orden',     icon:'📋', bg:['#eab308','#fde047'], active:true },
      { type:'cliente', label:'Cliente',   icon:'👤', bg:['#6b7280','#9ca3af'], active:true },
    ];

    const state = {
      searchTerm: '',
      activeTypes: new Set(legend.map(l => l.type)),
    };

    function toElements(graph) {
      const els = [];
      (graph?.nodes || []).forEach(n => els.push({ data: n.data ?? n }));
      (graph?.edges || []).forEach(e => els.push({ data: e.data ?? e }));
      return els;
    }

    function layoutConfig(name){
      if(name === 'dagre'){
        return {
          name: 'dagre',
          rankDir: 'TB', nodeSep: 120, edgeSep: 60, rankSep: 180,
          padding: 80, animate: true, animationDuration: 600, animationEasing: 'ease-out-cubic'
        }
      }
      // alternativo “concentric” para explorar
      return {
        name: 'concentric',
        concentric: node => node.indegree()+node.outdegree(),
        levelWidth: () => 2,
        minNodeSpacing: 100,
        padding: 80,
        animate: true,
        animationDuration: 600,
        animationEasing: 'ease-out-cubic'
      }
    }

    function doRender(el, graph) {
      if (!window.cytoscape || !el || !graph?.nodes?.length) return;

      if (cy) { try { cy.destroy(); } catch (e) {} }

      cy = cytoscape({
        container: el,
        elements: toElements(graph),
        style,
        layout: layoutConfig(currentLayout),
        wheelSensitivity: 0.15,
        minZoom: 0.2,
        maxZoom: 2.6
      });

      // Animación de entrada
      cy.nodes().forEach((node, i) => {
        node.style('opacity', 0);
        setTimeout(() => node.animate({ style: { opacity: 1 }, duration: 450, easing: 'ease-out' }), i * 90);
      });
      setTimeout(() => cy.fit(null, 90), 220);

      // Interacción
      cy.on('mouseover', 'node', e => {
        const node = e.target;
        node.style({ 'shadow-blur': 46, 'shadow-offset-y': 6 });
        node.neighborhood('edge').animate({ style: { 'width': 6, 'line-color': '#475569','target-arrow-color':'#334155' }, duration: 180 });
      });
      cy.on('mouseout', 'node', e => {
        const node = e.target;
        node.style({ 'shadow-blur': 28, 'shadow-offset-y': 14 });
        node.neighborhood('edge').animate({ style: { 'width': 4, 'line-color': '#94a3b8','target-arrow-color':'#64748b' }, duration: 180 });
      });
      cy.on('tap', 'node', e => {
        const node = e.target;
        // pulso sutil
        node.animate({ style: { 'shadow-blur': 60, 'shadow-offset-y': 0 }, duration: 140, complete: () => {
          node.animate({ style: { 'shadow-blur': 28, 'shadow-offset-y': 14 }, duration: 160 });
        }});
        // Puedes abrir detalle aquí si quieres:
        // Livewire.emit('abrirDocumento', node.data());
        console.log('Documento:', node.data());
      });
    }

    function applyFilters(){
      if(!cy) return;
      const active = state.activeTypes;
      cy.nodes().forEach(n => {
        const type = n.data('type') || n.data('tipo') || n.data('Tipo');
        if(!type) return;
        if(active.has(type)){ n.removeClass('dim'); }
        else { n.addClass('dim'); }
      });
      // atenúa edges cuya fuente o destino esté atenuado
      cy.edges().forEach(e => {
        const dim = e.source().hasClass('dim') || e.target().hasClass('dim');
        e.toggleClass('dim', dim);
      });
    }

    function highlightByNumber(term){
      if(!cy) return;
      const t = (term||'').toString().trim().toLowerCase();
      cy.nodes().removeClass('selected');
      if(!t){ cy.fit(null, 90); return; }

      // resaltar por data('numero')
      const matches = cy.nodes().filter(n => (n.data('numero')||'').toString().toLowerCase().includes(t));
      if(matches.length){
        matches.select();
        cy.animate({ fit: { eles: matches, padding: 120 }, duration: 500 });
      }
    }

    return {
      legend,
      state,

      init(open, graph) {
        if (open) {
          this.$nextTick(() => doRender(this.$refs.cy, graph));
        }
        this.$watch('$wire.open', isOpen => {
          if (isOpen) {
            this.$nextTick(() => doRender(this.$refs.cy, this.$wire.graph));
          } else {
            if (cy) { try { cy.destroy(); } catch(e){} cy = null; }
          }
        });
      },

      /* Toolbar actions */
      zoomIn(){ if(cy) cy.zoom({ level: cy.zoom()*1.15, renderedPosition: {x: cy.width()/2, y: cy.height()/2} }); },
      zoomOut(){ if(cy) cy.zoom({ level: cy.zoom()/1.15, renderedPosition: {x: cy.width()/2, y: cy.height()/2} }); },
      resetView(){ if(cy) cy.animate({ fit: { padding: 90 }, duration: 600, easing: 'easeInOutCubic' }); },
      toggleLayout(){
        if(!cy) return;
        currentLayout = currentLayout === 'dagre' ? 'concentric' : 'dagre';
        cy.layout(layoutConfig(currentLayout)).run();
        setTimeout(() => cy.fit(null, 90), 250);
      },
      downloadPNG(){
        if(!cy) return;
        const png = cy.png({ bg: '#ffffff', full: true, maxWidth: 4000, maxHeight: 4000, scale: 2 });
        const a = document.createElement('a');
        a.href = png; a.download = `mapa-relaciones-${Date.now()}.png`; a.click();
      },

      /* Legend filters */
      toggleType(type){
        const chip = this.legend.find(c => c.type === type);
        chip.active = !chip.active;
        if(chip.active) state.activeTypes.add(type); else state.activeTypes.delete(type);
        applyFilters();
      },
      resetFilters(){
        this.legend.forEach(c => { c.active = true; state.activeTypes.add(c.type); });
        applyFilters();
      },

      /* Search */
      searchNumber(){ highlightByNumber(this.state.searchTerm); },
    };
  }
</script>
