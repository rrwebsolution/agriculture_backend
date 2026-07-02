<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function recent()
    {
        $items = collect();

        // Farmers — first_name + last_name
        try {
            $items = $items->merge(
                DB::table('farmers')->select(
                    DB::raw("'farmer' as type"),
                    DB::raw("CONCAT('New farmer: ', first_name, ' ', last_name) as title"),
                    DB::raw("IFNULL(address_details, 'No address') as description"),
                    'created_at'
                )->latest('created_at')->limit(5)->get()
            );
        } catch (\Throwable $e) {}

        // Harvests — no unit column, uses quality
        try {
            $items = $items->merge(
                DB::table('harvests')
                    ->join('farmers', 'harvests.farmer_id', '=', 'farmers.id')
                    ->join('crops', 'harvests.crop_id', '=', 'crops.id')
                    ->select(
                        DB::raw("'harvest' as type"),
                        DB::raw("CONCAT('Harvest: ', crops.name) as title"),
                        DB::raw("CONCAT(farmers.first_name, ' ', farmers.last_name, ' — qty: ', harvests.quantity) as description"),
                        'harvests.created_at'
                    )->latest('harvests.created_at')->limit(5)->get()
            );
        } catch (\Throwable $e) {}

        // Expenses
        try {
            $items = $items->merge(
                DB::table('expenses')->select(
                    DB::raw("'expense' as type"),
                    DB::raw("CONCAT('Expense: ', category) as title"),
                    DB::raw("CONCAT('₱ ', FORMAT(amount, 2), IFNULL(CONCAT(' — ', remarks), '')) as description"),
                    'created_at'
                )->latest('created_at')->limit(5)->get()
            );
        } catch (\Throwable $e) {}

        // Nursery Records — crop_item is a string column, no join needed
        try {
            $items = $items->merge(
                DB::table('nursery_records')->select(
                    DB::raw("'nursery' as type"),
                    DB::raw("CONCAT('Nursery: ', activity) as title"),
                    DB::raw("CONCAT(crop_item, ' — qty: ', quantity, ' ', unit) as description"),
                    'created_at'
                )->latest('created_at')->limit(5)->get()
            );
        } catch (\Throwable $e) {}

        // Employee Logs — log_date + status, first_name/last_name
        try {
            $items = $items->merge(
                DB::table('technician_logs')
                    ->join('employees', 'technician_logs.employee_id', '=', 'employees.id')
                    ->select(
                        DB::raw("'log' as type"),
                        DB::raw("CONCAT('Log: ', employees.first_name, ' ', employees.last_name) as title"),
                        DB::raw("CONCAT(technician_logs.status, ' — ', technician_logs.log_date) as description"),
                        'technician_logs.created_at'
                    )->latest('technician_logs.created_at')->limit(5)->get()
            );
        } catch (\Throwable $e) {}

        $sorted = $items->sortByDesc('created_at')->take(15)->values()->map(function ($item) {
            return [
                'type'        => $item->type,
                'title'       => $item->title,
                'description' => $item->description,
                'time'        => $item->created_at,
            ];
        });

        return response()->json(['data' => $sorted]);
    }
}
