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

function startPageLoader() {
    $("#preloader").attr("style", "");
}

function endPageLoader() {
    $("#preloader").attr("style", "opacity: 0; visibility: hidden;");
}

function loadClaimTypes(selectedClaimId = null) {
    $.ajax({
        url: "get-claim-types",
        method: "GET",
        success: function (data) {
            const $select = $("#claimDetailModal").find("#claimTypeSelect");
            $select.empty().select2({
                data: data,
                placeholder: "Select Claim Type",
                width: "200px",
                allowClear: true,
                dropdownParent: $("#claimDetailModal"),
            });

            if (selectedClaimId) {
                $select.val(selectedClaimId).trigger("change");
            }
        },
        error: function () {
            console.error("Failed to load claim types.");
        },
    });
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
            url: "clear-caches",
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
    $("#companyModal").on("shown.bs.modal", function () {
        $.ajax({
            url: "companies-list",
            method: "GET",
            success: function (response) {
                const companySelect = $("#companySelect");
                companySelect.empty();
                companySelect.append(
                    '<option value="" disabled selected>Select a company</option>'
                );
                response.forEach((company) => {
                    companySelect.append(
                        `<option value="${company.id}">${company.name}</option>`
                    );
                });
            },
            error: function (xhr) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    xhr.responseJSON?.message
                );
            },
        });
    });
    $("#companySelect").on("change", function () {
        const company = $(this).val();
        const fySelect = $("#fySelect");
        fySelect.prop("disabled", true);
        fySelect.empty();
        fySelect.append(
            '<option value="" disabled selected>Select a financial year</option>'
        );
        if (company) {
            $.ajax({
                url: "financial-years.list",
                method: "GET",
                data: { company: company },
                success: function (response) {
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach((year) => {
                            fySelect.append(
                                `<option value="${year.id}">${year.name}</option>`
                            );
                        });
                    } else {
                        fySelect.append(
                            '<option value="" disabled>No financial years available</option>'
                        );
                    }
                    fySelect.prop("disabled", false);
                },
                error: function (xhr) {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        xhr.responseJSON?.message
                    );
                },
            });
        }
    });
    $("#submitSelection").on("click", function () {
        const button = this;
        const companyId = $("#companySelect").val();
        const yearId = $("#fySelect").val();
        if (!companyId || !yearId) {
            showAlert(
                "danger",
                "ri-error-warning-line",
                "Please select both a company and a financial year."
            );
            return;
        }
        $.ajax({
            url: "switch-database",
            method: "POST",
            beforeSend: function () {
                startLoader({ currentTarget: button });
            },
            data: {
                company_id: companyId,
                year_id: yearId,
                _token: $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    showAlert(
                        "success",
                        "ri-checkbox-circle-line",
                        response.message || "Database switched successfully!"
                    );
                } else {
                    showAlert(
                        "danger",
                        "ri-error-warning-line",
                        response.message
                    );
                }
            },
            error: function (xhr) {
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    xhr.responseJSON?.message
                );
            },
            complete: function () {
                endLoader({ currentTarget: button });
            },
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("empSearchOptions");
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
    let activeItemIndex = -1;

    if (!searchInput || !searchDropdown || !notificationList) {
        console.error("Required DOM elements are missing.");
        return;
    }

    const simpleBarInstance =
        (simpleBar && SimpleBar?.instances?.get(simpleBar)) || null;
    const scrollElement = simpleBarInstance
        ? simpleBarInstance.getScrollElement()
        : notificationList;

    if (!scrollElement) {
        console.error("Scroll element could not be determined.");
        return;
    }

    document.addEventListener("keydown", function (event) {
        if (event.ctrlKey && event.key === "e") {
            event.preventDefault();
            searchInput.focus();
            resetAndFetch(searchInput.value.trim());
            searchDropdown.classList.add("show");
        }
    });

    searchInput.addEventListener("keydown", function (event) {
        const items = notificationList.querySelectorAll(".dropdown-item");
        if (items.length === 0) return;

        if (event.key === "ArrowDown") {
            event.preventDefault();
            if (activeItemIndex < items.length - 1) {
                activeItemIndex++;
                updateActiveItem(items);
                scrollToActiveItem(items[activeItemIndex]);
            }
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (activeItemIndex > 0) {
                activeItemIndex--;
                updateActiveItem(items);
                scrollToActiveItem(items[activeItemIndex]);
            }
        } else if (event.key === "Enter" && activeItemIndex >= 0) {
            event.preventDefault();
            const selectedItem = items[activeItemIndex];
            if (selectedItem) {
                window.location.href = selectedItem.href;
            }
        }
    });

    searchInput.addEventListener("focus", function () {
        resetAndFetch("");
        searchDropdown.classList.add("show");
        activeItemIndex = -1;
    });

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            resetAndFetch(this.value.trim());
            searchDropdown.classList.add("show");
            activeItemIndex = -1;
        }, 300);
    });

    statusFilter?.addEventListener("change", function () {
        statusFilterValue = this.checked ? "A" : "";
        resetAndFetch(searchInput.value.trim());
        searchDropdown.classList.add("show");
        activeItemIndex = -1;
    });

    document
        .getElementById("search-close-options")
        ?.addEventListener("click", function () {
            searchInput.value = "";
            if (statusFilter) {
                statusFilter.checked = true;
                statusFilterValue = "A";
            }
            searchDropdown.classList.remove("show");
            notificationList.innerHTML = "";
            activeItemIndex = -1;
        });

    function resetAndFetch(term) {
        searchTerm = term;
        currentPage = 1;
        hasMore = true;
        notificationList.innerHTML = "";
        activeItemIndex = -1;
        fetchEmployees();
    }

    function fetchEmployees() {
        if (loading || !hasMore) return;
        loading = true;

        fetch("employees/search", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
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
                return {
                    label: "Active",
                    className: "text-success",
                };
            case "D":
                return {
                    label: "Deactive",
                    className: "text-danger",
                };
            default:
                return {
                    label: "Unknown",
                    className: "text-secondary",
                };
        }
    }

    function updateActiveItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle("active", index === activeItemIndex);
        });
    }

    function scrollToActiveItem(activeItem) {
        if (!activeItem || !simpleBarInstance) return;
        const scrollContainer = scrollElement;
        const itemTop = activeItem.offsetTop;
        const itemBottom = itemTop + activeItem.offsetHeight;
        const containerTop = scrollContainer.scrollTop;
        const containerBottom = containerTop + scrollContainer.clientHeight;

        if (itemTop < containerTop) {
            scrollContainer.scrollTop = itemTop;
        } else if (itemBottom > containerBottom) {
            scrollContainer.scrollTop =
                itemBottom - scrollContainer.clientHeight;
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
        if (appSearch && !appSearch.contains(event.target)) {
            searchDropdown.classList.remove("show");
            notificationList.innerHTML = "";
            searchInput.value = "";
            activeItemIndex = -1;
        }
    });
});

