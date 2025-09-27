$(document).ready(function () {
    flatpickr(".effective-date-input", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d-m-Y",
        maxDate: "today",
    });

    // store original values for reset
    $(".is-active-switch, .approval-select, .delayed-day-select").each(function () {
        $(this).data(
            "original-value",
            $(this).is(":checkbox") ? $(this).is(":checked") : $(this).val()
        );
    });

    $(".save-btn").on("click", function () {
        var departmentId = $(this).data("department-id");
        var $row = $('tr[data-department-id="' + departmentId + '"]');
        var $checkbox = $row.find(".is-active-switch");
        var $select = $row.find(".approval-select");
        var $delayedDay = $row.find(".delayed-day-select");
        var isActive = $checkbox.is(":checked") ? 1 : 0;
        var effectiveDate = $row.find(".effective-date-input").val();
        var approvalType = $select.val();
        var delayedDay = $delayedDay.val();

        if (isActive) {
            // check approval type
            if (!approvalType) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    "Approval Type is required when Enable Check is selected."
                );
                return;
            }

            // check effective date
            if (!effectiveDate) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    "Effective Date is required when Enable Check is selected."
                );
                return;
            }

            // check delayed day
            if (!delayedDay) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    "Delayed Day is required when Enable Check is selected."
                );
                return;
            }
        }

        $("#confirmSaveModal").modal("show");
        $("#confirmSaveBtn")
            .off("click")
            .on("click", function () {
                saveSetting(
                    this,
                    departmentId,
                    isActive,
                    approvalType,
                    delayedDay,
                    effectiveDate,
                    $row,
                    $checkbox,
                    $select,
                    $delayedDay
                );
                $("#confirmSaveModal").modal("hide");
            });
    });

    $("#confirmSaveModal").on("hidden.bs.modal", function () {
        var departmentId = $("#confirmSaveBtn").data("department-id");
        if (departmentId) {
            var $row = $('tr[data-department-id="' + departmentId + '"]');
            var $checkbox = $row.find(".is-active-switch");
            var $select = $row.find(".approval-select");
            var $delayedDay = $row.find(".delayed-day-select");
            $checkbox.prop("checked", $checkbox.data("original-value"));
            $select.val($select.data("original-value"));
            $delayedDay.val($delayedDay.data("original-value"));
        }
    });

    function saveSetting(
        buttonEl,
        departmentId,
        isActive,
        approvalType,
        delayedDay,
        effectiveDate,
        $row,
        $checkbox,
        $select,
        $delayedDay
    ) {
        var data = {};
        if (isActive !== null) data.is_active = isActive;
        if (approvalType !== null) data.approval_type = approvalType;
        if (effectiveDate !== null) data.effective_date = effectiveDate;
        if (delayedDay !== null) data.delayed_day = delayedDay;

        $.ajax({
            url: "odo-backdate-setting/" + departmentId,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: data,
            beforeSend: function () {
                startLoader({ currentTarget: buttonEl });
            },
            success: function (response) {
                showAlert(
                    "success",
                    "ri-checkbox-circle-line",
                    response.message || "Setting updated successfully!"
                );
                $checkbox.data("original-value", isActive);
                $select.data("original-value", approvalType);
                $delayedDay.data("original-value", delayedDay);
            },
            error: function (xhr) {
                var errorMsg = xhr.responseJSON.message || "An error occurred";
                showAlert("danger", "ri-error-warning-line", errorMsg);
                $checkbox.prop("checked", $checkbox.data("original-value"));
                $select.val($select.data("original-value"));
                $delayedDay.val($delayedDay.data("original-value"));
            },
            complete: function () {
                endLoader({ currentTarget: buttonEl });
            },
        });
    }
});
