<!-- =========================
     ADD / EDIT MODAL
========================= -->
<!-- =======================================
     CREATE / EDIT PROGRAM MODAL
======================================= -->
<div id="program-modal" class="modal">
    <div class="modal-content wide-modal">

        <div class="modal-header">
            <h2 id="program-modal-title">Create Program</h2>
                        <button class="modal-close" id="program-modal-close">&times;</button>
        </div>

        <form id="program-form" class="data-form form-grid">

            <!-- Program Details -->
            <div class="form-group hidden" id="reject-notes-box">
                <label>Notes</label>
                <textarea id="note_text" rows="3" readonly></textarea>
            </div>
            <!-- Row: Library Type | Parent Library | Program Type -->
            <input type="hidden" id="library_type_id" value="<?php echo $_SESSION['library_type_id']; ?>" readonly>
            <input type="hidden" id="parent_library_id" value="<?php echo $_SESSION['library_parent_id']; ?>" readonly>
            <input type="hidden" id="program_type_id" value="<?php echo $program_id; ?>" readonly>

            <!-- Program Name (full width) -->
            <div class="form-group full">
                <label>Program Name</label>
                <input type="text" id="program_name" required>
            </div>

            <!-- Row: Start Date | End Date -->
            <div class="form-group">
                <label>Start Date & Time</label>
                <input type="datetime-local" id="program_start" required>
            </div>

            <div class="form-group">
                <label>End Date & Time</label>
                <input type="datetime-local" id="program_end" required>
            </div>

            <!-- Row: Mode | Platform -->
            <div class="form-group">
                <label>Program Mode</label>
                <select id="program_mode">
                    <option value="" disabled selected>Please Select</option>
                    <option value="physical">Physical</option>
                    <option value="online">Online</option>
                    <option value="hybrid">Hybrid</option>
                </select>
            </div>

            <div class="form-group online-field hidden">
                <label>Platform</label>
                <select id="platform_id"></select>
            </div>

            <!-- Location (physical only) -->
            <div class="form-group physical-field full">
                <label>Location</label>
                <input type="text" id="location" placeholder="Venue">
            </div>

            <!-- Row: Scale | Officiate -->
            <div class="form-group">
                <label>Scale</label>
                <select id="scale_id" required></select>
            </div>

            <div class="form-group">
                <label>Officiate</label>
                <input type="text" id="officiated_by" placeholder="Speaker / Officiator">
            </div>

            <!-- Target Groups (full width) -->
            <div class="form-group full">
                <label>Target Groups</label>
                <div id="target-group-list" class="checkbox-list"></div>
            </div>
            
            <div id="participant-stats-section" class="participant-stats form-group full hidden">
                    <label>Participants</label>
                <div class="stats-row">
                    <span>Total: <strong id="total_participant_count">0</strong></span>
                    <span>Physical: <strong id="physical_participant_count">0</strong></span>
                    <span>Online: <strong id="online_participant_count">0</strong></span>
                </div>
 <!-- MANUAL OVERRIDE (PAST PROGRAM ONLY) -->
<div id="manual-override-box" class="form-group full hidden panel-spaced">
    <label>
        <input type="checkbox" id="manual_override_toggle">
        Use manual participant count (past program only)
    </label>

    <div id="manual-count-wrapper" class="hidden panel-spaced-sm">

        <div class="inline-fields">
            <div class="inline-field">
                <label>Physical Participants</label>
                <input type="number"
                       id="manual_physical"
                       min="0"
                       value="0">
            </div>

            <div class="inline-field">
                <label>Online Participants</label>
                <input type="number"
                       id="manual_online"
                       min="0"
                       value="0">
            </div>
        </div>

        <small class="danger-help">
            Upload & participant management will be disabled when enabled.
        </small>
    </div>
