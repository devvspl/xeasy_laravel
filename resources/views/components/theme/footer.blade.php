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
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="companyModalLabel">Select Company and Financial Year</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <form id="companyForm">
               <div class="mb-3">
                  <label for="companySelect" class="form-label">Company</label>
                  <select class="form-select" id="companySelect" required>
                     <option value="" disabled selected>Select a company</option>
                     <option value="companyA">Company A</option>
                     <option value="companyB">Company B</option>
                     <option value="companyC">Company C</option>
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
            <button type="button" class="btn btn-primary" id="submitSelection">Swatch</button>
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
@stack('scripts')
<script src="{{ URL::to('/') }}/assets/js/app.js"></script>
</body>

</html>