<?php

namespace App\Events;

use App\Models\FisheryRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FisheryUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $fishery;
    public $type;

    public function __construct(FisheryRecord $fishery, string $type = 'updated')
    {
        $this->fishery = $fishery;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('fisheries-channel');
    }

    public function broadcastAs(): string
    {
        return 'FisheryUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'fishery' => $this->fishery,
            'type' => $this->type,
        ];
    }
}