</div>


                <div class="upload-row">
                    <button id="open-upload-modal" class="btn btn-primary" type="button">
                        Upload Participants (CSV)
                    </button>
                    <button id="open-participant" class="btn btn-primary success-button" type="button">
                        Open Participants Management
                    </button>
                </div>
            
            </div>

            <!-- URL Link (online only) -->
            <div class="form-group full url-field">
                <label>Document URL [Poster/Brosur/Programme Book]</label>
                <input type="url" id="document_url" placeholder="https://drive.google.com/..." />
            </div>

            <!-- Program Details -->
            <div class="form-group full">
                <label>Program Description/Tentative</label>
                <textarea id="program_details" rows="3"></textarea>
            </div>

            <!-- Image URL -->
            <div class="form-group full">
                <label>Poster Image URL</label>
                <input type="url" id="cover_image_url" placeholder="https://drive.google.com/..." />
            </div>

            <!-- Buttons -->
            <div class="modal-footer full">
                <button type="button" class="btn-secondary" id="program-cancel-btn">Cancel</button>
                <button type="submit" class="program-save-btn btn-primary">Save Program</button>
            </div>

        </form>

    </div>
</div>


<!-- ================= BULK IMPORT MODAL ================= -->
<div id="bulk-import-modal" class="modal">
    <div class="modal-content">
        
        <div class="modal-header">
            <h2>Bulk Import Programs</h2>
            <button class="modal-close" id="bulk-import-close">&times;</button>
        </div>

        <form id="bulk-import-form" class="data-form" enctype="multipart/form-data">
            
            <div class="form-group">
          
                <input 
                    type="hidden" 
                    id="import-program-parent" 
                    value="<?php echo $_SESSION['library_parent_id']; ?>" 
                    readonly
                >
            </div>
            
            <div class="form-group">
              
                <input 
                    type="hidden" 
                    id="import-library-type" 
                    value="<?php echo $_SESSION['library_type_id']; ?>" 
                    readonly
                >
            </div>

            <!-- Program ID -->
            <div class="form-group">
                <input 
                    type="hidden" 
                    id="import-program-id" 
                    value="<?= htmlspecialchars($program_id, ENT_QUOTES, 'UTF-8') ?>" 
                    readonly
                >
            </div>

            <!-- Library ID -->
            <div class="form-group">
                <input 
                    type="hidden" 
                    id="import-library-id" 
                    value="<?= htmlspecialchars($_SESSION['library_id'], ENT_QUOTES, 'UTF-8') ?>" 
                    readonly
                >
            </div>

            <!-- CSV Upload -->
            <div class="form-group">
                <label for="import-file">CSV File</label>
                <input 
                    type="file" 
                    id="import-file" 
                    accept=".csv" 
                    required
                >
            </div>

            <button class="btn-secondary" id="download-template-btn" type="button">
                Download CSV Template
            </button>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="bulk-import-cancel">Cancel</button>
                <button type="submit" class="btn-primary">Upload</button>
            </div>

        </form>

    </div>
</div>



<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Record</h2>
            <button class="modal-close" id="modal-close">&times;</button>
        </div>

        <form id="modal-form" class="data-form">
            <div class="form-group">
                <label for="modal-name">Name</label>
                <input type="text" id="modal-name" placeholder="Enter name" required>
            </div>

            <div class="form-group">
                <label for="modal-email">Email</label>
                <input type="email" id="modal-email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label for="modal-status">Status</label>
                <select id="modal-status">
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancel-btn">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>


<!-- =========================
     BULK IMPORT MODAL
========================= -->
<div id="bulk-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Bulk Import Records</h2>
            <button class="modal-close" id="bulk-modal-close">&times;</button>
        </div>

        <div class="data-form">
            <p class="muted-block">
                Import multiple records at once. Enter data in CSV format:
                <br><br>
                <code>Name, Email, Status</code>
            </p>
            
            <div class="form-group">
                <label for="bulk-data">CSV Data</label>
                <textarea id="bulk-data" rows="8"
                    class="full-textarea"
                    placeholder="Example:
John Smith,john@example.com,Pending
Jane Doe,jane@example.com,In Progress"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="bulk-cancel-btn">Cancel</button>
                <button type="button" class="btn-primary" id="bulk-import-submit">Import</button>
            </div>
        </div>
    </div>
</div>




<!-- =======================================
     verify
