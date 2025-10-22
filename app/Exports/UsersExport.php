<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class UsersExport implements FromCollection, WithHeadings
{
    protected $userIds;
    protected $users;

    public function __construct($userIds = null)
    {
        $this->userIds = $userIds;
        $this->users = $this->getUsers();
    }

    protected function getUsers()
    {
        return User::with('role')->when($this->userIds, function ($q) {
            $q->whereIn('user_id', $this->userIds);
        })->get();
    }

public function collection()
{
    $baseUrl = 'http://localhost:8000/storage/'; // base URL

    return $this->users->map(function ($user) use ($baseUrl) {
        return [
            $user->user_id,
            $user->full_name,
            $user->email,
            $user->phone,
            $user->address,
            $user->id_card_front ? $baseUrl . $user->id_card_front : null,
            $user->id_card_back ? $baseUrl . $user->id_card_back : null,
            $user->bank_name,
            $user->bank_account,
            $user->role_id ? $user->role->name : 'Không có', // role name text
            $user->created_at,
            $user->deleted_at,
        ];
    });
}

    public function headings(): array
    {
        return [
            'user_id',
            'full_name',
            'email',
            'phone',
            'address',
            'id_card_front',
            'id_card_back',
            'bank_name',
            'bank_account',
            'role_id',
            'created_at',
            'deleted_at',
        ];
    }

   

}
