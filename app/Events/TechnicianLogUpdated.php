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

    private function sanitizeTechnicianLogPayload(array $technicianLog): array
    {
        unset($technicianLog['verification_photo']);

        if (isset($technicianLog['employee']) && is_array($technicianLog['employee'])) {
            $technicianLog['employee'] = array_intersect_key($technicianLog['employee'], array_flip([
                'id',
                'employee_no',
                'first_name',
                'last_name',
                'position',
                'division',
                'department',
                'work_location',
                'email',
            ]));
        }

        return $technicianLog;
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
            'technicianLog' => $this->sanitizeTechnicianLogPayload($this->technicianLog),
            'type' => $this->type,
        ];
    }
}
