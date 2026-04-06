<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets; // 🌟 Added
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable; // 🌟 Added
use Illuminate\Queue\SerializesModels;

class BarangayUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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