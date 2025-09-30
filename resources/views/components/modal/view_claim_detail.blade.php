    <div class="modal fade" id="claimDetailModal" tabindex="-1" aria-labelledby="claimDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="claimDetailModalLabel">
                        <i class="ri-file-text-line me-2"></i>Document Viewer & Data Entry
                    </h5>
                    <select name="" style="width: 250px;" class="form-control" id="">
                        <option value="">Select Document Type</option>
                        <option value="invoices">2/4 Wheeler</option>
                        <option value="reports">Meals</option>
                        <option value="contracts">Lodging</option>
                        <option value="academic">Air Fire</option>
                        <option value="others">Bus Fire</option>
                    </select>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-5 viewer-section p-2">
                            <div class="file-info small d-flex justify-content-between flex-wrap">
                                <span><strong>File:</strong> <span id="fileName">Loading...</span></span>
                                <span><strong>Type:</strong> <span id="fileType">-</span></span>
                                <span><strong>#</strong> <span id="fileIndex">1/2</span></span>
                            </div>

                            <div class="mb-3">
                                <small><label class="form-label small fw-bold">Documents:</label></small>
                                <div class="thumbnail-list" id="thumbnailList">
                                    <div class="loading-spinner">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="main-viewer" id="mainViewer">
                                <div class="loading-spinner">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7 form-section p-3">
                            <h6 class="mb-3">
                                <i class="ri-edit-line me-2"></i>Data Entry Form
                            </h6>

                            <form id="dataEntryForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="documentTitle" class="form-label">Document Title</label>
                                        <input type="text" class="form-control" id="documentTitle"
                                            placeholder="Enter title">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="documentDate" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="documentDate">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category">
                                            <option value="">Select category</option>
                                            <option value="expense">Expense Report</option>
                                            <option value="invoice">Invoice</option>
                                            <option value="contract">Contract</option>
                                            <option value="report">Report</option>
                                            <option value="academic">Academic Document</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="priority" class="form-label">Priority</label>
                                        <select class="form-select" id="priority">
                                            <option value="">Select priority</option>
                                            <option value="high">High</option>
                                            <option value="medium">Medium</option>
                                            <option value="low">Low</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" rows="4"
                                        placeholder="Enter description"></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="author" class="form-label">Author</label>
                                        <input type="text" class="form-control" id="author"
                                            placeholder="Enter author name">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department"
                                            placeholder="Enter department">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="amount" class="form-label">Amount (if applicable)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" id="amount" placeholder="0.00"
                                                step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="reference" class="form-label">Reference Number</label>
                                        <input type="text" class="form-control" id="reference"
                                            placeholder="Enter reference">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="tags" class="form-label">Tags</label>
                                    <input type="text" class="form-control" id="tags"
                                        placeholder="Enter tags separated by commas">
                                    <div class="form-text">e.g., finance, urgent, review</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="confidential">
                                            <label class="form-check-label" for="confidential">
                                                Mark as Confidential
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="requiresApproval">
                                            <label class="form-check-label" for="requiresApproval">
                                                Requires Approval
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" rows="3"
                                        placeholder="Any additional notes or comments"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="prevBtn">
                        <i class="ri-arrow-left-line me-2"></i>Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Next<i class="ri-arrow-right-line ms-2"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="saveBtn">
                        <i class="ri-save-line me-2"></i>Save Document
                    </button>
                </div>
            </div>
        </div>
    </div>