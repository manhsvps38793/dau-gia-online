<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->item_id,   // 👈 sửa lại
            'name' => $this->name,
            'description' => $this->description,
            'starting_price' => $this->starting_price,
            'image_url' => $this->image_url,
            'status' => $this->status,
            'category' => $this->category ? $this->category->name : null,
            'owner' => $this->owner ? [
                'id' => $this->owner->user_id,   // 👈 sửa lại
                'name' => $this->owner->full_name,
                'email' => $this->owner->email,
            ] : null,
            'sessions' => $this->sessions->map(function ($session) {
                return [
                    'id' => $session->session_id,   // 👈 sửa lại
                    'method' => $session->method,
                    'auction_org' => $session->auctionOrg ? $session->auctionOrg->full_name : null,
                    'register_start' => $session->register_start,
                    'register_end' => $session->register_end,
                    'checkin_time' => $session->checkin_time,
                    'bid_start' => $session->bid_start,
                    'bid_end' => $session->bid_end,
                    'bid_step' => $session->bid_step,
                    'status' => $session->status,
                    'bids' => $session->bids->map(function ($bid) {
                        return [
                            'id' => $bid->bid_id,   // 👈 sửa lại
                            'amount' => $bid->amount,
                            'user' => $bid->user ? $bid->user->full_name : null,
                            'time' => $bid->bid_time,
                        ];
                    }),
                    'contract' => $session->contract ? [
                        'id' => $session->contract->contract_id,   // 👈 sửa lại
                        'winner' => $session->contract->winner_id,
                        'final_price' => $session->contract->final_price,
                        'status' => $session->contract->status,
                    ] : null
                ];
            }),
        ];
    }
}
