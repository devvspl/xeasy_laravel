    <div class="modal fade" id="claimDetailModal" data-bs-backdrop="static" data-modal-expid="" data-modal-claimid="" data-modal-cgid=""
        tabindex="-1" aria-labelledby="claimDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center justify-content-between">
                    <h5 class="modal-title" id="claimDetailModalLabel">
                        <i class="ri-file-text-line me-2"></i>Document Viewer & Data Entry
                    </h5>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <span id="expIdDisplay" class="fw-semibold d-flex align-items-center" style="gap: 5px;">
                            Exp ID: <span id="expIdValue"></span>
                            <span class="copy-wrapper"
                                style="position: relative; display: flex; align-items: center; gap: 5px;">
                                <i class="ri-file-copy-line copy-icon" style="cursor: pointer;"></i>
                                <span class="copied-text" style="display:none; color: green; font-size: 0.85rem;">Copied!</span>
                            </span>
                        </span>
                        <select name="" style="width: 250px;" class="form-control" id="sltClaimTypeList">
                        </select>
                    </div>
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
                            <form id="dataEntryForm">
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="prevBtn">
                        <i class="ri-arrow-left-line me-1"></i>Previous
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="nextBtn">
                        Next<i class="ri-arrow-right-line ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="saveBtn">
                        <i class="ri-save-line me-1"></i>Save
                    </button>
                </div>
            </div>
        </div>
    </div>