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

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Barryvdh\DomPDF\Facade\Pdf;

class AuthController extends Controller
{
    // POST /api/register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:6|confirmed',
            'role_id' => 'nullable|exists:Roles,role_id', // cho admin chỉ định role
            'address' => 'nullable|string|max:255',
            'id_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_card_back' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi dữ liệu',
                'errors' => $validator->errors()
            ], 422);
        }

        $verifyToken = Str::random(64);

        $frontPath = $request->hasFile('id_card_front') ? $request->file('id_card_front')->store('idcards', 'public') : null;
        $backPath = $request->hasFile('id_card_back') ? $request->file('id_card_back')->store('idcards', 'public') : null;

        $defaultRole = Role::where('name', 'User')->first();
        $roleId = $request->role_id ?? ($defaultRole ? $defaultRole->role_id : null);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $roleId,
            'address' => $request->address,
            'id_card_front' => $frontPath,
            'id_card_back' => $backPath,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'verify_token' => $verifyToken,
            'created_at' => now()
        ]);

        // Tạo URL verify email
        $verifyUrl = url('/api/verify-email/' . $verifyToken);
        Mail::to($user->email)->queue(new VerifyEmailMail($user->full_name, $verifyUrl));

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác thực.',
            'user' => $user->load('role') // trả về luôn role
        ], 201);
    }

    // GET /api/verify-email/{token}
    public function verifyEmail($token)
    {
        $user = User::where('verify_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Liên kết xác thực không hợp lệ!'
            ], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'verify_token' => null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Email đã được xác thực thành công!'
        ]);
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

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản chưa xác thực email.'
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
            ]
        ]);
    }

    // PUT /api/user/update
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'password' => 'sometimes|min:6|confirmed',
            'address' => 'sometimes|string|max:255',
            'id_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'id_card_back' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'bank_name' => 'sometimes|string|max:255',
            'bank_account' => 'sometimes|string|max:50',
        ]);

        if ($request->hasFile('id_card_front')) {
            $path = $request->file('id_card_front')->store('idcards', 'public');
            $data['id_card_front'] = $path;
        }

        if ($request->hasFile('id_card_back')) {
            $path = $request->file('id_card_back')->store('idcards', 'public');
            $data['id_card_back'] = $path;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thông tin thành công',
            'user' => $user->fresh()->load('role')
        ]);
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

        return Excel::download(new \App\Exports\UsersExport($userIds), $fileName);
    }

}
