<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class PlantingUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $planting;
    public $action; // 'created', 'updated', or 'deleted'

    public function __construct($planting, string $action = 'updated')
    {
        // 🌟 I-ensure nato nga loaded ang relationships para kompleto ang data sa frontend
        if (is_object($planting) && method_exists($planting, 'load')) {
            $planting->load(['farmer', 'barangay', 'crop', 'statusHistory']);
        }
        
        $this->planting = $planting;
        $this->action = $action;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('plantings-channel');
    }

    public function broadcastAs(): string
    {
        return 'PlantingUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'planting' => $this->planting,
            'action'   => $this->action,
        ];
    }
}