<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpNotifier
{

    public static function notifyImportTrigger(): bool
    {
        try {
            Log::info('🔄 UMIS is triggering ERP import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports');

            Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('❌ Failed to notify ERP system', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
