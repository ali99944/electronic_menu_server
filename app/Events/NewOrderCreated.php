<?php

namespace App\Events;

use App\Models\Order; // Assuming you have an Order model
use App\Models\Orders;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Keep for potential future WebSocket use
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Support\Arrayable; // Import Arrayable


class NewOrderCreated implements ShouldBroadcastNow // Implement Arrayable for easy serialization
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Orders $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Orders $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return ['orders-channel'];
    }

    public function broadcastAs()
    {
        return 'order.created';
    }
}
