<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExpenseClaim;
class ClaimViewController extends Controller {
    public function getClaimDetailView(Request $request) {
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
}
