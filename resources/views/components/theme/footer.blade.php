<x-modal.view_claim_detail />
<button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
   <i class="ri-arrow-up-line"></i>
</button>
<div id="preloader">
   <div id="status">
      <div class="spinner-border text-white avatar-sm" role="status">
         <span class="visually-hidden"></span>
      </div>
      <span class="text-white mt-2">Loading...</span>
   </div>
</div>
<div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header justify-content-between">
            <h5 class="modal-title" id="companyModalLabel">
               Current - <span>{{ session('company_name') }}</span>
               <span>({{ session('year_value') }})</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>

         <div class="modal-body">
            <form id="companyForm">
               <div class="mb-3">
                  <label for="companySelect" class="form-label">Company</label>
                  <select class="form-select" id="companySelect" required>
                     <option value="" disabled selected>Select a company</option>
                  </select>
               </div>
               <div class="mb-3">
                  <label for="fySelect" class="form-label">Financial Year</label>
                  <select class="form-select" id="fySelect" disabled required>
                     <option value="" disabled selected>Select a financial year</option>
                  </select>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-primary btn-label waves-effect waves-light rounded-pill"
               id="submitSelection">
               <i class="ri-check-double-line label-icon align-middle rounded-pill fs-16 me-2">
                  <span class="loader" style="display: none;"></span>
               </i>
               Switch
            </button>
         </div>
      </div>
   </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/simplebar/simplebar.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/node-waves/waves.min.js"></script>
<script src="{{ URL::to('/') }}/assets/libs/feather-icons/feather.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="{{ URL::to('/') }}/custom/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.js"></script>
<script>
   document.addEventListener("DOMContentLoaded", function () {
      let viewer;
      const gallery = document.getElementById('gallery');

      // Re-init viewer only when modal is opened
      const modalEl = document.getElementById('claimDetailModal');
      modalEl.addEventListener('shown.bs.modal', function () {
         if (viewer) {
            viewer.destroy(); // destroy old instance
         }
         viewer = new Viewer(gallery, {
            inline: true,
            toolbar: true,
            navbar: true,
            title: false,
         });
      });
   });
</script>
@stack('scripts')
<script src="{{ URL::to('/') }}/assets/js/app.js"></script>
    <script>
        $(document).ready(function () {
            const defaultFiles = [
                {
                    url: 'https://s3.ap-south-1.amazonaws.com/developerinvnr.bkt/Expense/7/2006/Img_2006_240925141433_1.jpg',
                    name: 'Expense_Receipt_2006.jpg',
                    type: 'image/jpeg'
                },
                {
                    url: 'https://gate2024.iisc.ac.in/wp-content/uploads/2023/07/cs.pdf',
                    name: 'GATE_CS_Syllabus.pdf',
                    type: 'application/pdf'
                }
            ];

            let currentFileIndex = 0;

            $('#claimDetailModal').on('shown.bs.modal', function () {
                loadDefaultFiles();
            });

            function loadDefaultFiles() {
                displayThumbnails();
                showFile(0);
            }

            function displayThumbnails() {
                const thumbnailList = $('#thumbnailList');
                thumbnailList.empty();

                defaultFiles.forEach((file, index) => {
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

                thumbnailList.find('[data-index="0"]').addClass('active');
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
                if (index >= defaultFiles.length || index < 0) return;

                const file = defaultFiles[index];
                const mainViewer = $('#mainViewer');

                mainViewer.html(`
                    <div class="loading-spinner">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                updateFileInfo(file, index);
                loadFileInIframe(file, mainViewer);
            }

            function loadFileInIframe(file, container) {
                container.html(`
                    <div style="width:100%;height:100%;padding:0px;">
                        <iframe src="${file.url}" border="0" class="${file.type.startsWith('image/') ? 'image-viewer' : 'pdf-viewer'}"></iframe>
                    </div>
                    <div class="external-controls">
                        <button class="external-btn" data-url="${file.url}" title="Open in New Tab">
                            <i class="ri-external-link-line"></i>
                        </button>
                    </div>
                `);
            }

            $(document).on('click', '.external-btn', function () {
                const url = $(this).data('url');
                window.open(url, '_blank');
            });

            $('#prevBtn').on('click', function () {
                if (currentFileIndex > 0) {
                    showFile(currentFileIndex - 1);
                    updateActiveState(currentFileIndex - 1);
                }
            });

            $('#nextBtn').on('click', function () {
                if (currentFileIndex < defaultFiles.length - 1) {
                    showFile(currentFileIndex + 1);
                    updateActiveState(currentFileIndex + 1);
                }
            });

            function updateNavigationButtons() {
                $('#prevBtn').prop('disabled', currentFileIndex === 0);
                $('#nextBtn').prop('disabled', currentFileIndex === defaultFiles.length - 1);
            }

            function updateFileInfo(file, index) {
                $('#fileName').text(file.name);
                $('#fileType').text(file.type);
                $('#fileIndex').text(`${index + 1} of ${defaultFiles.length}`);
            }

            $(document).on('keydown', function (e) {
                if ($('#claimDetailModal').hasClass('show')) {
                    if (e.key === 'ArrowLeft') {
                        $('#prevBtn').click();
                    } else if (e.key === 'ArrowRight') {
                        $('#nextBtn').click();
                    }
                }
            });

            $('#saveBtn').on('click', function () {
                const formData = {
                    currentFile: defaultFiles[currentFileIndex].name,
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
            });
        });
    </script>
</body>

</html>