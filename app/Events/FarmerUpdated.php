<?php

namespace App\Events;

use App\Models\Farmer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FarmerUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $farmer;
    public $type;

    public function __construct(Farmer $farmer, string $type = 'updated')
    {
        $this->farmer = $farmer;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('farmers-channel');
    }

    public function broadcastAs(): string
    {
        return 'FarmerUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            // 🌟 Tungod kay nag-fresh() ta sa controller, ready na ipadala ang model
            'farmer' => $this->farmer,
            'type' => $this->type,
        ];
    }
}