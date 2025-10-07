$(document).ready(function () {
    let uploadedFiles = [];
    let currentFileIndex = 0;
    let currentZoom = 1;
    let currentRotation = 0;

    $('#claimDetailModal').on('shown.bs.modal', function () {
        loadDefaultFiles();
    });

    function loadDefaultFiles() {
        uploadedFiles = [];
        currentFileIndex = 0;
        displayThumbnails();
        if (uploadedFiles.length > 0) {
            showFile(0);
        } else {
            $('#mainViewer').html('<div class="text-center">No files available.</div>');
        }
    }

    function displayThumbnails() {
        const thumbnailList = $('#thumbnailList');
        thumbnailList.empty();

        if (uploadedFiles.length === 0) {
            thumbnailList.append('<div class="text-center">No files to display.</div>');
            return;
        }

        uploadedFiles.forEach((file, index) => {
            const thumbnail = $('<div>');

            if (file.type.startsWith('image/')) {
                const img = $('<img>')
                    .addClass('thumbnail-item')
                    .attr('data-index', index)
                    .attr('src', file.url)
                    .attr('alt', file.name);

                thumbnail.append(img);
            } else if (file.type === 'application/pdf') {
                const pdfThumb = $('<div>')
                    .addClass('thumbnail-item pdf-thumbnail')
                    .attr('data-index', index)
                    .html('<i class="ri-file-pdf-line"></i>');

                thumbnail.append(pdfThumb);
            }

            thumbnailList.append(thumbnail);
        });

        if (uploadedFiles.length > 0) {
            thumbnailList.find('[data-index="0"]').addClass('active');
        }
    }

    $(document).on('click', '.thumbnail-item', function () {
        const index = parseInt($(this).attr('data-index'));
        showFile(index);
        updateActiveState(index);
    });

    function updateActiveState(index) {
        $('.thumbnail-item').removeClass('active');
        $(`.thumbnail-item[data-index="${index}"]`).addClass('active');
        currentFileIndex = index;
        updateNavigationButtons();
    }

    function showFile(index) {
        if (index >= uploadedFiles.length || index < 0) return;

        const file = uploadedFiles[index];
        const mainViewer = $('#mainViewer');

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

        if (file.type.startsWith('image/')) {
            loadImage(file, mainViewer);
        } else if (file.type === 'application/pdf') {
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

        $('#imageContainer').on('wheel', handleMouseWheel);
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
        const img = $('#mainImage');
        img.css({
            transform: `scale(${currentZoom}) rotate(${currentRotation}deg)`,
            transformOrigin: 'center center'
        });
        $('#zoomDisplay').text(`${Math.round(currentZoom * 100)}%`);
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

    $(document).on('click', '#zoomIn', function () {
        currentZoom = Math.min(currentZoom * 1.2, 5);
        updateImageTransform();
    });

    $(document).on('click', '#zoomOut', function () {
        currentZoom = Math.max(currentZoom / 1.2, 0.5);
        updateImageTransform();
    });

    $(document).on('click', '#zoomReset', function () {
        currentZoom = 1;
        currentRotation = 0;
        updateImageTransform();
    });

    $(document).on('click', '#rotateLeft', function () {
        currentRotation -= 90;
        updateImageTransform();
    });

    $(document).on('click', '#rotateRight', function () {
        currentRotation += 90;
        updateImageTransform();
    });

    $(document).on('click', '#downloadImage, #downloadPDF', function () {
        const file = uploadedFiles[currentFileIndex];
        const link = document.createElement('a');
        link.href = file.url;
        link.download = file.name;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    $(document).on('click', '#openImage, #openPDF', function () {
        const file = uploadedFiles[currentFileIndex];
        window.open(file.url, '_blank');
    });

    $('#prevBtn').on('click', function () {
        if (currentFileIndex > 0) {
            showFile(currentFileIndex - 1);
            updateActiveState(currentFileIndex - 1);
        }
    });

    $('#nextBtn').on('click', function () {
        if (currentFileIndex < uploadedFiles.length - 1) {
            showFile(currentFileIndex + 1);
            updateActiveState(currentFileIndex + 1);
        }
    });

    function updateNavigationButtons() {
        $('#prevBtn').prop('disabled', currentFileIndex === 0);
        $('#nextBtn').prop('disabled', currentFileIndex === uploadedFiles.length - 1);
    }

    function updateFileInfo(file, index) {
        $('#fileName').text(file.name);
        $('#fileType').text(file.type);
        $('#fileIndex').text(`${index + 1} of ${uploadedFiles.length}`);
    }

    $(document).on('keydown', function (e) {
        if ($('#claimDetailModal').hasClass('show')) {
            if (e.key === 'ArrowLeft') {
                $('#prevBtn').click();
            } else if (e.key === 'ArrowRight') {
                $('#nextBtn').click();
            } else if (e.key === '+' || e.key === '=') {
                $('#zoomIn').click();
            } else if (e.key === '-') {
                $('#zoomOut').click();
            } else if (e.key === '0') {
                $('#zoomReset').click();
            }
        }
    });

    $('#saveBtn').on('click', function () {
        const formData = {
            currentFile: uploadedFiles[currentFileIndex]?.name || 'No file selected',
            currentFileIndex: currentFileIndex + 1,
            title: $('#documentTitle').val(),
            date: $('#documentDate').val(),
            category: $('#category').val(),
            priority: $('#priority').val(),
            description: $('#description').val(),
            author: $('#author').val(),
            department: $('#department').val(),
            amount: $('#amount').val(),
            reference: $('#reference').val(),
            tags: $('#tags').val(),
            confidential: $('#confidential').is(':checked'),
            requiresApproval: $('#requiresApproval').is(':checked'),
            notes: $('#notes').val()
        };

        console.log('Form data:', formData);
        alert('Document saved successfully!\nCheck console for form data.');
        $('#claimDetailModal').modal('hide');
    });

    $('#claimDetailModal').on('hidden.bs.modal', function () {
        $('#dataEntryForm')[0].reset();
        currentFileIndex = 0;
        currentZoom = 1;
        currentRotation = 0;
        $('#imageContainer').off('wheel', handleMouseWheel);
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
                        data: response.data
                    });

                    if (id) {
                        $select.val(id).trigger("change");
                    }
                } else {
                    showAlert("warning", "ri-alert-line", response.message ||
                        "No claim types found.");
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
                    "Failed to fetch claim types: " + (xhr.responseText || error)
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
                    uploadedFiles = response.data.uploaded_files.map(file => {
                        const ext = file.file_path.split('.').pop().toLowerCase();
                        let type = 'application/octet-stream';
                        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                            type = 'image/' + ext;
                        } else if (ext === 'pdf') {
                            type = 'application/pdf';
                        }
                        return {
                            url: file.file_url,
                            name: file.file_path,
                            type: type
                        };
                    });
                    displayThumbnails();
                    showFile(0);
                } else {
                    alert('No uploaded files found');
                    uploadedFiles = [];
                    displayThumbnails();
                }
            },
            error: function (xhr) {
                alert(xhr.responseText || 'Failed to fetch files');
                uploadedFiles = [];
                displayThumbnails();
            }
        });
    }

    $(document).on("click", ".copy-icon", function () {
        var expId = $('#expIdValue').text();
        navigator.clipboard.writeText(expId)
            .then(() => {
                var copiedText = $(this).siblings('.copied-text');
                copiedText.fadeIn(200).delay(1000).fadeOut(500);
            })
            .catch(err => console.error('Failed to copy!', err));
    });

    $(document).on("click", ".view-claim", function (e) {
        e.preventDefault();
        var claimId = $(this).data("claim-id");
        var expId = $(this).data("expid");
        $("#claimDetailModal").attr("data-modal-claimid", claimId);
        $("#claimDetailModal").attr("data-modal-expid", expId);
        $("#expIdValue").text(expId);
        claimTypeList(claimId);
        fetchUploadedFiles(expId, claimId);
    });

    $(document).on("change", "#sltClaimTypeList", function () {
        let claimId = $(this).val();
        let expId = $("#claimDetailModal").attr("data-modal-expid");
        if (!claimId || !expId) {
            $("#dataEntryForm").html(
                '<div class="alert alert-warning">Please select a claim type and ensure ExpId is set.</div>'
            );
            return;
        }
        $.ajax({
            url: "claim-detail",
            method: "GET",
            data: {
                claim_id: claimId,
                expid: expId
            },
            success: function (response) {
                if (response.html) {
                    $("#dataEntryForm").html(response.html);
                } else {
                    $("#dataEntryForm").html('<div class="alert alert-danger">No details found.</div>');
                }
            },
            error: function (xhr) {
                $("#dataEntryForm").html(
                    '<div class="alert alert-danger">Error loading claim details.</div>'
                );
                console.error("Claim detail fetch error:", xhr);
            }
        });
    });
});