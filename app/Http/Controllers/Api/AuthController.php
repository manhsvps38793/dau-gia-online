<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
     public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|min:6|confirmed',
            'role'      => 'in:User,Administrator,Customer,ChuyenVienTTC,DauGiaVien,DonViThuc,ToChucDauGia',
            'address'        => 'nullable|string|max:255',
            'id_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_card_back'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bank_name'      => 'nullable|string|max:255',
            'bank_account'   => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi dữ liệu',
                'errors'  => $validator->errors()
            ], 422);
        }

        $verifyToken = Str::random(64);
        $frontPath = $request->hasFile('id_card_front') ? $request->file('id_card_front')->store('id_cards', 'public') : null;
        $backPath  = $request->hasFile('id_card_back') ? $request->file('id_card_back')->store('id_cards', 'public') : null;

        $user = User::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => $request->password,
            'role'      => $request->role ?? 'User',
            'address'   => $request->address,
            'id_card_front' => $frontPath,
            'id_card_back'  => $backPath,
            'bank_name'     => $request->bank_name,
            'bank_account'  => $request->bank_account,
            'verify_token'  => $verifyToken,
            'created_at'    => now()
        ]);

        $verifyUrl = url('/verify-email/' . $verifyToken);

        // Gửi mail
        Mail::to($user->email)->queue(new VerifyEmailMail($user->full_name, $verifyUrl));
        // Mail::to($user->email)->send(new VerifyEmailMail($user->full_name, $verifyUrl));


        return response()->json([
            'status'  => true,
            'message' => 'Đăng ký thành công, vui lòng kiểm tra email để xác thực tài khoản.',
            'user'    => $user
        ], 201);
    }

    public function verifyEmail($token)
    {
        $user = User::where('verify_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Liên kết xác thực không hợp lệ!'], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'verify_token' => null,
        ]);

        return response()->json(['message' => 'Email đã được xác thực thành công!']);
    }

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

        // Kiểm tra email đã verify chưa
        if (is_null($user->email_verified_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản chưa được xác thực email. Vui lòng kiểm tra email để xác thực.'
            ], 403); // 403 Forbidden
        }

        // Tạo token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user,
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
public function index()
{
    $users = User::orderByDesc('user_id')->get(); // Lấy tất cả user, sắp xếp theo user_id giảm dần

    return response()->json([
        'status' => true,
        'users'  => $users
    ]);
}

    // GET /api/user
     public function user(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user()
        ]);
    }

    // ✅ Cập nhật thông tin user
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->user_id . ',user_id',
            'password' => 'sometimes|min:6|confirmed',
            'address'       => 'sometimes|string|max:255',
            'id_card_front' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'id_card_back'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bank_name'     => 'sometimes|string|max:255',
            'bank_account'  => 'sometimes|string|max:50',
        ]);

        if ($request->hasFile('id_card_front')) {
        $data['id_card_front'] = $request->file('id_card_front')->store('id_cards', 'public');
        }
        if ($request->hasFile('id_card_back')) {
            $data['id_card_back'] = $request->file('id_card_back')->store('id_cards', 'public');
        }
        if(isset($data['password'])){
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật thông tin thành công',
            'user'    => $user
        ]);
    }
}
