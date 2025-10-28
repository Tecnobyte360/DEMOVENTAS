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
        $this->graph = $this->buildGraph($facturaId);
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
        $money = fn($v) => number_format((float)$v, 2, ',', '.');

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 📄 FACTURA (Nodo Central)
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        $idFactura = "F{$f->id}";
        $len = $f->serie?->longitud ?? 6;
        $num = str_pad((string)($f->numero ?? 0), $len, '0', STR_PAD_LEFT);

        $push($nodes, [
            'id'       => $idFactura,
            'tipo'     => 'Factura',
            'numero'   => "{$f->prefijo}-{$num}",
            'monto'    => "$" . $money($f->total),
            'fecha'    => $f->fecha ?? '',
            'type'     => 'factura',
            'icon'     => '📄',
        ]);

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 👤 CLIENTE
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if ($f->cliente) {
            $idCliente = "C{$f->cliente->id}";
            $razon = strlen($f->cliente->razon_social) > 35 
                ? substr($f->cliente->razon_social, 0, 32) . '...' 
                : $f->cliente->razon_social;
            
            $push($nodes, [
                'id'       => $idCliente,
                'tipo'     => 'Cliente',
                'numero'   => $razon,
                'monto'    => $f->cliente->nit ? "NIT {$f->cliente->nit}" : '',
                'fecha'    => '',
                'type'     => 'cliente',
                'icon'     => '👤',
            ]);
            $push($edges, [
                'source' => $idCliente,
                'target' => $idFactura,
                'label'  => 'emite'
            ]);
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔄 NOTAS DE CRÉDITO
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (method_exists($f, 'notasCredito')) {
            foreach ($f->notasCredito as $nc) {
                $nid = "NC{$nc->id}";
                $numNc = str_pad($nc->numero ?? 0, 6, '0', STR_PAD_LEFT);
                $push($nodes, [
                    'id'       => $nid,
                    'tipo'     => 'Nota Crédito',
                    'numero'   => "{$nc->prefijo}-{$numNc}",
                    'monto'    => "$" . $money($nc->total),
                    'fecha'    => $nc->fecha ?? '',
                    'type'     => 'nc',
                    'icon'     => '🔄',
                ]);
                $push($edges, [
                    'source' => $nid,
                    'target' => $idFactura,
                    'label'  => 'afecta'
                ]);
            }
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 💰 PAGOS
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (method_exists($f, 'pagos')) {
            foreach ($f->pagos as $pago) {
                $pid = "P{$pago->id}";
                $push($nodes, [
                    'id'       => $pid,
                    'tipo'     => 'Pago',
                    'numero'   => "Recibo #{$pago->id}",
                    'monto'    => "$" . $money($pago->monto),
                    'fecha'    => $pago->fecha ?? '',
                    'type'     => 'pago',
                    'icon'     => '💰',
                ]);
                $push($edges, [
                    'source' => $pid,
                    'target' => $idFactura,
                    'label'  => 'abona'
                ]);
            }
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 📦 ENTREGAS
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (method_exists($f, 'entregas')) {
            foreach ($f->entregas as $ent) {
                $eid = "E{$ent->id}";
                $push($nodes, [
                    'id'       => $eid,
                    'tipo'     => 'Entrega',
                    'numero'   => "Remisión #{$ent->id}",
                    'monto'    => '',
                    'fecha'    => $ent->fecha ?? '',
                    'type'     => 'entrega',
                    'icon'     => '📦',
                ]);
                $push($edges, [
                    'source' => $idFactura,
                    'target' => $eid,
                    'label'  => 'genera'
                ]);
            }
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 📋 ORDEN DE VENTA
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (method_exists($f, 'ordenVenta') && $f->ordenVenta) {
            $ov = $f->ordenVenta;
            $oid = "OV{$ov->id}";
            $push($nodes, [
                'id'       => $oid,
                'tipo'     => 'Orden de Venta',
                'numero'   => "OV-{$ov->id}",
                'monto'    => '',
                'fecha'    => $ov->fecha ?? '',
                'type'     => 'orden',
                'icon'     => '📋',
            ]);
            $push($edges, [
                'source' => $oid,
                'target' => $idFactura,
                'label'  => 'origina'
            ]);
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    public function render()
    {
        return view('livewire.mapa-relacion.mapa-relaciones');
    }
}