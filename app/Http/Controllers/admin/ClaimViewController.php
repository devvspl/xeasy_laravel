<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExpenseClaim;
use App\Models\ClaimType;
use Illuminate\Support\Facades\DB;
use Storage;

class ClaimViewController extends Controller
{
    public function getClaimDetailView(Request $request)
    {
        $request->validate(['claim_id' => 'required', 'expid' => 'required']);
        $claimId = $request->input('claim_id');
        $expid = $request->input('expid');
        $yearId = session('year_id');
        $expense_detail = DB::connection('expense')->table(ExpenseClaim::tableName($yearId))->where('ExpId', $expid)->first();
        if (!$expense_detail) {
            return response()->json(['error' => "No expense claim found for ExpId: {$expid}"], 404);
        }
        $cgId = ClaimType::getCgIdByClaimId($claimId);
        $html = '';
        if ($cgId == 1) {
            $componentPath = resource_path("views/components/modal/claim_view/claim_detail_{$claimId}.blade.php");
            if (file_exists($componentPath)) {
                $html = view("components.modal.claim_view.claim_detail_{$claimId}", ['claimId' => $claimId, 'cgId' => $cgId, 'expense_detail' => $expense_detail])->render();
            } else {
                $html = '<div class="alert alert-warning">Component for Claim ID ' . $claimId . ' not found.</div>';
            }
        } elseif ($cgId == 7) {
            $activityRow = DB::connection('expense')->table('adv_activity_names')->where('activity_name', $claimId)->select('id as activity_name_id')->first();
            $activityId = $activityRow->activity_name_id ?? 0;
            $expenseHeads = DB::connection('expense')->table('adv_expense_head_name_mapping as m')->join('adv_expense_head_category as c', 'm.expense_head_id', '=', 'c.id')->where('m.activity_id', $activityId)->where('m.checked', 1)->orderBy('c.expense_head_name', 'ASC')->select('c.expense_head_name', 'c.short_code', 'c.field_type', 'c.has_file', 'c.file_required')->get();
            $expenses = [];
            foreach ($expenseHeads as $row) {
                $label = $row->expense_head_name;
                $value = preg_replace("/[^A-Za-z0-9 ]/", "", $label);
                $value = preg_replace("/\s+/", "_", trim($value));
                $value = preg_replace("/__+/", "_", $value);
                $value = strtolower($value);
                $expenses[] = ["label" => $label, "value" => $value, "file_input" => "file_" . $value . "[]", "field_type" => $row->field_type, "has_file" => (bool)$row->has_file, "file_required" => (bool)$row->file_required];
            }
            $componentPath = resource_path("views/components/modal/claim_view/claim_detail_g7.blade.php");
            if (file_exists($componentPath)) {
                $filledData = DB::connection('expense')->table("y{$yearId}_g7_expensefilldata")->where('ExpId', $expid)->first();
                $html = view("components.modal.claim_view.claim_detail_g7", ['claimId' => $claimId, 'cgId' => $cgId, 'expense_detail' => $expense_detail, 'expenses' => $expenses, 'filledData' => $filledData, 'expId' => $expid])->render();
            } else {
                $html = '<div class="alert alert-warning">Component for Claim ID ' . $claimId . ' not found.</div>';
            }
        } else {
            $html = '<div class="alert alert-warning">No component available for cgId = ' . $cgId . '.</div>';
        }
        return response()->json(['html' => $html]);
    }
    public function viewUploadedFiles(Request $request, $expid, $claimid)
    {
        $cgId = ClaimType::getCgIdByClaimId($claimid);
        $yearId = session('year_id');
        $uploadedFiles = [];
        if ($cgId == 1) {
            $uploadSequenceMap = ['File_Meeting_Hall' => 101, 'File_Lodging' => 102, 'File_Meals' => 103, 'File_Business_Entertainment' => 104, 'File_Others' => 105, 'File_Toll_Pass' => 106, 'File_Toll_Tax' => 107, 'File_International_Recharge' => 108, 'File_Printing_Stationery' => 109,];
            $uploadsTable = "y{$yearId}_claimuploads";
            $claimsTable = "y{$yearId}_expenseclaims";
            $files = DB::connection('expense')->table($uploadsTable . ' as u')->join($claimsTable . ' as c', 'u.ExpId', '=', 'c.ExpId')->where('u.ExpId', $expid)->orderBy('u.UploadSequence')->select('u.cuId', 'u.ExpId', 'u.FileName', 'u.UploadSequence', 'u.created_at', 'c.CrBy', 'c.ClaimYearId')->get();
            foreach ($files as $item) {
                $mappedName = array_search($item->UploadSequence, $uploadSequenceMap);
                $fileLabel = $mappedName ?? "Other_File";
                $uploadedFiles[] = ['sequence' => $item->UploadSequence ?? 999, 'cuId' => $item->cuId, 'file_column' => $fileLabel, 'file_path' => $item->FileName, 'file_url' => Storage::disk('s3')->url("Expense/{$item->ClaimYearId}/{$item->CrBy}/{$item->FileName}"), 'created_at' => $item->created_at,];
            }
        } elseif ($cgId == 7) {
            $g7Table = "y{$yearId}_g7_expensefilldata";
            $claimsTable = "y{$yearId}_expenseclaims";
            $claim = DB::connection('expense')->table($claimsTable)->where('ExpId', $expid)->select('CrBy', 'ClaimYearId')->first();
            if (!$claim) {
                return $this->jsonError('Claim not found', 404);
            }
            $data = DB::connection('expense')->table($g7Table)->where('ExpId', $expid)->first();
            if ($data) {
                foreach ((array)$data as $column => $value) {
                    if (str_starts_with($column, 'file_') && !empty($value)) {
                        $filesArray = json_decode($value, true);
                        if (is_array($filesArray)) {
                            foreach ($filesArray as $fileName) {
                                $uploadedFiles[] = ['sequence' => 999, 'file_column' => $column, 'file_path' => $fileName, 'file_url' => Storage::disk('s3')->url("Expense/activity/{$claim->ClaimYearId}/{$claimid}/{$claim->CrBy}/{$fileName}"),];
                            }
                        }
                    }
                }
            }
        }
        return $this->jsonSuccess(['expid' => $expid, 'cgid' => $cgId, 'uploaded_files' => $uploadedFiles,]);
    }
    public function getActiveClaimTypes()
    {
        $claimTypes = DB::table('claimtype as ct')->leftJoin('claimgroup as cg', 'cg.cgId', '=', 'ct.cgId')->whereIn('ct.ClaimStatus', ['A', 'B'])->whereIn('ct.cgId', [1, 7])->orderBy('cg.cgName')->orderBy('ct.ClaimName')->get(['ct.ClaimId', 'ct.ClaimName', 'cg.cgName']);
        $grouped = [];
        foreach ($claimTypes as $claim) {
            $grouped[$claim->cgName][] = ['id' => $claim->ClaimId, 'text' => $claim->ClaimName,];
        }
        $result = [];
        foreach ($grouped as $group => $claims) {
            $result[] = ['text' => $group, 'children' => $claims,];
        }
        return $this->jsonSuccess($result, 'Claim type fetched successfully.');
    }
}
