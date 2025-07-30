function showAlert(
    type = "primary",
    icon = "ri-user-smile-line",
    message = "Primary - Rounded label alert",
    duration = 5000
) {
    const timestamp = Date.now();
    const alertId = `alert-${timestamp}`;
    const progressId = `progress-${timestamp}`;

    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible alert-label-icon rounded-label fade show material-shadow position-relative overflow-hidden mt-2" role="alert" style="min-width: 300px;">
            <!-- Progress Bar at the Top -->
            <div id="${progressId}" class="position-absolute top-0 start-0 bg-${type}" style="height: 4px; width: 0%; transition: width ${duration}ms linear;"></div>

            <!-- Alert Content -->
            <i class="${icon} label-icon"></i><strong>${
        type.charAt(0).toUpperCase() + type.slice(1)
    }</strong> - ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    let container = document.getElementById("alert-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "alert-container";
        container.style.position = "fixed";
        container.style.top = "20px";
        container.style.right = "20px";
        container.style.zIndex = "1055";
        container.style.maxWidth = "350px";
        document.body.appendChild(container);
    }

    container.insertAdjacentHTML("beforeend", alertHTML);

    setTimeout(() => {
        const progressBar = document.getElementById(progressId);
        if (progressBar) {
            progressBar.style.width = "100%";
        }
    }, 50);

    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.remove("show");
            alert.classList.add("fade");
            setTimeout(() => alert.remove(), 500);
        }
    }, duration);
}

function toggleSwitchText() {
    const isActiveCheckbox = document.getElementById("is_active");
    const label = document.getElementById("is_active_label");
    label.textContent = isActiveCheckbox.checked ? "Active" : "Inactive";
}

function startLoader(event) {
    const button = event.currentTarget;
    let modalSelector = button.getAttribute("data-bs-target");

    if (!modalSelector) {
        const nearestModal = button.closest(".modal");
        if (nearestModal) {
            modalSelector = `#${nearestModal.id}`;
        }
    }

    if (!modalSelector) {
        console.error("Modal not found.");
        return;
    }

    const modal = document.querySelector(modalSelector);

    if (!modal) {
        console.error("Modal element not found.");
        return;
    }

    let progressContainer = modal.querySelector(".progress-container");

    if (!progressContainer) {
        progressContainer = document.createElement("div");
        progressContainer.className = "progress-container";
        progressContainer.style.display = "none";
        progressContainer.innerHTML = `
            <div class="progres" style="height: 5px;">
                <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
            </div>`;
        modal.querySelector(".modal-content").prepend(progressContainer);
    }

    const icon = button.querySelector(".label-icon");
    const loader = button.querySelector(".loader");

    if (progressContainer) {
        button.disabled = true;
        if (icon) icon.classList.remove("ri-check-double-line");
        if (loader) loader.style.display = "inline-block";
        progressContainer.style.display = "block";
    } else {
        console.error("Progress container element not found.");
    }
}

function endLoader(event) {
    const button = event.currentTarget;
    let modalSelector = button.getAttribute("data-bs-target");

    if (!modalSelector) {
        const nearestModal = button.closest(".modal");
        if (nearestModal) {
            modalSelector = `#${nearestModal.id}`;
        }
    }

    if (!modalSelector) {
        console.error("Modal not found.");
        return;
    }

    const modal = document.querySelector(modalSelector);

    if (!modal) {
        console.error("Modal element not found.");
        return;
    }

    const progressContainer = modal.querySelector(".progress-container");
    const icon = button.querySelector(".label-icon");
    const loader = button.querySelector(".loader");

    if (progressContainer) {
        button.disabled = false;
        if (icon) icon.classList.add("ri-check-double-line");
        if (loader) loader.style.display = "none";
        progressContainer.style.display = "none";
    } else {
        console.error("Progress container element not found.");
    }
}

function spinStartLoader(event) {
    const button = event.currentTarget;
    const loader = button.querySelector(".loader");
    if (loader) {
        button.disabled = true;
        loader.style.display = "inline-block";
        button.dataset.originalText = button.textContent.trim();
        button.textContent = "";
        button.appendChild(loader);
    } else {
        console.error("Loader element not found.");
    }
}

function spinEndLoader(event) {
    const button = event.currentTarget;
    const loader = button.querySelector(".loader");
    if (loader) {
        button.disabled = false;
        loader.style.display = "none";
        button.textContent = button.dataset.originalText || "Save";
    } else {
        console.error("Loader element not found.");
    }
}

function startSimpleLoader(event) {
    const button = event.currentTarget;
    const icon = button.querySelector(".label-icon");
    const loader = button.querySelector(".loader");

    button.disabled = true;
    if (icon) icon.classList.remove("ri-check-double-line");
    if (loader) loader.style.display = "inline-block";
}

function endSimpleLoader(event) {
    const button = event.currentTarget;
    const icon = button.querySelector(".label-icon");
    const loader = button.querySelector(".loader");

    button.disabled = false;
    if (icon) icon.classList.add("ri-check-double-line");
    if (loader) loader.style.display = "none";
}

