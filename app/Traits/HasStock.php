<?php

namespace App\Traits;

trait HasStock
{
    public function stockBajo(): bool
    {
        return $this->stock_minimo > 0 && $this->stock_actual <= $this->stock_minimo;
    }
}
