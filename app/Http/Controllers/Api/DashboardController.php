<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStorageInfo()
    {
        $dbSizeMb = 0;
        $connection = config('database.default');

        // 1. Calculate Database Size ACCURATELY in MB
        if ($connection === 'mysql') {
            $dbName = DB::connection()->getDatabaseName();
            
            // information_schema.TABLES stores data_length in BYTES.
            // Bytes / 1024 = KB. KB / 1024 = MB.
            $result = DB::select("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbName]);
            
            // Round to 2 decimal places (e.g., 2.15 MB)
            $dbSizeMb = round($result[0]->size_mb ?? 0, 2);
            
        } elseif ($connection === 'sqlite') {
            $dbPath = DB::connection()->getDatabaseName();
            if (file_exists($dbPath)) {
                // filesize() returns BYTES
                $dbSizeMb = round(filesize($dbPath) / 1024 / 1024, 2);
            }
        }

        // 2. Calculate Server Disk Space (Total Hard Drive)
        // disk_total_space returns BYTES. Bytes / 1073741824 = GB.
        $diskTotal = disk_total_space(base_path()); // Better to check the app folder path than '/'
        $diskFree = disk_free_space(base_path());
        $diskUsed = $diskTotal - $diskFree;
        
        $usagePercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                // We format it to always show 2 decimals, even if it's 2.00
                'database_size_mb' => number_format($dbSizeMb, 2),
                
                'server_storage' => [
                    'total_gb'      => round($diskTotal / 1073741824, 1),
                    'used_gb'       => round($diskUsed / 1073741824, 1),
                    'free_gb'       => round($diskFree / 1073741824, 1),
                    'usage_percent' => $usagePercent
                ]
            ]
        ], 200);
    }
}