$(document).ready(function () {
    $("#company").select2({
        placeholder: "-- Select Company --",
        allowClear: true,
    });
    $("#financial_year").select2({
        placeholder: "-- Select Financial Year --",
        allowClear: true,
    });
    $("#clearCacheBtn").on("click", function () {
        $.ajax({
            url: "clear-all-cache",
            type: "GET",
            success: function (response) {
                showAlert(
                    "success",
                    "ri-checkbox-circle-line",
                    response.message || "Cache cleared successfully!"
                );
            },
            error: function (xhr) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    xhr.responseJSON?.message ||
                        "An error occurred while clearing the cache."
                );
            },
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("empSearchOptions");
    console.log(searchInput);
    const searchDropdown = document.getElementById("search-dropdown");
    const notificationList = document.querySelector(".notification-list");
    const statusFilter = document.getElementById("statusFilter");
    const simpleBar = document.querySelector("[data-simplebar]");
    let debounceTimer;
    let currentPage = 1;
    let searchTerm = "";
    let statusFilterValue = "A";
    let loading = false;
    let hasMore = true;

    const simpleBarInstance = simpleBar
        ? SimpleBar.instances.get(simpleBar)
        : null;
    const scrollElement = simpleBarInstance
        ? simpleBarInstance.getScrollElement()
        : notificationList;

    document.addEventListener("keydown", function (event) {
        if (event.ctrlKey && event.key === "e") {
            event.preventDefault();
            searchInput.focus();
            resetAndFetch(searchInput.value.trim());
            searchDropdown.classList.add("show");
        }
    });
    searchInput.addEventListener("focus", function () {
        resetAndFetch("");
        searchDropdown.classList.add("show");
    });

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            resetAndFetch(this.value.trim());
            searchDropdown.classList.add("show");
        }, 300);
    });

    statusFilter.addEventListener("change", function () {
        statusFilterValue = this.checked ? "A" : "";
        resetAndFetch(searchInput.value.trim());
        searchDropdown.classList.add("show");
    });

    document
        .getElementById("search-close-options")
        .addEventListener("click", function () {
            searchInput.value = "";
            statusFilter.checked = true;
            statusFilterValue = "A";
            searchDropdown.classList.remove("show");
            notificationList.innerHTML = "";
        });

    function resetAndFetch(term) {
        searchTerm = term;
        currentPage = 1;
        hasMore = true;
        notificationList.innerHTML = "";
        fetchEmployees();
    }

    function fetchEmployees() {
        if (loading || !hasMore) return;
        loading = true;

        fetch("employees/search", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({
                search: searchTerm,
                page: currentPage,
                status: statusFilterValue,
            }),
        })
            .then((response) => {
                if (!response.ok)
                    throw new Error("Network response was not ok");
                return response.json();
            })
            .then((data) => {
                const { employees, has_more } = data;
                if (employees.length === 0 && currentPage === 1) {
                    notificationList.innerHTML =
                        '<div class="text-center text-muted py-2">No employees found</div>';
                    hasMore = false;
                } else {
                    appendResults(employees);
                    hasMore = has_more;
                    if (hasMore) currentPage++;
                }
                loading = false;
            })
            .catch((error) => {
                console.error("Error:", error);
                notificationList.innerHTML =
                    '<div class="text-center text-muted py-2">Error loading results</div>';
                loading = false;
            });
    }

    function appendResults(employees) {
        employees.forEach((employee) => {
            const employeeItem = document.createElement("a");
            const { label: statusLabel, className: statusClass } =
                getStatusLabel(employee.EmpStatus);

            employeeItem.href = `employee/${employee.EmployeeID}`;
            employeeItem.className = "dropdown-item notify-item py-2";
            employeeItem.innerHTML = `
                <div class="d-flex">
                    <img src="${employee.image_url}" 
                         class="me-3 rounded-circle avatar-xs" alt="user-pic"
                         onerror="this.src='/custom/no_user.png'">
                    <div class="flex-grow-1">
                        <h6 class="m-0">${employee.EmpCode} - ${
                employee.EmployeeName
            }</h6>
                        <span class="fs-11 mb-0 text-muted">
                            ${employee.grade_name || "N/A"} - ${
                employee.department_name || "N/A"
            }
                        </span>
                        <span style="float: right;" class="fs-11 mb-0 ${statusClass}">${statusLabel}</span>
                    </div>
                </div>
            `;
            notificationList.appendChild(employeeItem);
        });
    }

    function getStatusLabel(code) {
        switch (code) {
            case "A":
                return { label: "Active", className: "text-success" };
            case "D":
                return { label: "Deactive", className: "text-danger" };
            default:
                return { label: "Unknown", className: "text-secondary" };
        }
    }

    let scrollTimeout;
    scrollElement.addEventListener("scroll", function () {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const nearBottom =
                this.scrollTop + this.clientHeight >= this.scrollHeight - 20;
            if (nearBottom && !loading && hasMore) {
                fetchEmployees();
            }
        }, 100);
    });

    document.addEventListener("click", function (event) {
        const appSearch = document.querySelector(".app-search");
        const searchInput = document.querySelector("#empSearchOptions");
        $("#empSearchOptions").val();
        if (appSearch && !appSearch.contains(event.target)) {
            searchDropdown.classList.remove("show");
            notificationList.innerHTML = "";
            if (searchInput) searchInput.value = "";
        }
    });
});
