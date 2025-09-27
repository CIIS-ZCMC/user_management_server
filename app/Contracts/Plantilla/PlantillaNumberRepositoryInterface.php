<?php

namespace App\Contracts\Plantilla;

use App\Models\PlantillaNumber;

interface PlantillaNumberRepositoryInterface
{
    public function store(PlantillaNumber $plantillaNumber): PlantillaNumber;
}
