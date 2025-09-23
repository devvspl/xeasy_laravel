$(document).ready(function () {
    $(".is-active-switch, .approval-select").each(function () {
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
        var isActive = $checkbox.is(":checked") ? 1 : 0;
        var approvalType = $select.val();

        if (isActive && !approvalType) {
            showAlert(
                "danger",
                "ri-error-warning-line",
                "Approval type is required when Enable Check is selected."
            );
            return;
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
                    $row,
                    $checkbox,
                    $select
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
            $checkbox.prop("checked", $checkbox.data("original-value"));
            $select.val($select.data("original-value"));
        }
    });

    function saveSetting(
        buttonEl,
        departmentId,
        isActive,
        approvalType,
        $row,
        $checkbox,
        $select
    ) {
        var data = {};
        if (isActive !== null) data.is_active = isActive;
        if (approvalType !== null) data.approval_type = approvalType;

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
            },
            error: function (xhr) {
                var errorMsg = xhr.responseJSON.message || "An error occurred";
                showAlert("danger", "ri-error-warning-line", errorMsg);
                $checkbox.prop("checked", $checkbox.data("original-value"));
                $select.val($select.data("original-value"));
            },
            complete: function () {
                endLoader({ currentTarget: buttonEl });
            },
        });
    }
});
