<div class="row mb-3 modal-ncgid" data-modal-ncgid="{{ $cgId }}">
    <div class="col-md-4">
        <small class="form-text text-muted">Travel Date</small>
        <input type="text" class="form-control" id="travel_date" name="travel_date" placeholder="" />
    </div>
    <div class="col-md-4">
        <small class="form-text text-muted">Vehicle Type</small>
        <div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="vehicle" id="vehicle2w" value="2W" />
                <label class="form-check-label" for="vehicle2w">2W</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="vehicle" id="vehicle4w" value="4W" />
                <label class="form-check-label" for="vehicle4w">4W</label>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <small class="form-text text-muted">Vehicle Reg. No.</small>
        <input type="text" class="form-control" id="vehicle_reg" name="vehicle_reg"
            placeholder="" value="" />
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <small class="form-text text-muted">Trip Started On</small>
        <input type="text" class="form-control" id="trip_started" name="trip_started"
            placeholder="" />
    </div>
    <div class="col-md-6">
        <small class="form-text text-muted">Trip Ended On</small>
        <input type="text" class="form-control" id="trip_ended" name="trip_ended"
            placeholder="" />
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
            <tr>
                <th>Sn</th>
                <th>Opening Reading</th>
                <th>Closing Reading</th>
                <th>Km Running</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <input type="number" class="form-control" id="opening_reading" name="opening_reading"
                        placeholder="" />
                </td>
                <td>
                    <input type="number" class="form-control" id="closing_reading" name="closing_reading"
                        placeholder="" />
                </td>
                <td>
                    <input type="number" class="form-control" id="km_running" name="km_running"
                        placeholder="" readonly />
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <small class="form-text text-muted">Total KM</small>
        <input type="number" class="form-control" id="total_km" name="total_km" placeholder=""
            readonly />
    </div>
    <div class="col-md-6">
        <small class="form-text text-muted">Calculated Rate</small>
        <input type="text" class="form-control" id="rate" name="rate" placeholder=""
            readonly />
    </div>
</div>
<script>
    $(document).ready(function() {
        const today = new Date().toISOString().split("T")[0];
        flatpickr("#travel_date", {
            dateFormat: "Y-m-d",
            enableYearSelection: true,
            maxDate: "today",
            defaultDate: today,
            onReady: function() {
                this.isDisabled = false;
            },
        });
        flatpickr("#trip_started", {
            enableTime: true,
            dateFormat: "d-m-Y H:i",
            maxDate: "today",
            time_24hr: true,
        });
        flatpickr("#trip_ended", {
            enableTime: true,
            dateFormat: "d-m-Y H:i",
            maxDate: "today",
            time_24hr: true,
        });
        $(document).on("input", "#opening_reading, #closing_reading", function() {
            let opening = parseFloat($("#opening_reading").val()) || 0;
            let closing = parseFloat($("#closing_reading").val()) || 0;
            let kmRunning = closing - opening;
            $("#km_running").val(kmRunning > 0 ? kmRunning : "");
            $("#total_km").val(kmRunning > 0 ? kmRunning : "");
        });
        $(document).on("input", '#total_km, input[name="vehicle"]', function() {
            let totalKm = parseFloat($("#total_km").val()) || 0;
            let vehicle = $('input[name="vehicle"]:checked').val();
            let ratePerKm = vehicle === "2W" ? 5 : 10;
            $("#rate").val(totalKm * ratePerKm);
        });
    });
</script>
