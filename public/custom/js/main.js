// const { Dropdown } = require("bootstrap");

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
    $(document).on("click", ".viewClaimDetail", function () {
        var claimId = $(this).data("claim-id");
        var expId = $(this).data("expid");

        // Media URLs (images and PDF)
        var mediaUrls = [
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/1863/Img_1863_040825083550_1.jpg",
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/1720/Img_1720_040825205135_1.jpg",
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/100073/Img_100073_040825113109_1.jpg",
            "https://thesoftwarepro.com/wp-content/uploads/2019/12/microsoft-office-pdf-document-600x645.jpg", // PDF placeholder
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/1863/Img_1863_040825083550_1.jpg",
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/1720/Img_1720_040825205135_1.jpg",
            "https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/100073/Img_100073_040825113109_1.jpg",
            "https://thesoftwarepro.com/wp-content/uploads/2019/12/microsoft-office-pdf-document-600x645.jpg", // PDF placeholder
        ];

        // Populate the thumbnail gallery
        var galleryHtml = "";
        mediaUrls.forEach(function (url, index) {
            if (url.includes("pdf-document")) {
                galleryHtml += `
                <div>
                    <a href="https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/1729/Pdf_1729_040825221909_4.pdf" target="_blank">
                        <img src="${url}" alt="PDF Bill ${index + 1}">
                    </a>
                </div>`;
            } else {
                galleryHtml += `<div><img src="${url}" alt="Claim Image ${
                    index + 1
                }"></div>`;
            }
        });
        $("#imageGallery").html(galleryHtml);

        // Initialize large image preview
        $("#largeImagePreview").show();
        $("#largeImagePreview img").attr("src", mediaUrls[0]);

        // Handle thumbnail click to update large image
        $("#imageGallery img, #imageGallery a img").on("click", function (e) {
            e.preventDefault();
            var src = $(this).attr("src");
            if ($(this).parent().is("a")) {
                window.open($(this).parent().attr("href"), "_blank");
            } else {
                $("#largeImagePreview img").attr("src", src);
            }
        });

        // Initialize Viewer.js for large image click
        var viewer = new Viewer(
            document
                .getElementById("largeImagePreview")
                .getElementsByTagName("img")[0],
            {
                filter: function (image) {
                    return !image.src.includes("pdf-document"); // Exclude PDF placeholder
                },
                inline: false,
                navbar: true,
                toolbar: true,
                title: false,
                movable: true,
                zoomable: true,
                rotatable: true,
                scalable: true,
                transition: true,
                fullscreen: true,
                button: true,
                next: true,
                prev: true,
                url: function (image) {
                    return mediaUrls[
                        mediaUrls.indexOf(
                            image.src.replace(
                                "pdf-document",
                                "Img_1863_040825083550_1.jpg"
                            )
                        )
                    ]; // Map back to original image
                },
            }
        );

        // Populate the form
        var formHtml = `
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="row">
                    <div class="col-6">Expense Type: <strong>Postage Courier</strong></div>
                    <div class="col-6 text-end">Year: <strong>2025-2026</strong></div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Sender Name</label>
                        <input type="text" class="form-control" value="Raja S" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sender Address</label>
                        <input type="text" class="form-control" value="" readonly="">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Provider Name</label>
                        <input type="text" class="form-control" value="The Professional Couriers" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Weight Charged</label>
                        <input type="text" class="form-control" value="0.500 Kgs" readonly="">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Docket No.</label>
                        <input type="text" class="form-control" value="DDG565515" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Booked Date</label>
                        <input type="text" class="form-control" value="" readonly="">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Receiver Name</label>
                        <input type="text" class="form-control" value="Selvam Bakery" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Receiver Address</label>
                        <input type="text" class="form-control" value="Pettavaithalai, Trichy" readonly="">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Source City</label>
                        <input type="text" class="form-control" value="Dindigul" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Destination City</label>
                        <input type="text" class="form-control" value="Trichy" readonly="">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" value="90 Rs" readonly="">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Remark</label>
                        <input type="text" class="form-control" value="Postage Courier" readonly="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-success float-end">Submit</button>
                        <button type="button" class="btn btn-info me-2 float-end">Save as Draft</button>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <strong>Remarks:</strong><br>
                <p>Raja S - Last month unclaimed bill kindly update - 04-08-2025</p>
            </div>
        </div>`;
        $("#claimDetailContent").html(formHtml);

        // Perform the AJAX request (optional, if additional data is needed)
        $.ajax({
            url: "claim-detail",
            method: "GET",
            data: {
                claim_id: claimId,
                expid: expId,
            },
            success: function (response) {
                loadClaimTypes(claimId);
                // Additional data can be merged here if needed
            },
            error: function () {
                $("#claimDetailContent").append(
                    '<div class="text-danger mt-3">Failed to load additional data.</div>'
                );
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
