<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

     private array $data;

     /**
      * Create a new message instance.
      *
      * @return void
      */
     public function __construct(string $view, string $subject, array $data = [])
     {
         $this->view = $view;
         $this->subject = $subject;
         $this->data = $data;
     }
 
     /**
      * Build the message.
      */
     public function build(): static
     {
 
         return $this->subject($this->subject)->markdown($this->view)
             ->with($this->data);
     }
 }
 