const companyData = {
    companyA: ["2023-24", "2024-25"],
    companyB: ["2022-23", "2023-24", "2024-25"],
    companyC: ["2021-22", "2022-23"],
};

const tooltipTriggerList = document.querySelectorAll(
    '[data-bs-toggle="tooltip"]'
);
const tooltipList = [...tooltipTriggerList].map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
);

const companySelect = document.getElementById("companySelect");
const fySelect = document.getElementById("fySelect");
const submitButton = document.getElementById("submitSelection");

companySelect.addEventListener("change", function () {
    const selectedCompany = this.value;
    fySelect.innerHTML =
        '<option value="" disabled selected>Select a financial year</option>';
    fySelect.disabled = true;

    if (selectedCompany && companyData[selectedCompany]) {
        companyData[selectedCompany].forEach((fy) => {
            const option = document.createElement("option");
            option.value = fy;
            option.textContent = fy;
            fySelect.appendChild(option);
        });
        fySelect.disabled = false;
    }
});

submitButton.addEventListener("click", function () {
    const selectedCompany = companySelect.value;
    const selectedFY = fySelect.value;

    if (selectedCompany && selectedFY) {
        console.log(
            `Selected Company: ${selectedCompany}, Financial Year: ${selectedFY}`
        );

        bootstrap.Modal.getInstance(
            document.getElementById("companyModal")
        ).hide();
    } else {
        alert("Please select both a company and a financial year.");
    }
});
