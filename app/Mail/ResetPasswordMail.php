<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $fullName;
    public $resetUrl;

    public function __construct($fullName, $token)
    {
        $this->fullName = $fullName;
        $this->resetUrl = "http://localhost:3000/reset-password/{$token}";
    }

    public function build()
    {
        return $this->subject('🔑 Xác nhận yêu cầu đổi mật khẩu')
                    ->view('emails.reset_password_confirm') // 🔥 Dùng view HTML
                    ->with([
                        'fullName' => $this->fullName,
                        'resetUrl' => $this->resetUrl,
                    ]);
    }
}
