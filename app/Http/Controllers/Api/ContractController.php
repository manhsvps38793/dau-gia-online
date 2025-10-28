<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function destroy($sessionId)
    {
        $contract = Contract::where('session_id', $sessionId)->delete();

        if (!$contract) {
            return response()->json([
                'status' => false,
                'message' => 'Hợp đồng không tồn tại'
            ], 404);
        }


        return response()->json([
            'status' => true,
            'message' => 'Xóa hợp đồng thành công'
        ]);
    }




    public function update(Request $request, $id)
{
    $contract = Contract::find($id);
    if (!$contract) return response()->json(['status'=>false,'message'=>'Không tồn tại'],404);

    // Log để debug

    $contract->winner_id = $request->input('winner_id', $contract->winner_id);
    $contract->final_price = $request->input('final_price', $contract->final_price);
    $contract->signed_date = $request->input('signed_date', $contract->signed_date);
    $contract->status = $request->input('status', $contract->status);

  if ($request->hasFile('file')) {
        $fileUrl = $request->file('file')->store('contracts', 'public');
        $contract->file_path = Storage::url($fileUrl);
    }

    $contract->save();

    return response()->json([
        'status' => true,
        'message' => 'Cập nhật hợp đồng thành công',
        'contract' => $contract
    ]);
}


}
