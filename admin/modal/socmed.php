<!-- ================================
     SOCIAL MEDIA MODAL
================================ -->
<div class="modal" id="socmed-modal">
    <div class="modal-content socmed-modal">

        <!-- HEADER -->
        <div class="modal-header">
            <h2 id="socmed-modal-title">Social Media Activity</h2>
            <button class="modal-close" id="socmed-modal-close">&times;</button>
        </div>

        <!-- BODY -->
        <form id="socmed-form" class="modal-body">

            <!-- ACTIVITY NAME -->
            <div class="form-group full">
                <label>Activity / Post Title <span class="req">*</span></label>
                <input
                    type="text"
                    id="activity_name"
                    name="activity_name"
                    required
                    placeholder="e.g. Kempen Galakan Membaca di Facebook"
                >
            </div>

            <!-- PROGRAM TYPE -->
            <div class="form-group full">
                <label>Program Type <span class="req">*</span></label>
                <select id="program_type_id" name="program_type_id" required>
                    <option value="" disabled selected>Please Select</option>
                </select>
            </div>

            <!-- PLATFORM + DATE -->
            <div class="form-grid">

                <div class="form-group">
                    <label>Platform <span class="req">*</span></label>
                    <select id="platform_id" name="platform_id" required>
                        <option value="" disabled selected>Please Select</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Posting Date <span class="req">*</span></label>
                    <input
                        type="date"
                        id="activity_date"
                        name="activity_date"
                        required
                    >
                </div>

            </div>

            <!-- METRICS -->
            <div class="form-grid">

                <div class="form-group">
                    <label>Reach</label>
                    <input
                        type="number"
                        id="reach_estimate"
                        name="reach_estimate"
                        min="0"
                        placeholder="e.g. 1200"
                    >
                    <small class="hint">Approximate audience reached</small>
                </div>

                <div class="form-group">
                    <label>Engagement</label>
                    <input
                        type="number"
                        id="engagement_estimate"
                        name="engagement_estimate"
                        min="0"
                        placeholder="e.g. 85"
                    >
                    <small class="hint">Likes, comments, shares, etc.</small>
                </div>

            </div>

            <!-- URL -->
            <div class="form-group full">
                <label>Post URL</label>
                <input
                    type="url"
                    id="document_url"
                    name="document_url"
                    placeholder="https://facebook.com/..."
                >
                <small class="hint">Optional - for reference only</small>
            </div>

            <!-- REMARKS -->
            <div class="form-group full">
                <label>Remarks</label>
                <textarea
                    id="activity_details"
                    name="activity_details"
                    rows="3"
                    placeholder="Internal notes (optional)"
                ></textarea>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="socmed-cancel-btn">
                    Cancel
                </button>
                <button type="submit" class="btn-primary">
                    Save Activity
                </button>
            </div>

        </form>
    </div>
</div>

