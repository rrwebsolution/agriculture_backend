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