<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatusChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    private array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(string $view, string $subject, array $data = [])
    {
        $this->view = $view;
        $this->subject = $subject;
        $this->data = $data;
    }

    public function build(): static
    {
        return $this->subject($this->subject)->markdown($this->view)
            ->with($this->data);
    }
}
