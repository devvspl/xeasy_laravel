@php
    $totalAmount = 0;
@endphp
<table class="table table-bordered" style="margin-top: 10px;">
    <thead>
        <tr>
            <th style="text-align: left">Expense Head</th>
            <th>Attachment</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($expenses as $expense)
            @php
                $amount = $filledData->{$expense['value']} ?? 0;
                $totalAmount += $amount;
                $fileColumn = str_replace('[]', '', $expense['file_input']);
                $files = isset($filledData->{$fileColumn}) ? json_decode($filledData->{$fileColumn}, true) : [];
            @endphp
            <tr>
                <td style="text-align: left">
                    {{ $expense['label'] }}@if ($expense['file_required'])
                        <span style="color:red">*</span>
                    @endif
                </td>
                <td>
                    @if (is_array($files))
                        @foreach ($files as $index => $file)
                            @php
                                $fileUrl = Storage::disk('s3')->url(
                                    "Expense/activity/{$expense_detail->ClaimYearId}/{$expId}/{$expense_detail->CrBy}/{$file}",
                                );
                            @endphp
                            <a href="#" onclick="viewFile('{{ $fileUrl }}')" style="color: blue;">View
                                ({{ $index + 1 }})
                            </a><br>
                        @endforeach
                    @endif
                </td>
                <td>
                    <input style="padding: 2px 8px;" type="text" name="{{ $expense['value'] }}" class="form-control"
                        value="{{ $amount }}" readonly>
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2"><b>Total Amount:</b></td>
            <td>
                <input type="text" class="form-control" value="{{ $totalAmount }}" readonly>
            </td>
        </tr>
    </tbody>
</table>