======================================= -->
<div id="program-verify-modal" class="modal">
    <div class="modal-content wide-modal">

        <div class="modal-header">
            <h2 id="verify-modal-title">Program Details</h2>
            <button class="modal-close" id="verify-modal-close">&times;</button>
        </div>

<div id="verify-modal-body" class="verify-body">

  <!-- HEADER -->
  <div class="verify-title">
    <h2 id="v-program-name"></h2>
    <h3 id="v-library-name"></h3>
    <div class="verify-meta">
      <div class="hidden"><span><strong>ID:</strong> <span id="v-program-id">None</span></span></div>
      <span><strong>Library Type:</strong> <span id="v-library-type">None</span></span>
      <span><strong>Main Library:</strong> <span id="v-parent-library">None</span></span>
    </div>
  </div>
  <br>
  <!-- TWO COLUMN LAYOUT -->
  <div class="verify-layout">

    <!-- LEFT : PROGRAM DETAILS -->
    <section class="verify-panel">
      <h3>Program Details</h3>
      <div class="detail-row"><label>Scale</label><span id="v-scale">None</span></div>
      <div class="detail-row"><label>Mode</label><span id="v-mode">None</span></div>

      <div class="detail-row"><label>Start</label><span id="v-start"></span></div>
      <div class="detail-row"><label>End</label><span id="v-end"></span></div>
      
      <div class="detail-row"><label>Location</label><span id="v-location">None</span></div>
      <div class="detail-row"><label>Platform</label><span id="v-platform">None</span></div>
      
      <div class="detail-row"><label>Officiate</label><span id="v-officiated_by"></span></div>

      <div class="detail-row"><label>Image URL</label><span id="v-image"></span></div>
      <div class="detail-row"><label>Supp. Documents</label><span id="v-documents"></span></div>

      <div class="detail-row full">
        <label>Target Groups</label>
        <span id="v-target-groups"></span>
      </div>

      <div class="detail-row full">
        <label>Description</label>
        <div id="v-details" class="detail-box"></div>
      </div>
    </section>

    <!-- RIGHT : STACKED -->
    <div class="verify-side">

      <!-- PARTICIPANTS -->
      <section class="verify-panel">
        <h3>Participants</h3>

        <div class="participant-status">
          <span id="v-participant-status" class="participant-tag"></span>
        </div>

        <div class="participant-grid">
          <div><label>Physical</label><span id="v-p-physical">0</span></div>
          <div><label>Online</label><span id="v-p-online">0</span></div>
          <div class="total"><label>Total</label><span id="v-p-total">0</span></div>
        </div>
      </section>

      <!-- NOTES -->
      <section class="verify-panel">
        <h3>Notes</h3>
        <div id="v-notes-list" class="notes-box">
          <div class="note-item empty">No notes</div>
        </div>
      </section>

    </div>

  </div>
</div>




        <div class="modal-footer verify-footer">
            <button id="btn-approve" class="btn-primary">Approve</button>
            <button id="btn-reject" class="btn-warning">Reject</button>
            <button id="btn-remove" class="btn-danger">Remove</button>
            <button id="btn-close" class="btn-secondary">Close</button>
        </div>

    </div>
</div>


<div id="participant-upload-modal" class="modal">
    <div class="modal-content wide-modal">

        <h2>Upload Participants (CSV)</h2>

        <input type="file" id="csv-file" accept=".csv">
        <button id="open-template" class="btn btn-primary template-button" type="button"
                       >
                        Open Template
                    </button>
        <button id="upload-csv-btn" class="btn btn-primary">Upload</button>

        <hr>

        <div id="upload-results"></div>

        <button id="close-upload-modal" class="btn">Close</button>

    </div>
</div>


<!-- =========================
     DETAIL VIEW MODAL
========================= -->
<div id="detail-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Record Details</h2>
            <button class="modal-close" id="detail-modal-close">&times;</button>
        </div>

        <div class="data-form">

            <div class="detail-row">
                <span class="detail-label">ID:</span>
                <span class="detail-value" id="detail-id"></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value" id="detail-name"></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value" id="detail-email"></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" id="detail-status"></span>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-primary" id="detail-close-btn">Close</button>
            </div>
        </div>
    </div>
</div>
