<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CropUpdated implements ShouldBroadcastNow
{
    public $crop;
    public $type;

    public function __construct($crop, $type = 'updated')
    {
        $this->crop = $crop;
        $this->type = $type;
    }

    public function broadcastOn()
    {
        return new Channel('crops-channel');
    }

    public function broadcastAs()
    {
        return 'CropUpdated';
    }

    public function broadcastWith()
    {
        return [
            'crop' => $this->crop,
            'type' => $this->type,
        ];
    }
}