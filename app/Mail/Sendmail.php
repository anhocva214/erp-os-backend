<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\View;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class Sendmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailData['title'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function build(): Sendmail
    {

        if ($this->mailData['title'] == 'Forget Password') {
            $this->subject('Forget Password');
            $view2Content = View::make('forgetPass', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view2Content);
        } elseif ($this->mailData['title'] == 'New Account') {
            $this->subject('New Account');
            $view3Content = View::make('newAccount', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view3Content);
        } else if ($this->mailData['title'] == 'Invoice') {
            $this->subject('Invoice');
            $view4Content = View::make('invoiceView', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view4Content);
        } else if ($this->mailData['title'] == 'quote') {
            $this->subject('Quote');

            $view5Content = View::make('quote', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view5Content);
        } else if ($this->mailData['title'] == 'Purchase Reorder Invoice') {
            $this->subject('Purchase Reorder Invoice');
            $view6Content = View::make('purchaseReorderInvoice', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view6Content);
        } else if ($this->mailData['title'] == "request forget password") {
            $this->subject('Request for Forget Password');
            $view7Content = View::make('requestforgetpass', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view7Content);
        } else {
            $this->subject($this->mailData['title']);
            $view1Content = View::make('email', ['mailData' => $this->mailData])->render();
            return $this->subject($this->mailData['title'])
                ->html($view1Content);
        }
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        if (isset($this->mailData['attachment'])) {
            if (($this->mailData['attachment'])) {
                $attachments = [];

                foreach ($this->mailData['attachment'] as $attachmentPath) {
                    $attachments[] = Attachment::fromPath($attachmentPath);
                }

                return $attachments;
            }
        }

        return [];
    }
}
