<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->item_id,
            'name'           => $this->name,
            'description'    => $this->description,
            'starting_price' => $this->starting_price,
            'image_url'      => $this->image_url,
            'url_file'       => $this->url_file,
            'status'         => $this->status,
            'auction_org_id' => $this->auction_org_id, // <-- thêm dòng này
            'created_user' => $this->when($this->createdUser, function() {
    return [
        'id'   => $this->createdUser->user_id,
        'name' => $this->createdUser->full_name,
    ];
}, function() {
    // fallback nếu chưa load hoặc chỉ có ID
    return is_int($this->created_user) ? ['id' => $this->created_user, 'name' => null] : null;
}),// <-- thêm dòng này

            'category' => $this->category ? $this->category->name : null,

            'owner' => $this->owner ? [
                'id'    => $this->owner->user_id,
                'name'  => $this->owner->full_name,
                'email' => $this->owner->email,
                'phone' => $this->owner->phone,
                'address' => $this->owner->address,
                'phone' => $this->owner->phone,
            ] : null,
            'created_at' => $this->created_at,

            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id' => $img->image_id,
                    'url' => $img->image_url,
                    'is_primary' => $img->is_primary,
                ]);
            }),

            'sessions' => $this->whenLoaded('sessions', function () {
                return $this->sessions->map(function ($session) {
                    return [
                        'id' => $session->session_id,
                        'method' => $session->method,
                        'auction_org' => $session->auctionOrg?->full_name,
                        'register_start' => $session->register_start,
                        'register_end' => $session->register_end,
                        'checkin_time' => $session->checkin_time,
                        'bid_start' => $session->bid_start,
                        'bid_end' => $session->bid_end,
                        'bid_step' => $session->bid_step,
                        'status' => $session->status,
                        'bids' => $session->bids->map(fn($bid) => [
                            'id' => $bid->bid_id,
                            'amount' => $bid->amount,
                            'user' => $bid->user?->full_name,
                            'time' => $bid->bid_time,
                        ]),
                        'contract' => $session->contract ? [
                            'id' => $session->contract->contract_id,
                            'winner' => $session->contract->winner_id,
                            'final_price' => $session->contract->final_price,
                            'status' => $session->contract->status,
                        ] : null
                    ];
                });
            }),
        ];
    }
}
