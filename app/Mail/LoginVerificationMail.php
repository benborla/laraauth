<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoginVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $content;
    protected $code;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($content, $code)
    {
        $this->from('no-reply@laraauth.local');
        $this->subject('New Device Login');

        $this->content = $content;
        $this->code    = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('auth.login_verification_mail',[
            'content' => $this->content,
            'code'    => $this->code
        ]);
    }
}
