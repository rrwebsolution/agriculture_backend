<?php

namespace App\Events;

use App\Models\Harvest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class HarvestUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $harvest;
    public $type;

    public function __construct(Harvest $harvest, string $type = 'updated')
    {
        $this->harvest = $harvest->load(['farmer', 'barangay', 'crop']);
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('harvests-channel');
    }

    public function broadcastAs(): string
    {
        return 'HarvestUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'harvest' => $this->harvest,
            'type' => $this->type,
        ];
    }
}
