<?php

namespace App\Actions\Dashboard;

use Carbon\CarbonInterface;

interface DashboardWidget
{
    public function key(): string;

    public function ttl(): CarbonInterface;

    public function compute(): array;
}
