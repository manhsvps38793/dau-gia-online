<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    // Lấy danh sách tất cả hợp đồng
    public function index()
    {
        $contracts = Contract::with('session.item', 'winner')->orderBy('signed_date','desc')->get();
        
        return response()->json([
            'status' => true,
            'contracts' => $contracts
        ]);
    }

    // Xem chi tiết hợp đồng theo ID
    public function show($id)
    {
        $contract = Contract::with('session.item', 'winner')->find($id);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'message' => 'Hợp đồng không tồn tại'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'contract' => $contract
        ]);
    }
    // Xóa hợp đồng theo ID
    public function destroy($id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json([
                'status' => false,
                'message' => 'Hợp đồng không tồn tại'
            ], 404);
        }

        // Tiến hành xóa
        $contract->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa hợp đồng thành công'
        ]);
    }

}
