<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hợp đồng mua bán tài sản bán đấu giá</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
              font-family: 'DejaVu Sans', sans-serif !important;
        }
        
        body {
            background-color: #f8f6f2;
            padding: 30px 20px;
            color: #333;
            line-height: 1.6;
            font-family: "DejaVu Sans", sans-serif !important;
        }
        
        .contract-container {
            max-width: 820px;
            margin: 0 auto;
            background-color: white;
            padding: 50px 45px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid #d4af37;
        }
        
        .header h1 {
            font-size: 18px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
            color: #1a3c6e;
        }
        
        .header h2 {
            font-size: 20px;
            text-transform: uppercase;
            margin-bottom: 25px;
            color: #b30000;
            font-weight: bold;
        }
        
        .header p {
            font-size: 16px;
            margin-top: 15px;
        }
        
        .contract-number {
            text-align: center;
            margin-bottom: 35px;
            padding: 15px 0;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .contract-number p:first-child {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #1a3c6e;
        }
        
        .contract-number p:last-child {
            font-size: 16px;
        }
        
        .contract-info {
            margin-bottom: 30px;
        }
        
        .contract-info p {
            margin-bottom: 18px;
            display: flex;
            align-items: flex-end;
        }
        
        .contract-info .label {
            min-width: 380px;
            display: inline-block;
        }
        
        .contract-info .field {
            flex: 1;
            border-bottom: 1px dotted #666;
            margin-left: 15px;
            min-height: 22px;
            padding-bottom: 2px;
        }
        
        .contract-content {
            margin: 40px 0 35px;
            padding: 25px;
            background-color: #f9f9f9;
            border-left: 3px solid #1a3c6e;
        }
        
        .contract-content p {
            margin-bottom: 0;
            text-align: justify;
            line-height: 1.7;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px dashed #ccc;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
            padding: 20px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background-color: #fdfdfd;
        }
        
        .signature-box p:first-child {
            font-weight: bold;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .signature-box p:last-child {
            margin-top: 40px;
            font-style: italic;
            color: #666;
        }
        
        .notarization {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #d4af37;
        }
        
        .notarization p {
            margin-bottom: 18px;
            text-align: justify;
        }
        
        .notarization p:first-child {
            font-weight: bold;
            margin-bottom: 20px;
            color: #1a3c6e;
        }
        
        .notary-signature {
            text-align: right;
            margin-top: 50px;
            padding: 20px 0;
        }
        
        .notary-signature p:first-child {
            font-weight: bold;
            margin-bottom: 25px;
        }
        
        .notary-signature p:last-child {
            font-style: italic;
            color: #666;
        }
        
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            
            .contract-container {
                box-shadow: none;
                padding: 30px 25px;
                border: none;
            }
            
            .header {
                border-bottom: 2px solid #000;
            }
            
            .contract-number {
                background-color: transparent;
            }
            
            .contract-content {
                background-color: transparent;
                border-left: 3px solid #000;
            }
            
            .signature-box {
                border: 1px solid #000;
                background-color: transparent;
            }
            
            .notarization {
                border-top: 2px solid #000;
            }
        }
        
        /* Hiệu ứng tinh tế */
        .contract-container {
            position: relative;
        }
        
        .contract-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #1a3c6e, #d4af37, #b30000);
        }
        
        .watermark {
            position: absolute;
            opacity: 0.03;
            font-size: 120px;
            transform: rotate(-45deg);
            top: 40%;
            left: 10%;
            color: #1a3c6e;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="contract-container">
        <div class="watermark">HỢP ĐỒNG</div>
        
        <div class="header">
            <h1>CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM</h1>
            <h2>Độc lập – Tự do – Hạnh phúc</h2>
            <p>Tỉnh, thành phố TP. Hồ Chí Minh, ngày 15 tháng 10 năm 2025</p>
        </div>
        
        <div class="contract-number">
            <p>HỢP ĐỒNG MUA BÁN TÀI SẢN BÁN ĐẤU GIÁ</p>
            <p>Số: HD-MB-{{ $session->session_id ?? '001' }}/2025</p>
        </div>
        
        <div class="contract-info">
            <p><span class="label">Tên người bán đấu giá</span><span class="field">{{ $auction_org->name ?? 'Công ty Đấu giá ABC' }}</span></p>
            <p><span class="label">Địa chỉ</span><span class="field">{{ $auction_org->address ?? '123 Đường ABC, Quận 1, TP. HCM' }}</span></p>
            <p><span class="label">Họ, tên người điều hành cuộc bán đấu giá</span><span class="field">Nguyễn Văn A</span></p>
            <p><span class="label">Địa chỉ</span><span class="field">123 Đường ABC, Quận 1, TP. HCM</span></p>
            <p><span class="label">Họ, tên đấu giá viên</span><span class="field">Trần Thị B</span></p>
            <p><span class="label">Họ, tên người có tài sản bán đấu giá</span><span class="field">{{ $owner->name ?? 'Ông Lê Văn C' }}</span></p>
            <p><span class="label">Địa chỉ</span><span class="field">{{ $owner->address ?? '456 Đường XYZ, Quận 3, TP. HCM' }}</span></p>
            <p><span class="label">Họ, tên người mua được tài sản</span><span class="field">Bà Phạm Thị D</span></p>
            <p><span class="label">Địa chỉ</span><span class="field">789 Đường DEF, Quận 7, TP. HCM</span></p>
            <p><span class="label">Thời gian bán đấu giá</span><span class="field">{{ $session->start_time ?? '2025-10-01 09:00' }} - {{ $session->end_time ?? '2025-10-01 17:00' }}</span></p>
            <p><span class="label">Địa điểm bán đấu giá</span><span class="field">Trực tuyến qua nền tảng đấu giá điện tử</span></p>
            <p><span class="label">Tài sản bán đấu giá (có bản liệt kê, mô tả chi tiết kèm theo, nếu có)</span><span class="field">Mã tài sản: {{ $session->item->item_id ?? 'ID001' }}; Tên: {{ $session->item->name ?? 'Xe hơi Toyota Camry 2018' }}; Mô tả: {{ $session->item->description ?? 'Xe hơi cũ, tình trạng tốt, đăng ký lần đầu năm 2018' }} (Chi tiết kèm theo Phụ lục 1)</span></p>
            <p><span class="label">Giá khởi điểm của tài sản</span><span class="field">{{ number_format($session->item->start_price ?? 500000000) }} VND</span></p>
            <p><span class="label">Giá bán tài sản</span><span class="field">{{ number_format($contract->final_price ?? 600000000) }} VND</span></p>
            <p><span class="label">Thời hạn thanh toán tiền mua tài sản</span><span class="field">Trong vòng 07 ngày kể từ ngày ký hợp đồng</span></p>
            <p><span class="label">Phương thức thanh toán tiền mua tài sản</span><span class="field">Chuyển khoản ngân hàng hoặc tiền mặt</span></p>
            <p><span class="label">Địa điểm thanh toán tiền mua tài sản</span><span class="field">Tài khoản ngân hàng của {{ $auction_org->name ?? 'Công ty Đấu giá ABC' }} hoặc văn phòng công ty</span></p>
            <p><span class="label">Thời hạn giao tài sản</span><span class="field">Sau khi hoàn tất thanh toán đầy đủ</span></p>
            <p><span class="label">Địa điểm giao tài sản</span><span class="field">Tại địa chỉ của người mua hoặc địa điểm thỏa thuận</span></p>
            <p><span class="label">Trách nhiệm do vi phạm nghĩa vụ của các bên:</span><span class="field">Các bên chịu phạt 8% giá trị hợp đồng nếu vi phạm; chi tiết theo Điều {{ $session->regulation ?? '5' }} Quy định đấu giá</span></p>
        </div>
        
        <div class="contract-content">
            <p>Hợp đồng này được lập thành 4 bản có giá trị như nhau. Người bán đấu giá, người mua được tài sản bán đấu giá, người có tài sản bán đấu giá và cơ quan Nhà nước có thẩm quyền đăng ký quyền sở hữu tài sản, mỗi nơi giữ một bản.</p>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <p>ĐẤU GIÁ VIÊN<br>Trần Thị B</p>
                <p>(Ký, ghi rõ họ, tên và đóng dấu)</p>
            </div>
            
            <div class="signature-box">
                <p>NGƯỜI MUA ĐƯỢC TÀI SẢN<br>Bà Phạm Thị D</p>
                <p>(Ký, ghi rõ họ, tên)</p>
            </div>
        </div>
        
        <div class="notarization">
            <p>CHỨNG NHẬN CỦA PHÒNG CÔNG CHỨNG (nếu tài sản bán đấu giá là bất động sản):</p>
            <p>Chứng nhận Hợp đồng mua bán tài sản bán đấu giá được ký kết vào hồi 14 giờ ngày 15 tháng 10 năm 2025 tại TP. Hồ Chí Minh; các bên ký kết Hợp đồng có năng lực hành vi dân sự đầy đủ; chữ ký của các bên trong Hợp đồng là đúng; nội dung thoả thuận của các bên phù hợp với quy định của Nghị định số 05/2005/NĐ-CP ngày 18/1/2005 của Chính phủ về bán đấu giá tài sản.</p>
            
            <div class="notary-signature">
                <p>CÔNG CHỨNG VIÊN<br>Nguyễn Thị E</p>
                <p>(Ký, ghi rõ họ tên và đóng dấu)</p>
            </div>
        </div>
    </div>
</body>
</html>