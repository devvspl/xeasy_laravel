$(document).ready(function () {
    $("#usersMasterTable").DataTable({
        ordering: false,
        searching: true,
        paging: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
    });
    $("#userModal").on("shown.bs.modal", function () {
        $("#role_id").select2({
            dropdownParent: $("#userModal"),
            width: "100%",
            placeholder: "Select a role",
        });
    });
    $("#addUserBtn").on("click", function () {
        selectRoleList("role_id");
        $("#addUserLabel").text("Add New User");
        $("#userName").val("");
        $("#email").val("").removeAttr("readonly");
        $("#password").val("").removeAttr("readonly");
    });
    $("#passwordAddon").on("click", function () {
        const passwordInput = $("#password");
        const icon = $(this).find("i");
        const type =
            passwordInput.attr("type") === "password" ? "text" : "password";
        passwordInput.attr("type", type);
        icon.toggleClass("ri-eye-fill ri-eye-off-fill");
    });
    $("#generatePassword").on("click", function () {
        const length = 12;
        const charset =
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        $("#password").val(password);
    });
    $("#saveUserBtn").click(function (event) {
        event.preventDefault();
        const button = event.currentTarget;

        const formData = {
            name: $("#userName").val(),
            email: $("#email").val(),
            password: $("#password").val(),
            role_id: $("#role_id").val(),
            is_active: $("#is_active").is(":checked") ? 1 : 0,
            id: $(button).data("user-id") || null,
        };

        const requestType = formData.id ? "PUT" : "POST";
        const url = formData.id ? `users/${formData.id}` : "users";

        $.ajax({
            url: url,
            type: requestType,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: formData,
            dataType: "json",
            beforeSend: function () {
                startLoader({
                    currentTarget: button,
                });
            },
            success: function (response) {
                if (response.success) {
                    showAlert(
                        "success",
                        "ri-checkbox-circle-line",
                        response.message || "User saved successfully!"
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
            error: function (xhr, status, error) {
                let errorMsg = "Failed to save user.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({
                    currentTarget: button,
                });
            },
        });
    });
    $(document).on("click", ".edit-user", function (event) {
        event.preventDefault();
        const userId = $(this).data("id");
        const button = event.currentTarget;

        $.ajax({
            url: "users/" + userId + "/edit",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            beforeSend: function () {
                startLoader({
                    currentTarget: button,
                });
            },
            type: "GET",
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    const user = response.data;
                    $("#addUserLabel").text("Edit User - " + user.name);
                    $("#userName").val(user.name);
                    $("#email").val(user.email).attr("readonly", true);
                    $("#password").val("").attr("readonly", true);
                    $("#role_id").val(user.role_id);
                    $("#is_active").prop("checked", user.is_active);
                    $("#saveUserBtn").attr("data-user-id", user.id);
                    const selectedRoles = user.role_ids || [];
                    selectRoleList("role_id", selectedRoles);
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message || "Error fetching user."
                    );
                }
            },
            error: function (xhr) {
                const errorMsg =
                    xhr.responseJSON?.message || "Failed to fetch user.";
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({
                    currentTarget: button,
                });
            },
        });
    });
    $(document).on("click", ".delete-user", function (event) {
        event.preventDefault();
        const userId = $(this).data("id");
        const confirmation = confirm(
            "Are you sure you want to delete this user?"
        );
        if (confirmation) {
            $.ajax({
                url: "users/" + userId,
                type: "DELETE",
                dataType: "json",
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
                            response.message || "user deleted successfully!"
                        );
                        setTimeout(() => {
                            window.location.href = window.location.href;
                        }, 5000);
                    } else {
                        showAlert(
                            "danger",
                            "ri-error-warning-line",
                            response.message ||
                                "An error occurred while deleting."
                        );
                    }
                },
                error: function (xhr, status, error) {
                    let errorMsg = "Failed to delete user.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAlert("danger", "ri-error-warning-line", errorMsg);
                },
            });
        }
    });
    $("#importEmployee").click(function (event) {
        event.preventDefault();
        $("#importEmployeeModal").modal("show");
    });
    $("#confirmImportBtn").click(function (event) {
        event.preventDefault();
        const button = event.currentTarget;

        $.ajax({
            url: "users/import",
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            dataType: "json",
            beforeSend: function () {
                startLoader({
                    currentTarget: button,
                });
            },
            success: function (response) {
                if (response.success) {
                    $("#importEmployeeModal").modal("hide");
                    showAlert(
                        "success",
                        "ri-checkbox-circle-line",
                        response.message || "Employees imported successfully!"
                    );
                    setTimeout(() => {
                        window.location.href = window.location.href;
                    }, 2000);
                } else {
                    $("#importEmployeeModal").modal("hide");
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message ||
                            "An error occurred while importing employees."
                    );
                }
            },
            error: function (xhr, status, error) {
                $("#importEmployeeModal").modal("hide");
                let errorMsg = "Failed to import employees.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({
                    currentTarget: button,
                });
            },
        });
    });
    $(document).on("click", ".permission-access-user", function (event) {
        const userId = $(this).data("id");
        const button = event.currentTarget;

        let userPermissions = [];

        $.ajax({
            url: `users/${userId}/permissions`,
            type: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            beforeSend: function () {
                startLoader({
                    currentTarget: button,
                });
            },
            success: function (response) {
                if (response.success) {
                    const groupedPermissions = response.data.permissions;
                    const userInfo = response.data.user;
                    $("#userNameTitle").text(` - ${userInfo.name}`);
                    userPermissions = [];
                    Object.keys(groupedPermissions).forEach((group) => {
                        groupedPermissions[group].forEach((permission) => {
                            userPermissions.push(permission.id);
                        });
                    });
                    $.ajax({
                        url: "permissions-list",
                        type: "GET",
                        dataType: "json",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                                "content"
                            ),
                        },
                        success: function (permResponse) {
                            if (permResponse.success) {
                                const categories = permResponse.data;
                                const container = $("#permissions-container");
                                container.empty();

                                if (Object.keys(categories).length === 0) {
                                    container.append(
                                        "<p>No permissions available.</p>"
                                    );
                                    return;
                                }

                                Object.keys(categories).forEach(
                                    (category, catIndex) => {
                                        const categoryId = `category-accor-${catIndex}`;
                                        let categoryHtml = `
                                    <div class="accordion-item material-shadow${
                                        catIndex > 0 ? " mt-2" : ""
                                    }">
                                        <h2 class="accordion-header" id="category-header-${catIndex}">
                                            <button class="accordion-button${
                                                catIndex === 0
                                                    ? ""
                                                    : " collapsed"
                                            }" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#${categoryId}" 
                                                    aria-expanded="${
                                                        catIndex === 0
                                                            ? "true"
                                                            : "false"
                                                    }" 
                                                    aria-controls="${categoryId}">
                                                ${category}
                                            </button>
                                        </h2>
                                        <div id="${categoryId}" 
                                             class="accordion-collapse collapse${
                                                 catIndex === 0 ? " show" : ""
                                             }" 
                                             aria-labelledby="category-header-${catIndex}" 
                                             data-bs-parent="#permissions-container">
                                            <div class="accordion-body">
                                                <div class="accordion nesting-accordion custom-accordionwithicon-plus">
                                `;

                                        Object.keys(
                                            categories[category]
                                        ).forEach((group, groupIndex) => {
                                            const permissions =
                                                categories[category][group];
                                            const accordionId = `role-accor-plus-${catIndex}-${groupIndex}`;
                                            let groupHtml = `
                                        <div class="accordion-item material-shadow${
                                            groupIndex > 0 ? " mt-2" : ""
                                        }">
                                            <h2 class="accordion-header" id="role-accordionwithplus-${catIndex}-${groupIndex}">
                                                <button class="accordion-button${
                                                    groupIndex === 0
                                                        ? ""
                                                        : " collapsed"
                                                }" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#${accordionId}" 
                                                        aria-expanded="${
                                                            groupIndex === 0
                                                                ? "true"
                                                                : "false"
                                                        }" 
                                                        aria-controls="${accordionId}">
                                                    ${group}
                                                </button>
                                            </h2>
                                            <div id="${accordionId}" 
                                                 class="accordion-collapse collapse${
                                                     groupIndex === 0
                                                         ? " show"
                                                         : ""
                                                 }" 
                                                 aria-labelledby="role-accordionwithplus-${catIndex}-${groupIndex}" 
                                                 data-bs-parent="#${categoryId}">
                                                <div class="accordion-body">
                                                    <div class="row">
                                    `;

                                            permissions.forEach(
                                                (permission) => {
                                                    const isChecked =
                                                        userPermissions.includes(
                                                            permission.id
                                                        );
                                                    groupHtml += `
                                            <div class="col-4 mb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>${
                                                        permission.name
                                                    }</span>
                                                    <div class="form-check form-switch" style="margin-top: 0px;">
                                                        <input class="form-check-input permission-toggle" 
                                                               type="checkbox" 
                                                               role="switch" 
                                                               id="toggle-${
                                                                   permission.id
                                                               }" 
                                                               data-user-id="${userId}" 
                                                               data-permission-id="${
                                                                   permission.id
                                                               }" 
                                                               ${
                                                                   isChecked
                                                                       ? "checked"
                                                                       : ""
                                                               }>
                                                        <label class="form-check-label" for="toggle-${
                                                            permission.id
                                                        }"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                                }
                                            );

                                            groupHtml += `
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                            categoryHtml += groupHtml;
                                        });

                                        categoryHtml += `
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                        container.append(categoryHtml);
                                    }
                                );
                            }
                        },
                        error: function (xhr) {
                            console.error("Failed to fetch permissions:", xhr);
                            showAlert(
                                "danger",
                                "ri-error-warning-line",
                                "Failed to fetch permissions."
                            );
                        },
                        complete: function () {
                            endLoader({
                                currentTarget: button,
                            });
                        },
                    });
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message || "Error fetching permissions."
                    );
                }
            },
            error: function (xhr) {
                const errorMsg =
                    xhr.responseJSON?.message || "Failed to fetch permissions.";
                showAlert("danger", "ri-error-warning-line", errorMsg);
            },
            complete: function () {
                endLoader({
                    currentTarget: button,
                });
            },
        });
    });

    $(document).on("change", ".permission-toggle", function () {
        const userId = $(this).data("user-id");
        const permissionId = $(this).data("permission-id");
        const isChecked = $(this).is(":checked");
        const actionUrl = isChecked
            ? `users/${userId}/permissions/assign`
            : `users/${userId}/permissions/revoke`;

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: { permission_id: permissionId },
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    showAlert(
                        "success",
                        "ri-check-line",
                        response.message ||
                            (isChecked
                                ? "Permission assigned successfully."
                                : "Permission revoked successfully.")
                    );
                    $(
                        '.permission-access-user[data-id="' + userId + '"]'
                    ).trigger("click");
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message ||
                            (isChecked
                                ? "Error assigning permission."
                                : "Error revoking permission.")
                    );
                    $(this).prop("checked", !isChecked);
                }
            }.bind(this),
            error: function (xhr) {
                const errorMsg =
                    xhr.responseJSON?.message ||
                    (isChecked
                        ? "Failed to assign permission."
                        : "Failed to revoke permission.");
                showAlert("danger", "ri-error-warning-line", errorMsg);
                $(this).prop("checked", !isChecked);
            }.bind(this),
        });
    });
    function selectRoleList(fieldId, ids = []) {
        $.ajax({
            url: "get-roles-list",
            type: "GET",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (data) {
                const options = data.data.map(function (item) {
                    const isSelected = ids.includes(item.id);
                    return new Option(
                        item.name,
                        item.id,
                        isSelected,
                        isSelected
                    );
                });
                $(`#${fieldId}`).empty().append(options);
                if (ids.length > 0) {
                    $(`#${fieldId}`).val(ids);
                }
                $(`#${fieldId}`).trigger("change");
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch groups:", error);
            },
        });
    }
});
