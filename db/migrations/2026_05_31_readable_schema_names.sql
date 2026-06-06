-- Refactor unclear business columns to readable names.
-- Run this once on an existing pre-refactor database.

ALTER TABLE gm_programs
  CHANGE mode program_mode enum('physical','online','hybrid') NOT NULL,
  CHANGE url_link document_url text DEFAULT NULL,
  CHANGE officiate officiated_by varchar(255) DEFAULT NULL,
  CHANGE uid public_token char(36) NOT NULL,
  CHANGE image_url cover_image_url text DEFAULT NULL,
  CHANGE status verification_status enum('pending','verified','rejected','incomplete') NOT NULL DEFAULT 'incomplete',
  CHANGE deleted is_deleted tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE gm_participants
  CHANGE name participant_name varchar(255) NOT NULL,
  CHANGE phone phone_number varchar(50) DEFAULT NULL,
  CHANGE occupation position_title varchar(255) DEFAULT NULL,
  CHANGE company organization_name varchar(255) DEFAULT NULL,
  CHANGE registered_by registration_source enum('self','staff_upload') NOT NULL DEFAULT 'self';

ALTER TABLE gm_program_participant_stats
  CHANGE total_participants total_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE manual_override is_manual_override tinyint(1) NOT NULL DEFAULT 0,
  CHANGE male_count male_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE female_count female_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE avg_age average_age decimal(5,2) DEFAULT NULL,
  CHANGE physical_count physical_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE online_count online_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE self_registered_count self_registered_participant_count int(11) NOT NULL DEFAULT 0,
  CHANGE staff_uploaded_count staff_uploaded_participant_count int(11) NOT NULL DEFAULT 0;

ALTER TABLE gm_notes
  CHANGE role note_role enum('creator','verifier','reject','remove','admin') NOT NULL DEFAULT 'creator',
  CHANGE program_notes note_text text NOT NULL;

ALTER TABLE gm_socmed_activities
  CHANGE deleted is_deleted tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE gm_programs
  DROP INDEX unique_uid,
  ADD UNIQUE KEY unique_public_token (public_token),
  ADD KEY idx_programs_report_scope (library_id, is_deleted, verification_status, program_stage, program_start),
  ADD KEY idx_programs_parent_report_scope (parent_library_id, is_deleted, program_start),
  ADD KEY idx_programs_type_report (program_type_id, is_deleted, verification_status, program_start);

ALTER TABLE gm_participants
  DROP INDEX registration_source,
  DROP INDEX attendance_mode,
  ADD KEY idx_participants_program_mode_source (program_id, attendance_mode, registration_source),
  ADD KEY idx_participants_source (registration_source);

ALTER TABLE gm_program_target_groups
  DROP INDEX idx_program_target,
  ADD UNIQUE KEY unique_program_target_group (program_id, target_group_id);

ALTER TABLE gm_notes
  ADD KEY idx_notes_program_created (program_id, created_at);

ALTER TABLE gm_socmed_activities
  ADD KEY idx_socmed_library_deleted_date (library_id, is_deleted, activity_date);
