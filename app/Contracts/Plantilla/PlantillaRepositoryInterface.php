<?php

namespace App\Contracts\Plantilla;

use App\Models\Plantilla;

interface PlantillaRepositoryInterface
{
    public function store(Plantilla $plantilla): Plantilla;
}