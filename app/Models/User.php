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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'birth_date',
        'gender',
        'email',
        'phone',
        'address',
        'identity_number',
        'identity_issue_date',
        'identity_issued_by',
        'id_card_front',
        'id_card_back',
        'bank_name',
        'bank_account',
        'bank_branch',
        'position',
        'organization_name',
        'tax_code',
        'business_license_issue_date',
        'business_license_issued_by',
        'business_license',
        'online_contact_method',
        'certificate_number',
        'certificate_issue_date',
        'certificate_issued_by',
        'auctioneer_card_front',
        'auctioneer_card_back',
        'password',
        'role_id',
        'verify_token',
        'email_verified_at',

        // 👇 Thêm 2 dòng mới (cột mới thêm trong migration)
        'admin_verified_at',
        'admin_verify_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'verify_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'birth_date' => 'date',
        'identity_issue_date' => 'date',
        'business_license_issue_date' => 'date',
        'certificate_issue_date' => 'date',
        'email_verified_at' => 'datetime',
        'admin_verified_at' => 'datetime', // 👈 thêm dòng này
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Định dạng ngày theo múi giờ Việt Nam khi serialize
     */
    public function serializeDate(\DateTimeInterface $date)
    {
        $carbonDate = Carbon::instance($date)->setTimezone('Asia/Ho_Chi_Minh');
        return $carbonDate->format('Y-m-d\TH:i:sP');
    }

    /**
     * Mối quan hệ: user thuộc role nào
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /**
     * Mối quan hệ: user có nhiều hồ sơ đấu giá
     */
    public function auctionProfiles()
    {
        return $this->hasMany(AuctionProfile::class, 'user_id', 'user_id');
    }

    /**
     * Lấy tất cả quyền (permission) của user
     */
    public function permissions()
    {
        $rolePermissions = $this->role ? $this->role->permissions : collect();
        $userPermissions = $this->hasMany(UserPermission::class, 'user_id', 'user_id')
            ->with('permission')
            ->get()
            ->pluck('permission');

        return $rolePermissions->merge($userPermissions)->unique('permission_id');
    }

    /**
     * Kiểm tra quyền cụ thể
     */
    public function hasPermission($permissionName)
    {
        if (!$this->role) return false;

        return $this->role->permissions->contains('name', $permissionName);
    }

    /**
     * 🔹 Helper: Kiểm tra trạng thái xét duyệt của admin
     */
    public function isApprovedByAdmin()
    {
        return $this->admin_verify_status === 'approved';
    }

    public function isPendingApproval()
    {
        return $this->admin_verify_status === 'pending';
    }

    public function isRejectedByAdmin()
    {
        return $this->admin_verify_status === 'rejected';
    }
}
