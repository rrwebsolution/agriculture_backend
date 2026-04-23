<?php

namespace App\Events;

use App\Models\TechnicianLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TechnicianLogUpdated implements ShouldBroadcastNow
{
    public $technicianLog;
    public $type;

    public function __construct(TechnicianLog|array $technicianLog, string $type = 'updated')
    {
        $this->technicianLog = $technicianLog instanceof TechnicianLog
            ? $technicianLog->toArray()
            : $technicianLog;
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
