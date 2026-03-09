<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use Illuminate\Http\Request;
use App\Events\ClusterUpdated;

class ClusterController extends Controller
{
    public function index()
    {
        // I-load ang users para ma-display sa View Dialog
        // Added latest() to sort Clusters by created_at DESC
        $clusters = Cluster::with('users')->latest()->get()->map(function ($cluster) {
            return [
                'id' => $cluster->id,
                'name' => $cluster->name,
                'description' => $cluster->description,
                'status' => $cluster->status,
                'staffCount' => $cluster->users->count(),
                'users' => $cluster->users // Para sa imong view modal
            ];
        });

        return response()->json(['data' => $clusters]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|in:Active,Inactive'
        ]);

        $cluster = Cluster::create($validated);
        event(new ClusterUpdated($cluster, 'created')); 
        return response()->json(['data' => $cluster], 201);
    }

    public function update(Request $request, $id)
    {
        $cluster = Cluster::findOrFail($id);
        $cluster->update($request->all());
        event(new ClusterUpdated($cluster, 'updated'));
        return response()->json(['data' => $cluster]);
    }

    public function destroy($id)
    {
        $cluster = Cluster::findOrFail($id);

        $cluster->delete();

        // 🔥 Broadcast delete event
        event(new ClusterUpdated($cluster, 'deleted'));

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
