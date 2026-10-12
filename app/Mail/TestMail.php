<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Safe delivery probe: fixed subject/body shape, no business data, used by
 * Settings → "Send test email" and the template preview screen.
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $workspace, public bool $preview = false)
    {
    }

    public function build()
    {
        return $this->subject('['.$this->workspace.'] Test email delivery')
            ->view('emails.test');
    }
}
