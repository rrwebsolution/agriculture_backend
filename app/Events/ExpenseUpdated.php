<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ExpenseUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $expense;
    public $type;

    public function __construct(Expense $expense, string $type = 'updated')
    {
        $this->expense = $expense;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('expenses-channel');
    }

    public function broadcastAs(): string
    {
        return 'ExpenseUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'expense' => $this->expense,
            'type' => $this->type,
        ];
    }
}
