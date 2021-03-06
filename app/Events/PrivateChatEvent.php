<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PrivateChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $content;
    public $image;
    public $stringlength;
    public $chat;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($content, $image, $stringlength, $chat)
    {
        $this->content = $content;
        $this->image = $image;
        
        $this->stringlength = $stringlength;
        $this->chat = $chat;
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('Chat.' . $this->chat['session_id']);
    }
}
