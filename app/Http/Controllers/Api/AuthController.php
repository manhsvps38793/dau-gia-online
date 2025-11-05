<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerifyEmailMail;
use App\Mail\NewUserPendingApprovalMail;
use App\Mail\UserApprovedMail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Illuminate\Support\Carbon;



use Barryvdh\DomPDF\Facade\Pdf;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'account_type' => 'required|in:user,business,auction',
            'full_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|regex:/^(\+?\d{9,15})$/',
            'address' => 'nullable|string|max:255',
            'password' => 'required|min:6|confirmed',
            'identity_number' => 'required|string|max:20|unique:users,identity_number',
            'identity_issue_date' => 'required|date',
            'identity_issued_by' => 'required|string|max:255',
            'id_card_front' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'id_card_back' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'bank_name' => 'required|string|max:100',
            'bank_account' => 'required|string|max:50',
            'bank_branch' => 'required|string|max:255',
            // Business-specific fields
            'position' => 'required_if:account_type,business|string|max:255',
            'organization_name' => 'required_if:account_type,business|string|max:255',
            'tax_code' => 'required_if:account_type,business|string|max:20|unique:users,tax_code',
            'business_license_issue_date' => 'required_if:account_type,business|date',
            'business_license_issued_by' => 'required_if:account_type,business|string|max:255',
            'business_license' => 'required_if:account_type,business|file|mimes:pdf,doc,docx|max:5120',
            // Auctioneer-specific fields
            'online_contact_method' => 'required_if:account_type,auction|string|max:255',
            'certificate_number' => 'required_if:account_type,auction|string|max:50|unique:users,certificate_number',
            'certificate_issue_date' => 'required_if:account_type,auction|date',
            'certificate_issued_by' => 'required_if:account_type,auction|string|max:255',
            'auctioneer_card_front' => 'required_if:account_type,auction|image|mimes:jpg,jpeg,png|max:2048',
            'auctioneer_card_back' => 'required_if:account_type,auction|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'phone.regex' => 'Số điện thoại sai định dạng.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ánh xạ account_type với role_id
        $roleName = match ($request->account_type) {
            'user' => 'User',
            'business' => 'Bussiness',
            'auction' => 'Auction',
            default => null,
        };

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Loại tài khoản không hợp lệ'
            ], 400);
        }

        // Xử lý file uploads
        $idCardFrontPath = $request->file('id_card_front')->store('idcards', 'public');
        $idCardBackPath = $request->file('id_card_back')->store('idcards', 'public');
        $businessLicensePath = $request->account_type === 'business' && $request->hasFile('business_license')
            ? $request->file('business_license')->store('business_licenses', 'public')
            : null;
        $auctioneerCardFrontPath = $request->account_type === 'auction' && $request->hasFile('auctioneer_card_front')
            ? $request->file('auctioneer_card_front')->store('auctioneer_cards', 'public')
            : null;
        $auctioneerCardBackPath = $request->account_type === 'auction' && $request->hasFile('auctioneer_card_back')
            ? $request->file('auctioneer_card_back')->store('auctioneer_cards', 'public')
            : null;

        // Tạo verify token
        $verifyToken = Str::random(64);

        // Tạo user
        $user = User::create([
            'full_name' => $request->full_name,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'identity_number' => $request->identity_number,
            'identity_issue_date' => $request->identity_issue_date,
            'identity_issued_by' => $request->identity_issued_by,
            'id_card_front' => $idCardFrontPath,
            'id_card_back' => $idCardBackPath,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'bank_branch' => $request->bank_branch,
            'position' => $request->account_type === 'business' ? $request->position : null,
            'organization_name' => $request->account_type === 'business' ? $request->organization_name : null,
            'tax_code' => $request->account_type === 'business' ? $request->tax_code : null,
            'business_license_issue_date' => $request->account_type === 'business' ? $request->business_license_issue_date : null,
            'business_license_issued_by' => $request->account_type === 'business' ? $request->business_license_issued_by : null,
            'business_license' => $businessLicensePath,
            'online_contact_method' => $request->account_type === 'auction' ? $request->online_contact_method : null,
            'certificate_number' => $request->account_type === 'auction' ? $request->certificate_number : null,
            'certificate_issue_date' => $request->account_type === 'auction' ? $request->certificate_issue_date : null,
            'certificate_issued_by' => $request->account_type === 'auction' ? $request->certificate_issued_by : null,
            'auctioneer_card_front' => $auctioneerCardFrontPath,
            'auctioneer_card_back' => $auctioneerCardBackPath,
            'password' => Hash::make($request->password),
            'role_id' => $role->role_id,
            'verify_token' => $verifyToken,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Gửi email xác thực
        try {
            $verifyUrl = url('/api/verify-email/' . $verifyToken);
            Mail::to($user->email)->queue(new VerifyEmailMail($user->full_name, $verifyUrl));
        } catch (\Exception $e) {
            // Xóa user nếu gửi email thất bại
            $user->delete();
            return response()->json([
                'status' => false,
                'message' => 'Không thể gửi email xác thực. Vui lòng thử lại sau.'
            ], 500);
        }

        // Gửi mail cho admin thông báo có tài khoản mới chờ xét duyệt
        try {
            $adminEmail = config('mail.admin_address', 'admin@example.com'); // cấu hình mail admin
            $adminUrl = url('/admin/users/' . $user->user_id); // link admin xem chi tiết user
            Mail::to($adminEmail)->queue(new NewUserPendingApprovalMail($user, $adminUrl));
        } catch (\Exception $e) {
            return response()->json([
                'status' => true,
                'message' => 'Đăng ký thành công nhưng không thể gửi thông báo cho admin: ' . $e->getMessage(),
                'user' => $user->load('role')
            ], 201);
        }

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực.',
            'user' => $user->load('role')
        ], 201);
    }

    // GET /api/verify-email/{token}
   public function verifyEmail($token)
    {
        $user = User::where('verify_token', $token)->first();

        if (!$user) {
            return redirect()->to(env('FRONTEND_URL') . '/login?message=' . urlencode('Liên kết xác thực không hợp lệ hoặc đã hết hạn.'));
        }

        $user->update([
            'email_verified_at' => now(),
            'verify_token' => null
        ]);

        return redirect()->to(env('FRONTEND_URL') . '/login?message=' . urlencode('Xác minh thành công, chờ admin xét duyệt tài khoản.'));
    }

    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        // Kiểm tra tài khoản bị khóa
        if ($user->is_locked == 1) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            ], 403);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản chưa xác thực email.'
            ], 403);
        }

        if (is_null($user->admin_verified_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chờ admin xét duyệt.'
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user->load('role'),
            'token' => $token
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }

    // GET /api/user
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'user' => [
                ...$user->toArray(),
                'role' => $user->role ? $user->role->name : null,
                'id_card_front_url' => $user->id_card_front ? asset('storage/' . $user->id_card_front) : null,
                'id_card_back_url' => $user->id_card_back ? asset('storage/' . $user->id_card_back) : null,
                'business_license_url' => $user->business_license ? asset('storage/' . $user->business_license) : null,
                'auctioneer_card_front_url' => $user->auctioneer_card_front ? asset('storage/' . $user->auctioneer_card_front) : null,
                'auctioneer_card_back_url' => $user->auctioneer_card_back ? asset('storage/' . $user->auctioneer_card_back) : null,
            ]
        ]);
    }

    // PUT /api/user/update


    public function update(Request $request, $id)
    {
        try {
            // === 1. Tìm user ===
            $user = User::where('user_id', $id)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Người dùng không tồn tại'
                ], 404);
            }

            // === 2. Validate dữ liệu ===
            $rules = [
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
                'phone' => 'sometimes|string|max:20|nullable',
                'password' => 'sometimes|min:6|confirmed',
                'identity_number' => 'sometimes|string|max:20|nullable|unique:users,identity_number,' . $user->user_id . ',user_id',
                'birth_date' => 'sometimes|date|nullable',
                'gender' => 'sometimes|string|in:male,female,other|nullable',
                'address' => 'sometimes|string|max:500|nullable',
                'identity_issue_date' => 'sometimes|date|nullable',
                'identity_issued_by' => 'sometimes|string|max:255|nullable',
                'bank_name' => 'sometimes|string|max:255|nullable',
                'bank_account' => 'sometimes|string|max:50|nullable',
                'bank_branch' => 'sometimes|string|max:255|nullable',
                'position' => 'sometimes|string|max:255|nullable',
                'organization_name' => 'sometimes|string|max:255|nullable',
                'tax_code' => 'sometimes|string|max:50|nullable',
                'business_license_issue_date' => 'sometimes|date|nullable',
                'business_license_issued_by' => 'sometimes|string|max:255|nullable',
                'online_contact_method' => 'sometimes|string|max:255|nullable',
                'certificate_number' => 'sometimes|string|max:50|nullable',
                'certificate_issue_date' => 'sometimes|date|nullable',
                'certificate_issued_by' => 'sometimes|string|max:255|nullable',
                'role_id' => 'sometimes|exists:roles,role_id',
                // FILES
                'id_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'id_card_back' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'business_license' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
                'auctioneer_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'auctioneer_card_back' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // === 3. Lấy dữ liệu ===
            $fields = [
                'full_name','email','phone','birth_date','gender','address',
    'identity_number','identity_issue_date','identity_issued_by',
                'bank_name','bank_account','bank_branch',
                'position','organization_name','tax_code',
                'business_license_issue_date','business_license_issued_by',
                'online_contact_method','certificate_number',
                'certificate_issue_date','certificate_issued_by',
                'role_id'
            ];
            $data = $request->only($fields);

            // === 4. Xử lý nullable fields ===
            $nullableFields = $fields;
            foreach ($nullableFields as $field) {
                if ($request->has($field) && $request->input($field) === '') {
                    $data[$field] = null;
                }
            }

            // === 5. Update password nếu có ===
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // === 6. Xử lý file upload ===
            $fileFields = [
                'id_card_front' => 'idcards',
                'id_card_back' => 'idcards',
                'business_license' => 'business_licenses',
                'auctioneer_card_front' => 'auctioneer_cards',
                'auctioneer_card_back' => 'auctioneer_cards',
            ];

            foreach ($fileFields as $field => $folder) {
                if ($request->hasFile($field)) {
                    // Xóa file cũ nếu có
                    if ($user->$field) {
                        Storage::disk('public')->delete($user->$field);
                    }
                    // Lưu file mới
                    $data[$field] = $request->file($field)->store($folder, 'public');
                }
            }

            // === 7. Kiểm tra có dữ liệu để update không ===
            if (empty($data)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không có dữ liệu để cập nhật'
                ], 400);
            }

            // === 8. Update dữ liệu ===
            $updated = $user->update($data);
            if (!$updated) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không thể cập nhật dữ liệu'
                ], 500);
            }

            // === 9. Trả kết quả ===
            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thành công',
                'user' => $user->fresh(['role'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }


    // GET /api/users (dành cho admin)
    public function index()
    {
        $users = User::with('role.permissions')->orderByDesc('user_id')->get();

        return response()->json([
            'status' => true,
            'users' => $users
        ]);
    }

    public function exportUserPDF($id)
    {
        $user = User::with('role')->find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        $pdf = Pdf::loadView('pdf.user_detail', compact('user'));

        $fileName = 'user_' . $user->user_id . '.pdf';
        return $pdf->download($fileName);
    }

    public function exportUsersExcel(Request $request)
    {
        $userIds = $request->input('user_ids'); // Mảng user_id cần export (hoặc null = tất cả)
        $fileName = 'users_export_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new UsersExport($userIds), $fileName);
    }

    public function approveUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }
        if ($user->email_verified_at == null) {
            return response()->json(['status' => false, 'message' => 'Tài khoản chưa được xác minh'], 404);
        }

        $user->update([
            'admin_verified_at' => now(),
            'admin_verify_status' => 'approved'
        ]);



        // Gửi mail thông báo user đã được duyệt
        // try {
        //     Mail::to($user->email)->queue(new UserApprovedMail($user));
        // } catch (\Exception $e) {}

        return response()->json(['status' => true, 'message' => 'Tài khoản đã được duyệt thành công.']);
    }

    public function rejectUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        $user->update([
            'admin_verified_at' => null,
            'admin_verify_status' => 'rejected'
        ]);

        return response()->json(['status' => true, 'message' => 'Tài khoản đã bị từ chối.']);
    }

 // POST /api/user/lock/{id} - Khóa tài khoản
    public function lockUser($id)
    {
        try {
            // if (!auth()->user()->hasPermission('lock_users')) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Bạn không có quyền thực hiện hành động này'
            //     ], 403);
            // }
            $user = User::where('user_id', $id)->first(); // Sửa thành where('user_id')
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $user->update([
                'is_locked' => 1,
                'locked_at' => now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Đã khóa tài khoản thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('Lock user error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

// POST /api/user/unlock/{id} - Mở khóa tài khoản
    public function unlockUser($id)
    {
        try {
            $user = User::where('user_id', $id)->first(); // Đã sửa
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy người dùng'
                ], 404);
            }

            $user->update([
                'is_locked' => 0, // Sửa thành 0 thay vì null
                'locked_at' => null
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Đã mở khóa tài khoản thành công'
            ]);

        } catch (\Exception $e) {
            \Log::error('Unlock user error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
            }

            $token = Str::random(60);

            $user->update([
                'reset_token' => $token,
                'reset_token_expires_at' => now()->addMinutes(30),
            ]);

            Mail::to($user->email)->send(new ResetPasswordMail($user->full_name, $token));

            return response()->json([
                'message' => 'Vui lòng kiểm tra email để xác nhận yêu cầu đổi mật khẩu.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Forgot password error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    public function verifyResetToken($token)
    {
        $user = User::where('reset_token', $token)
                    ->where('reset_token_expires_at', '>', Carbon::now())
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!'
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Token hợp lệ! Vui lòng nhập mật khẩu mới.'
        ]);
    }

    // ✅ 3. Đổi mật khẩu mới
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::where('reset_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ!'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.'
        ]);
    }
}
