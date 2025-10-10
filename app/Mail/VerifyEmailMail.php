<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $verifyUrl;
    public $fullName;

    public function __construct($fullName, $verifyUrl)
    {
        $this->fullName = $fullName;
        $this->verifyUrl = $verifyUrl;
    }

    public function build()
    {
        return $this->subject('Xác thực tài khoản của bạn')
                    ->markdown('emails.verify_email')
                    ->with([
                        'fullName' => $this->fullName,
                        'verifyUrl' => $this->verifyUrl,
                    ]);
    }
}
