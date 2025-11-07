<?php
namespace App\Mail;

use App\Models\AuctionSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WinnerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $user;

    public function __construct(AuctionSession $session, User $user)
    {
        $this->session = $session;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('🎉 Chúc mừng bạn đã thắng đấu giá!')
            ->markdown('emails.winner_notification');
    }
}
