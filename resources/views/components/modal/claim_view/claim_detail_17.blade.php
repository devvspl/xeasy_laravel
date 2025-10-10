<div class="row modal-ncgid" data-modal-ncgid="{{ $cgId }}">
    <div class="col-md-4 mb-3">
        <input type="date" class="form-control" id="bill_date" name="bill_date" placeholder="Bill Date" />
    </div>
    <div class="col-12">
        <table class="table table-bordered" id="items_table">
            <thead class="table-light">
                <tr>
                    <th>Particulars</th>
                    <th>Remark</th>
                    <th>Amount</th>
                    <th>
                        <button type="button" class="btn btn-sm btn-success" id="add_item"><i class="ri-add-circle-fill"></i></button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="particulars[]" class="form-control" placeholder="Particulars" /></td>
                    <td><input type="text" name="remark[]" class="form-control" placeholder="Remark" /></td>
                    <td><input type="number" name="amount[]" class="form-control amount_input" placeholder="Amount" /></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove_item"><i class="ri-delete-bin-fill"></i></button>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total:</th>
                    <th colspan="2"><input type="text" id="total_amount" class="form-control" readonly /></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
    $(document).ready(function () {
        const today = new Date().toISOString().split("T")[0];
        flatpickr("#bill_date", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: today,
        });

        function updateTotal() {
            let total = 0;
            $(".amount_input").each(function () {
                const val = parseFloat($(this).val());
                if (!isNaN(val)) total += val;
            });
            $("#total_amount").val(total.toFixed(2));
        }

        $("#add_item").click(function () {
            const newRow = `<tr>
            <td><input type="text" name="particulars[]" class="form-control" placeholder="Particulars" /></td>
            <td><input type="text" name="remark[]" class="form-control" placeholder="Remark" /></td>
            <td><input type="number" name="amount[]" class="form-control amount_input" placeholder="Amount" /></td>
            <td><button type="button" class="btn btn-sm btn-danger remove_item"><i class="ri-delete-bin-fill"></i></button></td>
        </tr>`;
            $("#items_table tbody").append(newRow);
            updateTotal();
        });
        
        $(document).on("click", ".remove_item", function () {
            if ($("#items_table tbody tr").length > 1) {
                $(this).closest("tr").remove();
                updateTotal();
            } else {
                showAlert("danger", "ri-error-warning-line", "At least one row is required");
            }
        });
        
        $(document).on("input", ".amount_input", function () {
            updateTotal();
        });
        
        updateTotal();
    });
</script>
