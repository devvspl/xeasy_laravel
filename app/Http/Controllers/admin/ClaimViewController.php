<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExpenseClaim;
use Illuminate\Support\Facades\DB;

class ClaimViewController extends Controller
{
    public function getClaimDetailView(Request $request)
    {
        $request->validate(['claim_id' => 'required', 'expid' => 'required']);
        $claimId = $request->input('claim_id');
        $expid = $request->input('expid');
        $expense_detail = ExpenseClaim::where('ExpId', $expid)->first();
        if (!$expense_detail) {
            return response()->json(['error' => "No expense claim found for ExpId: {$expid}"], 404);
        }
        $componentPath = resource_path("views/components/modal/claim_view/claim_detail_{$claimId}.blade.php");
        if (file_exists($componentPath)) {
            $html = view("components.modal.claim_view.claim_detail_{$claimId}", ['claimId' => $claimId, 'expense_detail' => $expense_detail])->render();
        } else {
            $html = '<div class="alert alert-warning">Component for Claim ID ' . $claimId . ' not found.</div>';
        }
        return response()->json(['html' => $html]);
    }
    public function getActiveClaimTypes()
    {
        $claimTypes = DB::table('claimtype as ct')
            ->leftJoin('claimgroup as cg', 'cg.cgId', '=', 'ct.cgId')
            ->where('ct.ClaimStatus', 'A')
            ->whereIn('ct.cgId', [1, 7])
            ->orderBy('cg.cgName')
            ->orderBy('ct.ClaimName')
            ->get(['ct.ClaimId', 'ct.ClaimName', 'cg.cgName']);

        $grouped = [];

        foreach ($claimTypes as $claim) {
            $grouped[$claim->cgName][] = [
                'id' => $claim->ClaimId,
                'text' => $claim->ClaimName,
            ];
        }

        $result = [];

        foreach ($grouped as $group => $claims) {
            $result[] = [
                'text' => $group,
                'children' => $claims,
            ];
        }
        return $this->jsonSuccess($result, 'Claim type fetched successfully.');
    }
}
