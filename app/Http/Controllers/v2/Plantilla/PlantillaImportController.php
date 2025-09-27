<?php

namespace App\Http\Controllers\v2\Plantilla;

use App\Services\Plantilla\ImportPlantillaService;
use Illuminate\Http\Request;

class PlantillaImportController
{
    public function __construct(
        private ImportPlantillaService $importPlantillaService
    ) {}

    public function __invoke(Request $request)
    {
        $this->importPlantillaService->handle($request);
    }
}