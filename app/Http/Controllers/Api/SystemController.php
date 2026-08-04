<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function diskUsage(): JsonResponse
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'size' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'avail' => $this->formatBytes($free),
                'use_percent' => $percent,
            ],
        ], 200);
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return round($value, 1).' '.$units[$power];
    }
}
