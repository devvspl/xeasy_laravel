@php
    $totalClaim = 0;
    $totalVerified = 0;
    $totalApproved = 0;
    $totalFinance = 0;
@endphp
<div class="row">

    <div class="col-md-6 mb-1">
        <small class="form-text text-muted">Bill Date</small>
        <input type="text" class="form-control" id="bill_date" name="bill_date" placeholder="Enter Bill Date">
    </div>


    <div class="col-md-6 mb-1">
        <small class="form-text text-muted">Activity Type</small>
        <select name="activity_type" id="activity_type" class="form-control">
            <option value="">-- Select Activity Type --</option>
            @foreach ($activity_types as $typeItem)
                <option value="{{ $typeItem->id }}">{{ $typeItem->type_name }}</option>
            @endforeach
        </select>
    </div>


    <div class="col-md-6 mb-1">
        <small class="form-text text-muted">Crops</small>
        <select name="crops[]" id="crops" class="form-control" multiple>
            @foreach ($crops as $cropItem)
                <option value="{{ $cropItem->id }}">{{ $cropItem->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-1">
        @if ($employee->DepartmentId !== 17)
            <small class="form-text text-muted">Variety</small>
            <select name="variety[]" id="variety" class="form-control" multiple></select>
        @else
            <small class="form-text text-muted">Trial No.</small>
            <input type="text" name="trial_no[]" id="trial_no" placeholder="Enter Trial No." class="form-control">
        @endif
    </div>
</div>


<div class="table-responsive modal-ncgid" data-modal-ncgid="{{ $cgId }}">
    <table class="table table-bordered expense-table" style="margin-top: 10px;">
        <thead class="table-light">
            <tr>
                <th>Expense Head</th>
                <th>Attachment</th>
                <th>Claim Amt</th>
                {{-- <th>Ver. Amt</th>
                <th>Ver. Remark</th>
                <th>Appr. Amt</th>
                <th>Appr. Remark</th>
                <th>Fin. Amt</th>
                <th>Fin. Remark</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach ($expenses as $expense)
                @php
                    $head = $expense['value'];
                    $amount = (float) ($filledData->{$head} ?? 0);
                    $verAmt = (float) ($filledData->{"ver_{$head}"} ?? 0);
                    $verRemark = $filledData->{"ver_remark_{$head}"} ?? '';
                    $appAmt = (float) ($filledData->{"app_{$head}"} ?? 0);
                    $appRemark = $filledData->{"app_remark_{$head}"} ?? '';
                    $finAmt = (float) ($filledData->{"pay_{$head}"} ?? 0);
                    $finRemark = $filledData->{"pay_remark_{$head}"} ?? '';

                    $totalClaim += $amount;
                    $totalVerified += $verAmt;
                    $totalApproved += $appAmt;
                    $totalFinance += $finAmt;

                    $fileColumn = str_replace('[]', '', $expense['file_input']);
                    $files = isset($filledData->{$fileColumn}) ? json_decode($filledData->{$fileColumn}, true) : [];
                @endphp

                <tr>
                    <td style="font-size: 12px;">
                        {{ $expense['label'] }}
                        @if ($expense['file_required'])
                            <span style="color:red">*</span>
                        @endif
                    </td>

                    <td class="text-center">
                        <div class="file-upload-wrapper">
                            <i class="ri-upload-2-fill upload-icon" title="Upload files"></i>
                            <input type="file" class="file-input" name="files_{{ $expense['value'] }}[]" multiple
                                hidden>
                            <div class="file-list">
                                @if (is_array($files))
                                    @foreach ($files as $file)
                                        <div>
                                            <span class="file-name" data-preloaded="1" data-file="{{ $file }}">
                                                {{ $file }}
                                            </span>
                                            <i class="ri-delete-bin-line remove-file"></i>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </td>

                    <td><input type="text" class="form-control amount claim" value="{{ $amount }}"></td>
                    {{-- <td><input type="text" class="form-control amount verified" value="{{ $verAmt }}"></td>
                <td><input type="text" class="form-control" value="{{ $verRemark }}"></td>
                <td><input type="text" class="form-control amount approved" value="{{ $appAmt }}"></td>
                <td><input type="text" class="form-control" value="{{ $appRemark }}"></td>
                <td><input type="text" class="form-control amount finance" value="{{ $finAmt }}"></td>
                <td><input type="text" class="form-control" value="{{ $finRemark }}"></td> --}}
                </tr>
            @endforeach

            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="2" class="text-start">Total:</td>
                <td><input id="totalClaim" type="text" class="form-control text-end"
                        value="{{ number_format($totalClaim) }}" readonly></td>
                {{-- <td><input id="totalVerified" type="text" class="form-control text-end"
                        value="{{ number_format($totalVerified) }}" readonly></td>
                <td></td>
                <td><input id="totalApproved" type="text" class="form-control text-end"
                        value="{{ number_format($totalApproved) }}" readonly></td>
                <td></td>
                <td><input id="totalFinance" type="text" class="form-control text-end"
                        value="{{ number_format($totalFinance) }}" readonly></td>
                <td></td> --}}
            </tr>
        </tbody>
    </table>
</div>
<script>
    $(document).ready(function() {


        $(document).on('input', '.amount', updateTotals);

   
        $('#activity_type').select2({
            dropdownParent: $('#claimDetailModal'),
            width: '100%',
            placeholder: 'Select Activity Type',
            allowClear: true
        });


        $('#crops').select2({
            dropdownParent: $('#claimDetailModal'),
            width: '100%',
            placeholder: 'Select Crops',
            allowClear: true
        });

  
        $('#variety').select2({
            dropdownParent: $('#claimDetailModal'),
            width: '100%',
            placeholder: 'Select Varieties',
            allowClear: true
        });



        const today = new Date().toISOString().split('T')[0];
        flatpickr("#bill_date", {
            dateFormat: "Y-m-d",
            enableYearSelection: true,
            maxDate: "today",
            defaultDate: today,
            onReady: function() {
                this.isDisabled = false;
            },
        });

        $(document).on('change', '#crops', function() {
            var selectedCrops = $(this).val();

            if (selectedCrops && selectedCrops.length > 0) {
                $.ajax({
                    url: '/get-varieties',
                    method: 'POST',
                    data: {
                        crop_ids: selectedCrops,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {

                        $('#variety').empty();


                        $.each(response, function(index, variety) {
                            $('#variety').append(
                                $('<option>', {
                                    value: variety.id,
                                    text: variety.variety_name
                                })
                            );
                        });

                        $('#variety').trigger('change');
                    }
                });
            } else {
                $('#variety').empty().trigger('change');
            }
        });




        $(document).on('click', '.upload-icon', function() {
            $(this).siblings('.file-input').trigger('click');
        });



        $(document).on('change', '.file-input', function() {
            const input = this;
            const fileListDiv = $(this).siblings('.file-list');



            const existingFiles = new Set(
                fileListDiv.find('.file-name').map(function() {
                    return $(this).data('file');
                }).get()
            );

            Array.from(input.files).forEach((file) => {
                if (existingFiles.has(file.name)) {


                    showAlert("danger", "ri-error-warning-line",
                        `File "${file.name}" is already selected.`);
                    return;

                }

                const fileDiv = $('<div></div>');
                const fileNameLink = $('<span class="file-name"></span>')
                    .text(file.name)
                    .attr('data-preloaded', 0)
                    .attr('data-file', file.name)
                    .css('cursor', 'pointer')
                    .on('click', function() {
                        const fileURL = URL.createObjectURL(file);
                        window.open(fileURL, '_blank');
                    });

                const removeBtn = $(
                    '<i class="ri-delete-bin-line remove-file" title="Remove file"></i>');
                removeBtn.on('click', function() {
                    removeFile(input, file.name);
                    fileDiv.remove();
                });

                fileDiv.append(fileNameLink, removeBtn);
                fileListDiv.append(fileDiv);
            });
        });



        $(document).on('click', '.remove-file', function() {
            const parentDiv = $(this).parent();
            const fileName = parentDiv.find('.file-name').data('file');
            const isPreloaded = parentDiv.find('.file-name').data('preloaded') == 1;
            const input = $(this).closest('.file-upload-wrapper').find('.file-input')[0];
            if (!confirm(`Are you sure you want to remove "${fileName}"?`)) {
                return;

            }
            if (isPreloaded) {


                parentDiv.attr('data-remove', '1').hide();
            } else {
                removeFile(input, fileName);
                parentDiv.remove();
            }
        });



        function removeFile(input, fileName) {
            const dt = new DataTransfer();
            Array.from(input.files).forEach((file) => {
                if (file.name !== fileName) dt.items.add(file);
            });
            input.files = dt.files;
        }



        function updateTotals() {
            $('#totalClaim').val(numberFormat(sumInputs('.claim')));
            $('#totalVerified').val(numberFormat(sumInputs('.verified')));
            $('#totalApproved').val(numberFormat(sumInputs('.approved')));
            $('#totalFinance').val(numberFormat(sumInputs('.finance')));
        }

        function sumInputs(selector) {
            let total = 0;
            $(selector).each(function() {
                total += parseFloat($(this).val().replace(/,/g, '') || 0);
            });
            return total;
        }

        function numberFormat(num) {
            return num.toLocaleString('en-IN', {
                maximumFractionDigits: 2
            });
        }

        updateTotals();
    });
</script>
