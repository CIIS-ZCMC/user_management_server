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

    public static function notifySectionImport(): bool
    {
        try {
            Log::info('🔄 UMIS is triggering ERP section import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'sections'
            ]);

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

    public static function notifyDepartmentImport(): bool
    {
        try {
            \Log::info('🔄 UMIS is triggering ERP department import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'departments',
            ]);

            \Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            \Log::error('❌ Failed to notify ERP system (departments)', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyDivisionImport(): bool
    {
        try {
            \Log::info('🔄 UMIS is triggering ERP division import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'divisions',
            ]);

            \Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            \Log::error('❌ Failed to notify ERP system (divisions)', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyUnitImport(): bool
    {
        try {
            \Log::info('🔄 UMIS is triggering ERP unit import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'units',
            ]);

            \Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            \Log::error('❌ Failed to notify ERP system (units)', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyUserImport(): bool
    {
        try {
            \Log::info('🔄 UMIS is triggering ERP user import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'users',
            ]);

            \Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            \Log::error('❌ Failed to notify ERP system (users)', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function notifyAssignedAreaImport(): bool
    {
        try {
            \Log::info('🔄 UMIS is triggering ERP assigned area import');

            $response = Http::withHeaders([
                'X-UMIS-SECRET' => config('services.umis.secret'),
            ])->post(config('services.umis.erp_url') . '/api/trigger-imports', [
                'type' => 'assigned_areas',
            ]);

            \Log::info('📬 ERP response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            \Log::error('❌ Failed to notify ERP system (assigned areas)', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
