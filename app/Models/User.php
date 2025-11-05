<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $fillable = [
        'full_name', 'birth_date', 'gender', 'email', 'phone', 'address',
        'identity_number', 'identity_issue_date', 'identity_issued_by',
        'id_card_front', 'id_card_back', 'bank_name', 'bank_account',
        'bank_branch', 'position', 'organization_name', 'tax_code',
        'business_license_issue_date', 'business_license_issued_by',
        'business_license', 'online_contact_method', 'certificate_number',
        'certificate_issue_date', 'certificate_issued_by',
        'auctioneer_card_front', 'auctioneer_card_back', 'password',
        'role_id', 'verify_token', 'email_verified_at',
        'admin_verified_at', 'admin_verify_status','is_locked', 'locked_at','reset_token',
        'reset_token_expires_at',
    ];

    protected $hidden = ['password', 'verify_token' ,'reset_token'];

    protected $casts = [
         'is_locked' => 'integer',
         'locked_at' => 'datetime',
        'birth_date' => 'date',
        'identity_issue_date' => 'date',
        'business_license_issue_date' => 'date',
        'certificate_issue_date' => 'date',
        'email_verified_at' => 'datetime',
        'admin_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];

    public function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::instance($date)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i:sP');
    }

    // === QUAN HỆ 1-1 ===
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function auctionProfiles()
    {
        return $this->hasMany(AuctionProfile::class, 'user_id', 'user_id');
    }

    // === PERMISSIONS ===
    public function permissions()
    {
        return $this->role?->permissions ?? collect();
    }

    public function hasPermission($permissionName)
    {
        return $this->role?->permissions->contains('name', $permissionName) ?? false;
    }

    // === HELPER ===
    public function isApprovedByAdmin() { return $this->admin_verify_status === 'approved'; }
    public function isPendingApproval() { return $this->admin_verify_status === 'pending'; }
    public function isRejectedByAdmin() { return $this->admin_verify_status === 'rejected'; }
}
