<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FreeUpTableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $table_number;

    /**
     * Create a new event instance.
     */
    public function __construct($table_number)
    {
        $this->table_number = $table_number;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['emenu-website-client'];
    }

    public function broadcastAs()
    {
        return 'free-up-table';
    }
}
