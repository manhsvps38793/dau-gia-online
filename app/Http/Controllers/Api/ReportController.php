<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\AuctionSession;
use App\Models\Bid;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // POST /api/reports/generate
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string|in:TongQuan,KPI'
        ]);

        $user = $request->user(); // người tạo báo cáo

        $content = '';

        if ($request->report_type == 'TongQuan') {
            $totalItems    = \App\Models\AuctionItem::count();
            $totalSessions = AuctionSession::count();
            $totalBids     = Bid::count();
            $totalContracts= Contract::count();
            $totalPayments = Payment::count();

            $content = "Tổng quan hệ thống: Sản phẩm=$totalItems, Phiên đấu giá=$totalSessions, Lượt đặt giá=$totalBids, Hợp đồng=$totalContracts, Thanh toán=$totalPayments";
        } elseif ($request->report_type == 'KPI') {
            // ví dụ KPI: số lượt thắng của từng đấu giá viên
            $kpis = Contract::with('winner')->get()->groupBy('winner_id')->map(function($contracts, $winner_id){
                return [
                    'winner_id' => $winner_id,
                    'winner_name' => $contracts[0]->winner->full_name ?? '',
                    'wins' => count($contracts)
                ];
            });
            $content = json_encode($kpis);
        }

        $report = Report::create([
            'generated_by' => $user->user_id,
            'report_type'  => $request->report_type,
            'content'      => $content,
            'created_at'   => now()
        ]);

        return response()->json([
            'status' => true,
            'message'=> 'Tạo báo cáo thành công',
            'report' => $report
        ]);
    }

    // GET /api/reports
    public function listReports()
    {
        $reports = Report::with('user')->orderBy('created_at','desc')->get();
        return response()->json([
            'status' => true,
            'reports' => $reports
        ]);
    }
}
