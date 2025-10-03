<?php
// app/Helpers/Money.php
namespace App\Helpers;

class Money
{
    public static function base(array $l): float
    {
        $cant  = max(0,(float)($l['cantidad'] ?? 0));
        $pre   = max(0,(float)($l['precio_unitario'] ?? 0));
        $desc  = max(0,(float)($l['descuento_pct'] ?? 0));
        return round($cant * $pre * (1 - $desc/100), 2);
    }

    public static function ivaImporte(array $l): float
    {
        $base = self::base($l);
        $ivaP = max(0,(float)($l['impuesto_pct'] ?? 0));
        return round($base * $ivaP/100, 2);
    }

    public static function totalLinea(array $l): float
    {
        return round(self::base($l) + self::ivaImporte($l), 2);
    }
    
}
