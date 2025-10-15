<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EContracts;
use Illuminate\Support\Facades\Validator;

class EContractsController extends Controller
{
    public function index()
    {
        $econtracts = EContracts::with(['contract', 'session', 'signer'])
            ->orderBy('econtract_id', 'desc') 
            ->get();
        return response()->json([
            'status' => true,
            'econtracts' => $econtracts
        ]);
    }

    public function show($id)
    {
        $econtract = EContracts::with(['contract', 'session', 'signer'])->findOrFail($id);
        return response()->json([
            'status' => true,
            'econtract' => $econtract
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_type' => 'required|string',
            'file_url'      => 'required|url',
            'signed_by'     => 'required|exists:Users,user_id',
            'session_id'    => 'required|exists:AuctionSessions,session_id',
            'contract_id'   => 'required|exists:contracts,contract_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $econtract = EContracts::create($request->all());

        return response()->json([
            'status' => true,
            'econtract' => $econtract
        ]);
    }

    public function update(Request $request, $id)
    {
        $econtract = EContracts::findOrFail($id);
        $econtract->update($request->all());

        return response()->json([
            'status' => true,
            'econtract' => $econtract
        ]);
    }

    public function destroy($id)
    {
        $econtract = EContracts::findOrFail($id);
        $econtract->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa hợp đồng điện tử thành công'
        ]);
    }
}
