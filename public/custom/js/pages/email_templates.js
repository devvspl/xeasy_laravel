$(document).ready(function () {
    $("#templateMasterTable").DataTable({
        ordering: false,
        searching: true,
        paging: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
    });

    // Initialize TinyMCE for body_html (self-hosted, free version)
    tinymce.init({
        selector: "#body_html",
        plugins: "lists charmap preview anchor", // removed link & image
        toolbar:
            "undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist", // removed link & image
        height: 400,
        menubar: false,
        statusbar: false,
        branding: false,
        license_key: "gpl", // keep this for free version
        setup: function (editor) {
            editor.on("change", function () {
                editor.save();
            });
        },
    });

    $("#templateBtn").click(function () {
        $("#templateModalLabel").text("Add New Email Template");
        $("#templateForm")[0].reset();
        tinymce.get("body_html").setContent("");
        $("#variableContainer").html(`
            <div class="variable-row mb-2">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="variables[0][variable_name]" placeholder="Variable Name (e.g., {{user_name}})" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="variables[0][description]" placeholder="Description" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-variable"><i class="ri-delete-bin-5-fill"></i></button>
                    </div>
                </div>
            </div>
        `);
        $("#saveTemplateBtn").data("template-id", null);
        $("#templateModal").modal("show");
    });

    let variableIndex = 1;
    $("#addVariableBtn").click(function () {
        $("#variableContainer").append(`
            <div class="variable-row mb-2">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="variables[${variableIndex}][variable_name]" placeholder="Variable Name (e.g., {{user_name}})" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="variables[${variableIndex}][description]" placeholder="Description" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-variable"><i class="ri-delete-bin-5-fill"></i></button>
                    </div>
                </div>
            </div>
        `);
        variableIndex++;
    });

    $(document).on("click", ".remove-variable", function () {
        if ($(".variable-row").length > 1) {
            $(this).closest(".variable-row").remove();
        }
    });

    $("#saveTemplateBtn").click(function (event) {
        event.preventDefault();
        const button = event.currentTarget;
        const form = $("#templateForm")[0];
        const formData = {
            name: $("#name").val(),
            subject: $("#subject").val(),
            body_html: tinymce.get("body_html").getContent(),
            is_active: $("#is_active").is(":checked") ? 1 : 0,
            category: $("#category").val() || null,
            variables: [],
            id: $(button).data("template-id") || null,
        };

        $(".variable-row").each(function () {
            const variableName = $(this)
                .find("input[name$='[variable_name]']")
                .val();
            const description = $(this)
                .find("input[name$='[description]']")
                .val();
            if (variableName && description) {
                formData.variables.push({
                    variable_name: variableName,
                    description: description,
                });
            }
        });

        const requestType = formData.id ? "PUT" : "POST";
        const url = formData.id
            ? `email-template/${formData.id}`
            : "email-template";

        $.ajax({
            url: url,
            type: requestType,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: formData,
            dataType: "json",
            beforeSend: function () {
                startLoader({ currentTarget: button });
            },
            success: function (response) {
                if (response.success) {
                    showAlert(
                        "success",
                        "ri-checkbox-circle-line",
                        response.message || "Email template saved successfully!"
                    );
                    setTimeout(() => {
                        window.location.href = window.location.href;
                    }, 2000);
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message || "An error occurred while saving."
                    );
                }
            },
            error: function (xhr) {
                let errorMsg = "Failed to save email template.";
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        errorMsg = Object.values(xhr.responseJSON.errors)
                            .flat()
                            .join(" ");
                    } else if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                }
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({ currentTarget: button });
            },
        });
    });

    $(document).on("click", ".edit-template", function (event) {
        event.preventDefault();
        const templateId = $(this).data("id");
        const button = event.currentTarget;

        $.ajax({
            url: `email-template/${templateId}/edit`,
            type: "GET",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            beforeSend: function () {
                startLoader({ currentTarget: button });
            },
            success: function (response) {
                if (response.success) {
                    const template = response.data;
                    $("#templateModalLabel").text(
                        `Edit Email Template - ${template.name}`
                    );
                    $("#name").val(template.name);
                    $("#subject").val(template.subject);
                    tinymce
                        .get("body_html")
                        .setContent(template.body_html || "");
                    $("#is_active").prop("checked", template.is_active == 1);
                    $("#category").val(template.category || "");

                    // Populate variables
                    $("#variableContainer").empty();
                    variableIndex = 0;
                    if (template.variables && template.variables.length > 0) {
                        template.variables.forEach((variable, index) => {
                            $("#variableContainer").append(`
                                <div class="variable-row mb-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="variables[${index}][variable_name]" value="${variable.variable_name}" placeholder="Variable Name (e.g., {{user_name}})" required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="variables[${index}][description]" value="${variable.description}" placeholder="Description" required>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-variable"><i class="ri-delete-bin-5-fill"></i></button>
                                        </div>
                                    </div>
                                </div>
                            `);
                            variableIndex = index + 1;
                        });
                    } else {
                        $("#variableContainer").html(`
                            <div class="variable-row mb-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="variables[0][variable_name]" placeholder="Variable Name (e.g., {{user_name}})" required>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="variables[0][description]" placeholder="Description" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-variable"><i class="ri-delete-bin-5-fill"></i></button>
                                    </div>
                                </div>
                            </div>
                        `);
                        variableIndex = 1;
                    }

                    $("#saveTemplateBtn").data("template-id", template.id);
                    $("#templateModal").modal("show");
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message || "Error fetching email template."
                    );
                }
            },
            error: function (xhr) {
                const errorMsg =
                    xhr.responseJSON?.message ||
                    "Failed to fetch email template.";
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({ currentTarget: button });
            },
        });
    });

    $(document).on("click", ".delete-template", function (event) {
        event.preventDefault();
        const templateId = $(this).data("id");
        const confirmation = confirm(
            "Are you sure you want to delete this email template?"
        );
        if (confirmation) {
            $.ajax({
                url: `email-template/${templateId}`,
                type: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content"
                    ),
                },
                success: function (response) {
                    if (response.success) {
                        showAlert(
                            "success",
                            "ri-checkbox-circle-line",
                            response.message ||
                                "Email template deleted successfully!"
                        );
                        setTimeout(() => {
                            window.location.href = window.location.href;
                        }, 2000);
                    } else {
                        showAlert(
                            "danger",
                            "ri-error-warning-line",
                            response.message ||
                                "An error occurred while deleting."
                        );
                    }
                },
                error: function (xhr) {
                    const errorMsg =
                        xhr.responseJSON?.message ||
                        "Failed to delete email template.";
                    showAlert("danger", "ri-error-warning-line", errorMsg);
                },
            });
        }
    });

    let logTable = null;
    $(document).on("click", ".view-logs", function (event) {
        event.preventDefault();
        const templateId = $(this).data("id");
        const button = event.currentTarget;

        if (logTable) {
            logTable.destroy();
            $("#logTable tbody").empty();
        }

        logTable = $("#logTable").DataTable({
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            lengthChange: true,
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            ajax: {
                url: `/email-template/log/${templateId}`,
                type: "GET",
                beforeSend: function () {
                    startLoader({ currentTarget: button });
                },
                dataSrc: function (response) {
                    $("#logTemplateTitle").text(response.title || "Untitled");
                    return response.data || [];
                },
                error: function (xhr) {
                    console.error("Error fetching logs:", xhr);
                    alert("Error fetching logs.");
                },
                complete: function () {
                    endLoader({ currentTarget: button });
                },
            },
            columns: [
                { data: "event" },
                { data: "description" },
                {
                    data: "properties",
                    render: function (data) {
                        return formatChanges(data);
                    },
                },
                {
                    data: "causer",
                    render: function (data) {
                        return data && data.name ? data.name : "System";
                    },
                },
                {
                    data: "created_at",
                    render: function (data) {
                        return data ? new Date(data).toLocaleString() : "N/A";
                    },
                },
            ],
            order: [[4, "desc"]],
        });

        $("#logModal").modal("show");
    });

    function formatChanges(properties) {
        if (!properties || (!properties.attributes && !properties.old)) {
            return "No changes recorded";
        }

        let changes = "<ul style='text-align: left;'>";
        const now = new Date();

        for (let key in properties.attributes) {
            if (properties.old && key in properties.old) {
                if (key === "updated_at") {
                    const oldDate = new Date(properties.old[key]);
                    const newDate = new Date(properties.attributes[key]);
                    const diffMs = newDate - oldDate;
                    const diffMins = Math.floor(diffMs / 60000);

                    if (diffMins < 60) {
                        changes += `<li>Updated <strong>${diffMins} minute${
                            diffMins !== 1 ? "s" : ""
                        } ago</strong></li>`;
                    } else {
                        changes += `<li>Updated from <strong>${oldDate.toLocaleString(
                            "en-US",
                            { timeZone: "Asia/Kolkata" }
                        )}</strong> to <strong>${newDate.toLocaleString(
                            "en-US",
                            { timeZone: "Asia/Kolkata" }
                        )}</strong></li>`;
                    }
                } else {
                    changes += `<li>${
                        key.charAt(0).toUpperCase() + key.slice(1)
                    } changed from <strong>${
                        properties.old[key]
                    }</strong> to <strong>${
                        properties.attributes[key]
                    }</strong></li>`;
                }
            } else {
                changes += `<li>${
                    key.charAt(0).toUpperCase() + key.slice(1)
                } set to <strong>${
                    properties.attributes[key]
                }</strong> (created)</li>`;
            }
        }
        changes += "</ul>";
        return changes;
    }
});
