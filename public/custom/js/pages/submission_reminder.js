$(document).ready(function () {
    $("#submissionReminderTable").DataTable({
        ordering: true,
        searching: true,
        paging: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        ajax: {
            url: "get-submission-reminder",
            type: "GET",
            dataSrc: function (json) {
                return json.data;
            },
            error: function (xhr, error, thrown) {
                console.error("Error fetching data:", error, thrown);
                alert("Failed to load data: " + xhr.responseJSON.message);
            },
        },
        columns: [
            {
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                },
            },
            { data: "employee_name" },
            { data: "email" },
            { data: "month" },
            { data: "status" },
            {
                data: "stage",
                className: "text-center",
                render: function (data, type, row) {
                    const sendDay = parseInt(
                        row.conditions?.send_day ||
                            row.debugger?.send_day ||
                            "",
                        10
                    );

                    function ordinalSuffix(n) {
                        if (!n) return "";
                        const j = n % 10,
                            k = n % 100;
                        if (j === 1 && k !== 11) return n + "st";
                        if (j === 2 && k !== 12) return n + "nd";
                        if (j === 3 && k !== 13) return n + "rd";
                        return n + "th";
                    }

                    const dayWithSuffix = ordinalSuffix(sendDay);

                    if (data == 1) {
                        return `<span class="badge bg-primary-subtle text-primary">First Reminder – ${dayWithSuffix}</span>`;
                    }
                    if (data == 2) {
                        return `<span class="badge bg-warning-subtle text-warning">Second Reminder – ${dayWithSuffix}</span>`;
                    }
                    if (data == 3) {
                        return `<span class="badge bg-danger-subtle text-danger">Final Reminder – ${dayWithSuffix}</span>`;
                    }
                    return '<span class="badge bg-secondary">-</span>';
                },
            },
            { data: "email_template_subject" },
            { data: "conditions.already_sent" },
        ],
    });
    $("#sendEmails").click(function (event) {
        event.preventDefault();
        $("#sendEmailsModal").modal("show");
    });
    $("#confirmSendEmailsBtn").click(function (event) {
        event.preventDefault();
        const button = event.currentTarget;

        $.ajax({
            url: "/admin/notifications/non-submitted/dynamic",
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            dataType: "json",
            beforeSend: function () {
                startLoader({ currentTarget: button });
            },
            success: function (response) {
                $("#sendEmailsModal").modal("hide");

                if (response.success) {
                    showAlert(
                        "success",
                        "ri-checkbox-circle-line",
                        response.message || "Reminder emails sent successfully!"
                    );

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message ||
                            "An error occurred while sending emails."
                    );
                }
            },
            error: function (xhr, status, error) {
                $("#sendEmailsModal").modal("hide");
                let errorMsg = "Failed to send reminder emails.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({ currentTarget: button });
            },
        });
    });
});
