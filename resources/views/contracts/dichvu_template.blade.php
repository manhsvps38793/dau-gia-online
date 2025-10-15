<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hợp đồng dịch vụ đấu giá</title>
    <style>
        /* Font hỗ trợ tiếng Việt */
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .contract-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .contract-header {
            background: linear-gradient(135deg, #1a5276 0%, #2c3e50 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
            position: relative;
        }

        .contract-header:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #e74c3c;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .contract-subtitle {
            font-size: 18px;
            margin: 0;
            opacity: 0.9;
            font-weight: 400;
        }

        .contract-body {
            padding: 30px;
        }

        h2 {
            font-size: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 8px;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }

        .info-item strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-item span {
            color: #34495e;
            font-size: 16px;
        }

        .highlight-box {
            background: #fff8e1;
            border: 1px solid #ffd54f;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .highlight-box h3 {
            margin-top: 0;
            color: #e67e22;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        table thead {
            background: #34495e;
            color: white;
        }

        table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }

        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tbody tr:hover {
            background-color: #f1f8ff;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            height: 1px;
            background: #2c3e50;
            margin: 60px 0 10px;
            position: relative;
        }

        .signature-line:before {
            content: "";
            position: absolute;
            top: -5px;
            left: 0;
            right: 0;
            height: 1px;
            background: #2c3e50;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 5px;
        }

        .signature-title {
            font-size: 14px;
            color: #7f8c8d;
        }

        .contract-footer {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            color: #7f8c8d;
            font-size: 14px;
            border-top: 1px solid #e0e0e0;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.03);
            pointer-events: none;
            z-index: 1000;
            font-weight: bold;
            white-space: nowrap;
        }

        @media print {
            body {
                background: white;
            }
            .contract-container {
                box-shadow: none;
                margin: 0;
            }
            .watermark {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Watermark (chỉ hiển thị khi in) -->
    <div class="watermark">HỢP ĐỒNG ĐẤU GIÁ</div>

    <div class="contract-container">
        <div class="contract-header">
            <h1>HỢP ĐỒNG DỊCH VỤ ĐẤU GIÁ</h1>
            <p class="contract-subtitle">Phiên đấu giá #{{ $session->session_id }}</p>
        </div>

        <div class="contract-body">
            <div class="info-grid">
                <div class="info-item">
                    <strong>Người tạo phiên đấu giá</strong>
                    <span>{{ $auction_org->name ?? 'Chưa xác định' }}</span>
                </div>
                <div class="info-item">
                    <strong>Người sở hữu tài sản</strong>
                    <span>{{ $owner->name ?? 'Chưa xác định' }}</span>
                </div>
                <div class="info-item">
                    <strong>Thời gian đấu giá</strong>
                    <span>{{ $session->start_time }} - {{ $session->end_time }}</span>
                </div>
                <div class="info-item">
                    <strong>Giá cuối cùng</strong>
                    <span>{{ number_format($contract->final_price) }} VND</span>
                </div>
            </div>

            <div class="highlight-box">
                <h3>Thông tin quan trọng</h3>
                <p><strong>Quy định đấu giá:</strong> {{ $session->regulation }}</p>
                <p><strong>Phương thức đấu giá:</strong> {{ $session->method }}</p>
            </div>

            <h2>Chi tiết tài sản đấu giá</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã tài sản</th>
                        <th>Tên tài sản</th>
                        <th>Mô tả</th>
                        <th>Giá khởi điểm</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $session->item->item_id ?? 'N/A' }}</td>
                        <td>{{ $session->item->name ?? 'N/A' }}</td>
                        <td>{{ $session->item->description ?? 'N/A' }}</td>
                        <td>{{ number_format($session->item->start_price ?? 0) }} VND</td>
                    </tr>
                </tbody>
            </table>

            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $auction_org->name ?? 'Người tạo phiên đấu giá' }}</div>
                    <div class="signature-title">(Ký, ghi rõ họ tên và đóng dấu)</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $owner->name ?? 'Người sở hữu tài sản' }}</div>
                    <div class="signature-title">(Ký, ghi rõ họ tên)</div>
                </div>
            </div>
        </div>

        <div class="contract-footer">
            <p>Hợp đồng được tạo tự động vào {{ now()->format('d/m/Y H:i') }} | Trang 1/1</p>
        </div>
    </div>
</body>
</html>