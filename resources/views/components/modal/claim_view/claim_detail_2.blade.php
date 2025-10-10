<div class="row modal-ncgid" data-modal-ncgid="{{ $cgId }}">
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Passenger Name</small>
        <input type="text" class="form-control" id="passenger" name="passenger" placeholder="Passenger Name" value="" readonly />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Agent Name</small>
        <select class="form-control" id="agent_name" name="agent_name">
            <option value="">Select Agent Name</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Flight Name</small>
        <input type="text" class="form-control" id="flight_name" name="flight_name" placeholder="Flight Name" value="" />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Class</small>
        <select class="form-control" id="class" name="class">
            <option value="Economy" selected>Economy</option>
            <option value="Business">Business</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">PNR Number</small>
        <input type="text" class="form-control" id="pnr_number" name="pnr_number" placeholder="PNR Number" value="" />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Booking Date</small>
        <input type="date" class="form-control" id="booking_date" name="booking_date" placeholder="Booking Date" />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Journey Date</small>
        <input type="date" class="form-control" id="journey_date" name="journey_date" placeholder="Journey Date" />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Period (Days)</small>
        <input type="number" class="form-control" id="period" name="period" placeholder="Period (Days)" value="0" readonly />
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Journey From</small>
        <select class="form-control" id="journey_from" name="journey_from">
            <option value="Raipur" selected>Raipur</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Journey Upto</small>
        <select class="form-control" id="journey_upto" name="journey_upto">
            <option value="Delhi NCR" selected>Delhi NCR</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <small class="form-text text-muted">Travel Insurance</small>
        <input type="text" class="form-control" id="travel_insurance" name="travel_insurance" placeholder="Travel Insurance" />
    </div>
</div>

<script>
    $(document).ready(function() {
        const today = new Date().toISOString().split("T")[0];
        flatpickr("#booking_date, #journey_date", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: today,
        });
    });
</script>
