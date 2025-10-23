<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $adminUrl;

    public function __construct($user, $adminUrl)
    {
        $this->user = $user;
        $this->adminUrl = $adminUrl;
    }

    public function build()
    {
        return $this->subject('Tài khoản mới cần xét duyệt')
            ->view('emails.new_user_pending_approval');
    }
}
