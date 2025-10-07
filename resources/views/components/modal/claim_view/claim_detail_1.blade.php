<div class="row">
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="billing_person" name="billing_person" placeholder="Billing Person" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="billing_address" name="billing_address"
            placeholder="Billing Address" />
    </div>
    <div class="col-md-4 mb-3">
        <input type="text" class="form-control" id="city_category" name="city_category"
            placeholder="City - Category" />
    </div>
    <div class="col-md-4 mb-3">
        <select class="form-select" id="hotel_name" name="hotel_name">
            <option value="">Select Hotel</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <input type="text" class="form-control" id="hotel_contact" name="hotel_contact"
            placeholder="Hotel Contact" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="email" class="form-control" id="hotel_email" name="hotel_email" placeholder="Hotel Email" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="hotel_address" name="hotel_address"
            placeholder="Hotel Address" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="meal_plan" name="meal_plan" value=""
            placeholder="Meal Plan" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="billing_instruction" name="billing_instruction" value=""
            placeholder="Billing Instruction" />
    </div>
    <div class="col-md-4 mb-3">
        <input type="date" class="form-control" id="bill_date" name="bill_date" placeholder="Bill Date" />
    </div>
    <div class="col-md-4 mb-3">
        <input type="text" class="form-control" id="bill_no" name="bill_no" placeholder="Bill No." />
    </div>
    <div class="col-md-4 mb-3">
        <input type="text" class="form-control" id="duration" name="duration" placeholder="Duration" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="datetime-local" class="form-control" id="arrival_datetime" name="arrival_datetime"
            placeholder="Arrival Date/Time" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="datetime-local" class="form-control" id="departure_datetime" name="departure_datetime"
            placeholder="Departure Date/Time" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="gst_rate" name="gst_rate" placeholder="GST/Tax Rate" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="number" class="form-control" id="no_of_pax" name="no_of_pax" value=""
            placeholder="No. of Pax" />
    </div>
    <div class="col-md-6 mb-3">
        <input type="text" class="form-control" id="other_pax" name="other_pax"
            placeholder="Other Pax Details" />
    </div>
    <div class="col-md-6 mb-3">
        <select class="form-select" id="pax_details" name="pax_details" multiple placeholder="Select Pax Details">
            <option value="">Select Pax Details</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <select class="form-select" id="payment_mode" name="payment_mode">
            <option value="">Select Payment Mode</option>
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Online">Online</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <input type="number" class="form-control" id="total_amount" name="total_amount" value=""
            placeholder="Total Amount" />
    </div>
</div>
<script>
    $(document).ready(function() {
        $("#hotel_name").select2({
            dropdownParent: $("#claimDetailModal"),
            width: "100%",
            placeholder: "Select Hotel",
            allowClear: true,
        });

        $("#pax_details").select2({
            dropdownParent: $("#claimDetailModal"),
            width: "100%",
            placeholder: "Select Pax Details",
            allowClear: true,
        });

        $("#payment_mode").select2({
            dropdownParent: $("#claimDetailModal"),
            width: "100%",
            placeholder: "Select Payment Mode",
            allowClear: true,
        });

        const today = new Date().toISOString().split("T")[0];
        flatpickr("#bill_date", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: today,
        });
        flatpickr("#arrival_datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            maxDate: "today",
            time_24hr: true,
        });
        flatpickr("#departure_datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            maxDate: "today",
            time_24hr: true,
        });
    });
</script>
