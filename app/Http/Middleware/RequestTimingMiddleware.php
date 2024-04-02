<?php

namespace App\Http\Middleware;

use App\Models\SystemLogs;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestTimingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        if ($response->status() >= 200 && $response->status() < 300) {
            $end = microtime(true);
            $executionTime = round(($end - $start) * 1000, 2); // Time in milliseconds

            // Get the log message from the response data
            $logs = $response->getData()->logs ?? [];

            if($logs !== []){
                // Store log data in the database
                SystemLogs::create([...(array) $logs,'execution_time' => $executionTime]);

                $responseData = json_decode($response->getContent(), true);
                unset($responseData['logs']);
                $response->setContent(json_encode($responseData));
            }
        }

        return $response;
    }
}
