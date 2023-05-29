<?php

namespace AdminUI\AdminUIInstaller\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, InteractsWithBroadcasting;

    public string $key;
    public ?string $message;
    public ?string $details;
    public ?string $status;
    public ?int $step;
    public ?int $total;
    public ?string $error;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data)
    {
        $this->broadcastVia('soketi');
        $this->key = $data['key'];
        $this->step = $data['step'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->total = $data['total'] ?? null;
        $this->message = $data['msg'] ?? null;
        $this->details = $data['details'] ?? null;
        $this->error = $data['error'] ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('update.progress');
    }
}
