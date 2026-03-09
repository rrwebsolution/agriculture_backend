<?php

namespace App\Events;

use App\Models\Cluster;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ClusterUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $cluster;
    public $type;

    public function __construct(Cluster $cluster, string $type = 'updated')
    {
        $this->cluster = $cluster;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('clusters-channel');
    }

    public function broadcastAs(): string
    {
        return 'ClusterUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'cluster' => $this->cluster,
            'type' => $this->type,
        ];
    }
}