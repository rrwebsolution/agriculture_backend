<?php

namespace App\Events;

use App\Models\TechnicianLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TechnicianLogUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $technicianLog;
    public $type;

    public function __construct(TechnicianLog $technicianLog, string $type = 'updated')
    {
        $this->technicianLog = $technicianLog;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('technician-logs-channel');
    }

    public function broadcastAs(): string
    {
        return 'TechnicianLogUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'technicianLog' => $this->technicianLog,
            'type' => $this->type,
        ];
    }
}
