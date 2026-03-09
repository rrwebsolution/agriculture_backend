<?php

namespace App\Events;

use App\Models\Fisherfolk;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FisherfolkUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $fisherfolk;
    public $type;

    public function __construct(Fisherfolk $fisherfolk, string $type)
    {
        $this->fisherfolk = $fisherfolk->load('barangay');
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('fisherfolks-channel');
    }

    public function broadcastAs(): string
    {
        return 'FisherfolkUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'fisherfolk' => $this->fisherfolk,
            'type' => $this->type,
        ];
    }
}