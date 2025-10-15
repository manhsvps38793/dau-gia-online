<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EContracts;
use Illuminate\Support\Facades\Validator;
use App\Models\contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
            'contract_id'   => 'required|exists:contracts,contract_id',
            'contract_type' => 'required|string',
            'signed_by'     => 'required|exists:Users,user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $contract = contract::findOrFail($request->contract_id);

        // Sinh PDF nếu muốn
        $pdf = PDF::loadView('contracts.dichvu_template', [
            'contract' => $contract,
            'owner' => $request->signed_by,
            'session' => $contract->session,
        ]);
        $fileName = 'contracts/contract_'.$contract->contract_id.'.pdf';
        Storage::put('public/'.$fileName, $pdf->output());

        $econtract = EContracts::create([
            'contract_id'   => $contract->contract_id,
            'contract_type' => 'MuaBanTaiSan',
            'signed_by'     => $request->signed_by,
            'file_url'      => Storage::url('public/'.$fileName),
            'session_id'    => $contract->session_id,
        ]);

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
