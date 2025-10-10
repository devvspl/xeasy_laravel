$(document).ready(function () {
    let uploadedFiles = [];
    let currentFileIndex = 0;
    let currentZoom = 1;
    let currentRotation = 0;

    $("#claimDetailModal").on("shown.bs.modal", function () {
        loadDefaultFiles();
    });

    function loadDefaultFiles() {
        uploadedFiles = [];
        currentFileIndex = 0;
        displayThumbnails();
        if (uploadedFiles.length > 0) {
            showFile(0);
        } else {
            $("#mainViewer").html(
                '<div class="text-center">No files available.</div>'
            );
        }
    }

    function displayThumbnails() {
        const thumbnailList = $("#thumbnailList");
        thumbnailList.empty();

        if (uploadedFiles.length === 0) {
            thumbnailList.append(
                '<div class="text-center">No files to display.</div>'
            );
            return;
        }

        const shortCodeCounts = {};

        uploadedFiles.forEach((file) => {
            const shortCode = file.short_code || "Document";
            shortCodeCounts[shortCode] = (shortCodeCounts[shortCode] || 0) + 1;
        });

        const shortCodeIndices = {};
        uploadedFiles.forEach((file, index) => {
            let displayName = file.short_code || "Document";
            if (shortCodeCounts[displayName] > 1) {
                shortCodeIndices[displayName] =
                    (shortCodeIndices[displayName] || 0) + 1;
                displayName = `${displayName} (${shortCodeIndices[displayName]})`;
            }

            const thumbnail = $("<div>").addClass("thumbnail-container");

            if (file.type.startsWith("image/")) {
                const img = $("<img>")
                    .addClass("thumbnail-item")
                    .attr("data-index", index)
                    .attr("src", file.url)
                    .attr("alt", file.name);

                thumbnail.append(img);
                thumbnail.append(
                    $("<div>").addClass("thumbnail-label").text(displayName)
                );
            } else if (file.type === "application/pdf") {
                const pdfThumb = $("<div>")
                    .addClass("thumbnail-item pdf-thumbnail")
                    .attr("data-index", index)
                    .html('<i class="ri-file-pdf-line"></i>');

                thumbnail.append(pdfThumb);
                thumbnail.append(
                    $("<div>").addClass("thumbnail-label").text(displayName)
                );
            }

            thumbnailList.append(thumbnail);
        });

        if (uploadedFiles.length > 0) {
            thumbnailList.find('[data-index="0"]').addClass("active");
        }
    }

    $(document).on("click", ".thumbnail-item", function () {
        const index = parseInt($(this).attr("data-index"));
        showFile(index);
        updateActiveState(index);
    });

    function updateActiveState(index) {
        $(".thumbnail-item").removeClass("active");
        $(`.thumbnail-item[data-index="${index}"]`).addClass("active");
        currentFileIndex = index;
        updateNavigationButtons();
    }

    function showFile(index) {
        if (index >= uploadedFiles.length || index < 0) return;

        const file = uploadedFiles[index];
        const mainViewer = $("#mainViewer");

        mainViewer.html(`
            <div class="loading-spinner">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        updateFileInfo(file, index);

        currentZoom = 1;
        currentRotation = 0;

        if (file.type.startsWith("image/")) {
            loadImage(file, mainViewer);
        } else if (file.type === "application/pdf") {
            loadPDF(file, mainViewer);
        }
    }

    function loadImage(file, container) {
        container.html(`
            <div class="image-container" id="imageContainer">
                <img src="${file.url}" id="mainImage" alt="${file.name}">
            </div>
            <div class="viewer-controls">
                <button class="control-btn" id="zoomOut" title="Zoom Out">
                    <i class="ri-zoom-out-line"></i>
                </button>
                <div class="zoom-display" id="zoomDisplay">100%</div>
                <button class="control-btn" id="zoomIn" title="Zoom In">
                    <i class="ri-zoom-in-line"></i>
                </button>
                <button class="control-btn" id="zoomReset" title="Reset Zoom">
                    <i class="ri-focus-line"></i>
                </button>
                <div class="control-divider"></div>
                <button class="control-btn" id="rotateLeft" title="Rotate Left">
                    <i class="ri-anticlockwise-line"></i>
                </button>
                <button class="control-btn" id="rotateRight" title="Rotate Right">
                    <i class="ri-clockwise-line"></i>
                </button>
                <div class="control-divider"></div>
                <button class="control-btn" id="downloadImage" title="Download">
                    <i class="ri-download-line"></i>
                </button>
                <button class="control-btn" id="openImage" title="Open in New Tab">
                    <i class="ri-external-link-line"></i>
                </button>
            </div>
        `);

        updateImageTransform();

        $("#imageContainer").on("wheel", handleMouseWheel);
    }

    function loadPDF(file, container) {
        container.html(`
            <div style="width:100%;height:100%;padding:0px;">
                <iframe src="${file.url}" border="0" class="pdf-viewer"></iframe>
            </div>
            <div class="viewer-controls">
                <button class="control-btn" id="downloadPDF" title="Download">
                    <i class="ri-download-line"></i>
                </button>
                <button class="control-btn" id="openPDF" title="Open in New Tab">
                    <i class="ri-external-link-line"></i>
                </button>
            </div>
        `);
    }

    function updateImageTransform() {
        const img = $("#mainImage");
        img.css({
            transform: `scale(${currentZoom}) rotate(${currentRotation}deg)`,
            transformOrigin: "center center",
        });
        $("#zoomDisplay").text(`${Math.round(currentZoom * 100)}%`);
    }

    function handleMouseWheel(event) {
        event.preventDefault();
        const delta = event.originalEvent.deltaY;
        if (delta > 0) {
            currentZoom = Math.max(currentZoom / 1.1, 0.5);
        } else {
            currentZoom = Math.min(currentZoom * 1.1, 5);
        }
        updateImageTransform();
    }

    $(document).on("click", "#zoomIn", function () {
        currentZoom = Math.min(currentZoom * 1.2, 5);
        updateImageTransform();
    });

    $(document).on("click", "#zoomOut", function () {
        currentZoom = Math.max(currentZoom / 1.2, 0.5);
        updateImageTransform();
    });

    $(document).on("click", "#zoomReset", function () {
        currentZoom = 1;
        currentRotation = 0;
        updateImageTransform();
    });

    $(document).on("click", "#rotateLeft", function () {
        currentRotation -= 90;
        updateImageTransform();
    });

    $(document).on("click", "#rotateRight", function () {
        currentRotation += 90;
        updateImageTransform();
    });

    $(document).on("click", "#downloadImage, #downloadPDF", function () {
        const file = uploadedFiles[currentFileIndex];
        const link = document.createElement("a");
        link.href = file.url;
        link.download = file.name;
        link.target = "_blank";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    $(document).on("click", "#openImage, #openPDF", function () {
        const file = uploadedFiles[currentFileIndex];
        window.open(file.url, "_blank");
    });

    $("#prevBtn").on("click", function () {
        if (currentFileIndex > 0) {
            showFile(currentFileIndex - 1);
            updateActiveState(currentFileIndex - 1);
        }
    });

    $("#nextBtn").on("click", function () {
        if (currentFileIndex < uploadedFiles.length - 1) {
            showFile(currentFileIndex + 1);
            updateActiveState(currentFileIndex + 1);
        }
    });

    function updateNavigationButtons() {
        $("#prevBtn").prop("disabled", currentFileIndex === 0);
        $("#nextBtn").prop(
            "disabled",
            currentFileIndex === uploadedFiles.length - 1
        );
    }

    function updateFileInfo(file, index) {
        $("#fileName").text(file.name);
        $("#fileType").text(file.type);
        $("#fileIndex").text(`${index + 1} of ${uploadedFiles.length}`);
    }

    $(document).on("keydown", function (e) {
        if ($("#claimDetailModal").hasClass("show")) {
            if (e.key === "ArrowLeft") {
                $("#prevBtn").click();
            } else if (e.key === "ArrowRight") {
                $("#nextBtn").click();
            } else if (e.key === "+" || e.key === "=") {
                $("#zoomIn").click();
            } else if (e.key === "-") {
                $("#zoomOut").click();
            } else if (e.key === "0") {
                $("#zoomReset").click();
            }
        }
    });

    function validateClaimForm(claimid, cgid, expid) {
        let isValid = true;
        let errorMessages = [];
        if (cgid == 7) {
            $(".expense-table tbody tr").each(function () {
                const row = $(this);
                const headName = row.find("td:first").text().trim();
                if (!headName || headName === "Total:") return;

                const claimAmt = parseFloat(
                    row.find(".claim").val().replace(/,/g, "") || 0
                );

                const fileList = [];
                row.find(".file-list .file-name").each(function () {
                    const fileName = $(this).data("file");
                    const isRemoved =
                        $(this).parent().attr("data-remove") == "1";
                    const isPreloaded = $(this).data("preloaded") == 1;
                    if (!isRemoved)
                        fileList.push({
                            file_name: fileName,
                            preloaded: isPreloaded ? 1 : 0,
                        });
                });

                let rowValid = true;
                if (fileList.length > 0 && claimAmt <= 0) {
                    rowValid = false;
                    errorMessages.push(
                        `Claim amount must be > 0 for "${headName}" because file is selected.`
                    );
                }
                if (claimAmt > 0 && fileList.length === 0) {
                    rowValid = false;
                    errorMessages.push(
                        `At least one file is required for "${headName}" because claim amount is > 0.`
                    );
                }

                row.css("background-color", rowValid ? "" : "#f8d7da");
                if (!rowValid) isValid = false;
            });
        } else if (cgid == 1 && claimid == 1) {
            const formFields = [
                {
                    id: "billing_person",
                    name: "Billing Person",
                    required: true,
                },
                {
                    id: "billing_address",
                    name: "Billing Address",
                    required: true,
                },
                {
                    id: "city_category",
                    name: "City - Category",
                    required: true,
                },
                {
                    id: "hotel_name",
                    name: "Hotel",
                    required: true,
                    isSelect2: true,
                },
                { id: "hotel_contact", name: "Hotel Contact", required: true },
                { id: "bill_date", name: "Bill Date", required: true },
                { id: "bill_no", name: "Bill No", required: true },
                {
                    id: "total_amount",
                    name: "Total Amount",
                    required: true,
                    isNumber: true,
                },
            ];

            formFields.forEach((f) => {
                let value = "";
                if (f.isSelect2) {
                    value = $(`#${f.id}`).val();
                } else {
                    value = $(`#${f.id}`).val()?.trim();
                }

                const valid = !f.required || (value && value !== "");
                if (!valid) {
                    $(`#${f.id}`).css("border", "1px solid red");
                    errorMessages.push(`${f.name} is required.`);
                    isValid = false;
                } else {
                    $(`#${f.id}`).css("border", "");
                }

                if (f.isNumber && value && isNaN(parseFloat(value))) {
                    $(`#${f.id}`).css("border", "1px solid red");
                    errorMessages.push(`${f.name} must be a valid number.`);
                    isValid = false;
                }
            });
        }
        return { isValid, errorMessages };
    }

    $("#saveBtn").on("click", function () {
        const claimid = $("#claimDetailModal").attr("data-modal-claimid");
        const cgid = $(".modal-ncgid").attr("data-modal-ncgid");
        const expid = $("#claimDetailModal").attr("data-modal-expid");
        const { isValid, errorMessages } = validateClaimForm(
            claimid,
            cgid,
            expid
        );
        if (!isValid) {
            showAlert(
                "danger",
                "ri-error-warning-line",
                errorMessages.join("<br>")
            );
            return;
        }
        const formData = {
            claimid: claimid,
            cgid: cgid,
            expid: expid,
            expenses: [],
            formData: $("#dataEntryForm").serializeArray(),
        };
        console.log("Validated data ready to save:", formData);
        alert("Data validated and ready to save! Check console.");
    });

    $("#claimDetailModal").on("hidden.bs.modal", function () {
        $("#dataEntryForm")[0].reset();
        currentFileIndex = 0;
        currentZoom = 1;
        currentRotation = 0;
        $("#imageContainer").off("wheel", handleMouseWheel);
    });

    function claimTypeList(id = null) {
        $.ajax({
            url: "get-claim-types",
            method: "GET",
            contentType: "application/json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success && response.data) {
                    let $select = $("#sltClaimTypeList");
                    $select.empty();

                    $select.select2({
                        dropdownParent: $("#claimDetailModal"),
                        width: "250px",
                        placeholder: "Select Document Type",
                        allowClear: true,
                        data: response.data,
                    });

                    if (id) {
                        $select.val(id).trigger("change");
                    }
                } else {
                    showAlert(
                        "warning",
                        "ri-alert-line",
                        response.message || "No claim types found."
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error("Fetch error:", {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    response: xhr.response,
                });
                showAlert(
                    "danger",
                    "ri-error-warning-line",
                    "Failed to fetch claim types: " +
                        (xhr.responseText || error)
                );
            },
        });
    }

    function fetchUploadedFiles(expid, claim_id) {
        $.ajax({
            url: `/get-uploaded-files/${expid}/${claim_id}`,
            type: "GET",
            dataType: "json",
            success: function (response) {
                if (response.data && response.data.uploaded_files) {
                    uploadedFiles = [];
                    if (Array.isArray(response.data.uploaded_files)) {
                        uploadedFiles = response.data.uploaded_files.map(
                            (file) => {
                                const ext = file.file_path
                                    .split(".")
                                    .pop()
                                    .toLowerCase();
                                let type = "application/octet-stream";
                                if (
                                    [
                                        "jpg",
                                        "jpeg",
                                        "png",
                                        "gif",
                                        "bmp",
                                        "webp",
                                    ].includes(ext)
                                ) {
                                    type = "image/" + ext;
                                } else if (ext === "pdf") {
                                    type = "application/pdf";
                                }

                                const shortCode =
                                    file.file_path.split("_")[0] || "Document";
                                return {
                                    url: file.file_url,
                                    name: file.file_path,
                                    type: type,
                                    short_code: shortCode,
                                };
                            }
                        );
                    } else {
                        Object.keys(response.data.uploaded_files).forEach(
                            (key) => {
                                const category =
                                    response.data.uploaded_files[key];
                                const shortCode =
                                    category.short_code || "Document";
                                category.files.forEach((file) => {
                                    const ext = file.file_path
                                        .split(".")
                                        .pop()
                                        .toLowerCase();
                                    let type = "application/octet-stream";
                                    if (
                                        [
                                            "jpg",
                                            "jpeg",
                                            "png",
                                            "gif",
                                            "bmp",
                                            "webp",
                                        ].includes(ext)
                                    ) {
                                        type = "image/" + ext;
                                    } else if (ext === "pdf") {
                                        type = "application/pdf";
                                    }
                                    uploadedFiles.push({
                                        url: file.file_url,
                                        name: file.file_path,
                                        type: type,
                                        short_code: shortCode,
                                    });
                                });
                            }
                        );
                    }
                    displayThumbnails();
                    showFile(0);
                } else {
                    alert("No uploaded files found");
                    uploadedFiles = [];
                    displayThumbnails();
                }
            },
            error: function (xhr) {
                alert(xhr.responseText || "Failed to fetch files");
                uploadedFiles = [];
                displayThumbnails();
            },
        });
    }

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Copy functionality
    $(document).on("click", ".copy-icon", function () {
        var expId = $("#expIdValue").text();
        navigator.clipboard
            .writeText(expId)
            .then(() => {
                var copiedText = $(this).siblings(".copied-text");
                copiedText.fadeIn(200).delay(1000).fadeOut(500);
            })
            .catch((err) => console.error("Failed to copy!", err));
    });

    $(document).on("click", ".view-claim", function (e) {
        e.preventDefault();
        var claimid = $(this).data("claim-id");
        var expId = $(this).data("expid");
        var cgId = $(this).data("cgid");
        $("#claimDetailModal").attr("data-modal-claimid", claimid);
        $("#claimDetailModal").attr("data-modal-expid", expId);
        $("#claimDetailModal").attr("data-modal-cgid", cgId);
        $("#expIdValue").text(expId);
        claimTypeList(claimid);
        fetchUploadedFiles(expId, claimid);
    });

    $(document).on("change", "#sltClaimTypeList", function () {
        let claimid = $(this).val();
        let expId = $("#claimDetailModal").attr("data-modal-expid");
        if (!claimid || !expId) {
            $("#dataEntryForm").html(
                '<div class="alert alert-warning">Please select a claim type and ensure ExpId is set.</div>'
            );
            return;
        }
        $.ajax({
            url: "claim-detail",
            method: "GET",
            data: {
                claim_id: claimid,
                expid: expId,
            },
            success: function (response) {
                if (response.html) {
                    $("#dataEntryForm").html(response.html);
                } else {
                    $("#dataEntryForm").html(
                        '<div class="alert alert-danger">No details found.</div>'
                    );
                }
            },
            error: function (xhr) {
                $("#dataEntryForm").html(
                    '<div class="alert alert-danger">Error loading claim details.</div>'
                );
                console.error("Claim detail fetch error:", xhr);
            },
        });
    });
});
