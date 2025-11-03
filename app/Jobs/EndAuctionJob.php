<?php

namespace App\Jobs;

use App\Models\AuctionSession;
use App\Events\AuctionSessionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Bid;
use App\Models\Contract;
use App\Models\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
class EndAuctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }
public function handle()
{
    \Log::info("EndAuctionJob: Start session_id={$this->sessionId}");

    $session = AuctionSession::find($this->sessionId);
    if (!$session) {
        \Log::error("EndAuctionJob: Session not found session_id={$this->sessionId}");
        return;
    }

    \Log::info("EndAuctionJob: Session found, status={$session->status}, bid_end={$session->bid_end}");

    if ($session->status !== 'KetThuc' && now()->gte($session->bid_end)) {

        $highestBid = Bid::where('session_id', $session->session_id)
                         ->orderBy('amount', 'desc')
                         ->first();

        if ($highestBid) {
            \Log::info("EndAuctionJob: Highest bid found user_id={$highestBid->user_id}, amount={$highestBid->amount}");
        } else {
            \Log::info("EndAuctionJob: No bids found for session_id={$session->session_id}");
        }

        $session->status = 'KetThuc';
        $session->save();
        \Log::info("EndAuctionJob: Session status updated to KetThuc");

        broadcast(new AuctionSessionUpdated($session))->toOthers();
        \Log::info("EndAuctionJob: Broadcasted AuctionSessionUpdated");

        if ($highestBid) {
            // 1️⃣ Cập nhật hợp đồng gốc
            $contract = Contract::where('session_id', $session->session_id)->first();
            if (!$contract) {
                $contract = Contract::create([
                    'session_id' => $session->session_id,
                    'winner_id' => $highestBid->user_id,
                    'final_price' => $highestBid->amount,
                    'status' => 'ChoThanhToan',
                    'signed_date' => now(),
                ]);
                \Log::info("EndAuctionJob: Contract created contract_id={$contract->contract_id}");
            } else {
                $contract->update([
                    'winner_id' => $highestBid->user_id,
                    'final_price' => $highestBid->amount,
                    'status' => 'ChoThanhToan',
                    'signed_date' => now(),
                ]);
                \Log::info("EndAuctionJob: Contract updated contract_id={$contract->contract_id}");
            }

            // 2️⃣ Sinh PDF
            $fileUrl = null;
            try {
                $pdf = Pdf::loadView('contracts.muaban_template', [
                    'contract' => $contract,
                    'session' => $session,
                    'winner' => $highestBid->user,
                    'owner' => $session->item->user ?? null
                ])->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

                $fileName = 'contracts/contract_muaban_session_' . $session->session_id . '.pdf';
                \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $pdf->output());
                $fileUrl = \Illuminate\Support\Facades\Storage::url($fileName);

                \Log::info("EndAuctionJob: PDF created file={$fileName}");
            } catch (\Exception $e) {
                \Log::error("EndAuctionJob: PDF generation failed - ".$e->getMessage()."\n".$e->getTraceAsString());
            }

            // 3️⃣ Tạo hợp đồng online & notification
            if ($fileUrl) {
                try {
                    \App\Models\EContracts::create([
                        'contract_type' => 'MuaBanTaiSan',
                        'contract_id' => $contract->contract_id,
                        'session_id' => $session->session_id,
                        'file_url' => $fileUrl,
                        'signed_by' => $highestBid->user_id,
                    ]);
                    \Log::info("EndAuctionJob: EContract created for contract_id={$contract->contract_id}");
                } catch (\Exception $e) {
                    \Log::error("EndAuctionJob: EContract creation failed - ".$e->getMessage());
                }

                try {
                    Notification::create([
                        'user_id' => $highestBid->user_id,
                        'type' => 'ThangDauGia',
                        'message' => "Chúc mừng! thắng phiên đấu giá #{$session->session_id} với giá {$highestBid->amount} VND. Hãy ký hợp đồng online:",
                        'sent_at' => now()
                    ]);
                    \Log::info("EndAuctionJob: Notification sent to user_id={$highestBid->user_id}");
                } catch (\Exception $e) {
                    \Log::error("EndAuctionJob: Notification creation failed - ".$e->getMessage());
                }
            } else {
                \Log::warning("EndAuctionJob: Skipping EContract and Notification because PDF was not created.");
            }
        } else {
            \Log::info("EndAuctionJob: No highest bid, skipping contract creation.");
        }
    } else {
        \Log::info("EndAuctionJob: Session already ended or bid_end not reached.");
    }

    \Log::info("EndAuctionJob: Finished session_id={$this->sessionId}");
}


}
