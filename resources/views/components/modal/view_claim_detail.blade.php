<div class="modal fade" id="claimDetailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="claimDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="progress-container" style="display: none">
                <div class="progres" style="height: 5px;">
                    <div class="indeterminate" style="background-color: var(--vz-primary);"></div>
                </div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title">Claim Detail</h5>
                <select id="claimTypeSelect" class="form-select" style="width: 200px">
                    <option value="">Select Claim Type</option>
                </select>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div id="imageGallery" class="mb-3">
                            <!-- Thumbnails will be inserted here -->
                        </div>
                        <div id="largeImagePreview" style="display: none;">
                            <img src="" alt="Large Preview" class="img-fluid" style="width: 100%; height: 300px; object-fit: contain; cursor: pointer; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div id="claimDetailContent">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>