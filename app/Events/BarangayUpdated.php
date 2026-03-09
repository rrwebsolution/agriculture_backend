<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BarangayUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $barangay;
    public $type;

    public function __construct($barangay, string $type = 'updated')
    {
        $this->barangay = $barangay;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('barangays-channel');
    }

    public function broadcastAs(): string
    {
        return 'BarangayUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'barangay' => $this->barangay,
            'type' => $this->type,
        ];
    }
}