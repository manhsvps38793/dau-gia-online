<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông tin người dùng</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
            width: 180px;
        }

        img {
            max-width: 250px;
            height: auto;
            border: 1px solid #ccc;
            padding: 2px;
        }
    </style>
</head>

<body>
    <h1>Thông tin người dùng</h1>
    <table>
        <tr>
            <th>Họ tên</th>
            <td>{{ $user->full_name ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $user->email ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Số điện thoại</th>
            <td>{{ $user->phone ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Địa chỉ</th>
            <td>{{ $user->address ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Ngân hàng</th>
            <td>{{ $user->bank_name ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Số tài khoản</th>
            <td>{{ $user->bank_account ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>Quyền</th>
            <td>{{ $user->role_id ? $user->role->name : 'Không có' }}</td>
        </tr>
        <tr>
            <th>Ngày tạo</th>
            <td>{{ $user->created_at ?? 'Chưa có' }}</td>
        </tr>
        <tr>
            <th>CMND/CCCD - Mặt trước</th>
            
            <td>
                <img src="{{ 'storage/' . $user->id_card_front }}" alt="CMND Front">
            </td>
        </tr>

        <tr>
            <th>CMND/CCCD - Mặt sau</th>
            <td>
                <img src="{{ 'storage/' . $user->id_card_back }}" alt="CMND Back">
            </td>
        </tr>
    </table>
</body>

</html>
