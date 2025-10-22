<?php

namespace App\Livewire\MapaRelacion;

use Livewire\Component;
use App\Models\Factura\Factura;

class MapaRelaciones extends Component
{
    public bool $open = false;
    public ?int $facturaId = null;
    public array $graph = ['nodes'=>[], 'edges'=>[]];

    protected $listeners = ['abrir-mapa' => 'abrir'];

    public function abrir(int $facturaId): void
    {
        $this->facturaId = $facturaId;
        
        // Construir el grafo ANTES de abrir
        $this->graph = $this->buildGraph($facturaId);
        
        // Ahora sí abrir el modal
        $this->open = true;
    }

    public function cerrar(): void
    {
        $this->reset(['open','facturaId','graph']);
    }

    protected function buildGraph(int $id): array
    {
        $f = Factura::with(['cliente:id,razon_social,nit', 'serie:id,longitud'])
            ->find($id);

        if (!$f) {
            return ['nodes' => [], 'edges' => []];
        }

        $nodes = [];
        $edges = [];

        $push = fn(&$arr, $data) => $arr[] = ['data' => $data];
        $money = fn($v) => number_format((float)$v, 2);

        // ---- FACTURA ----
        $idFactura = "F{$f->id}";
        $len = $f->serie?->longitud ?? 6;
        $num = str_pad((string)($f->numero ?? 0), $len, '0', STR_PAD_LEFT);

        $push($nodes, [
            'id'    => $idFactura,
            'label' => "Factura {$f->prefijo}-{$num}",
            'sub'   => "Total: $" . $money($f->total),
            'type'  => 'factura',
        ]);

        // ---- CLIENTE ----
        if ($f->cliente) {
            $idCliente = "C{$f->cliente->id}";
            $push($nodes, [
                'id'    => $idCliente,
                'label' => $f->cliente->razon_social,
                'sub'   => $f->cliente->nit ? "NIT: {$f->cliente->nit}" : '',
                'type'  => 'cliente',
            ]);
            $push($edges, [
                'source' => $idCliente,
                'target' => $idFactura,
                'label'  => 'Factura a'
            ]);
        }

        // ---- NOTAS DE CRÉDITO ----
        if (method_exists($f, 'notasCredito')) {
            foreach ($f->notasCredito as $nc) {
                $nid = "NC{$nc->id}";
                $push($nodes, [
                    'id'    => $nid,
                    'label' => "NC {$nc->prefijo}-" . str_pad($nc->numero ?? 0, 6, '0', STR_PAD_LEFT),
                    'sub'   => "Total: $" . $money($nc->total),
                    'type'  => 'nc',
                ]);
                $push($edges, [
                    'source' => $nid,
                    'target' => $idFactura,
                    'label'  => 'Afecta'
                ]);
            }
        }

        // ---- PAGOS ----
        if (method_exists($f, 'pagos')) {
            foreach ($f->pagos as $pago) {
                $pid = "P{$pago->id}";
                $push($nodes, [
                    'id'    => $pid,
                    'label' => "Pago #{$pago->id}",
                    'sub'   => "Monto: $" . $money($pago->monto),
                    'type'  => 'pago',
                ]);
                $push($edges, [
                    'source' => $pid,
                    'target' => $idFactura,
                    'label'  => 'Abona a'
                ]);
            }
        }

        // ---- ENTREGAS ----
        if (method_exists($f, 'entregas')) {
            foreach ($f->entregas as $ent) {
                $eid = "E{$ent->id}";
                $push($nodes, [
                    'id'    => $eid,
                    'label' => "Entrega #{$ent->id}",
                    'sub'   => $ent->fecha ? "Fecha: {$ent->fecha}" : '',
                    'type'  => 'entrega',
                ]);
                $push($edges, [
                    'source' => $idFactura,
                    'target' => $eid,
                    'label'  => 'Genera'
                ]);
            }
        }

        // ---- ORDEN DE VENTA ----
        if (method_exists($f, 'ordenVenta') && $f->ordenVenta) {
            $ov = $f->ordenVenta;
            $oid = "OV{$ov->id}";
            $push($nodes, [
                'id'    => $oid,
                'label' => "Orden #{$ov->id}",
                'sub'   => $ov->fecha ? "Fecha: {$ov->fecha}" : '',
                'type'  => 'orden',
            ]);
            $push($edges, [
                'source' => $oid,
                'target' => $idFactura,
                'label'  => 'Genera'
            ]);
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    public function render()
    {
        return view('livewire.mapa-relacion.mapa-relaciones');
    }
}