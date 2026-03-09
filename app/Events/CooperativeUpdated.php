<?php

namespace App\Events;

use App\Models\Cooperative;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class CooperativeUpdated implements ShouldBroadcastNow
{
    public $cooperative;
    public $type;

    public function __construct($cooperative, $type = 'updated')
    {
        $this->cooperative = $cooperative;
        $this->type = $type;
    }

    public function broadcastOn()
    {
        return new Channel('cooperatives-channel');
    }

    public function broadcastAs()
    {
        return 'CooperativeUpdated';
    }

    public function broadcastWith()
    {
        return [
            'cooperative' => $this->cooperative,
            'type' => $this->type,
        ];
    }
}