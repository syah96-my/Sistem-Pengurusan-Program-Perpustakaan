<?php

require __DIR__ . '/controllers/library_controller.php';
require __DIR__ . '/controllers/child_library_controller.php';
require __DIR__ . '/controllers/library_types_controller.php';
require __DIR__ . '/controllers/program_types_controller.php';
require __DIR__ . '/controllers/scales_controller.php';
require __DIR__ . '/controllers/platforms_controller.php';
require __DIR__ . '/controllers/target_groups_controller.php';
require __DIR__ . '/controllers/programs_controller.php';
require __DIR__ . '/controllers/program_notes_controller.php';
require __DIR__ . '/controllers/program_workflow_controller.php';
require __DIR__ . "/controllers/bulk_programs_controller.php";
require __DIR__ . "/controllers/participants_controller.php";
require __DIR__ . "/controllers/UsersController.php";
require __DIR__ . "/controllers/StatusCountController.php";
require __DIR__ . "/controllers/stats_controller.php";
require __DIR__ . "/controllers/program_status_controller.php";
require __DIR__ . "/controllers/SocmedActivitiesController.php";
require __DIR__ . "/controllers/dashboard_controller.php";

$route = $_GET['route'] ?? '';

switch ($route) {
    
case 'statuscount/summary':
    StatusCountController::summary($pdo);
    break;
/* ================================
   Dashboard
================================ */
case 'dashboard/summary':
    DashboardController::summary($pdo);
    break;

case 'dashboard/verified-graph':
    DashboardController::verifiedGraph($pdo);
    break;
    
case 'dashboard/monthly':
    DashboardController::verifiedMonthly($pdo);
    break;

case 'dashboard/recent':
    DashboardController::recent($pdo);
    break;

/* ================================
   PROGRAM STATUS RECONCILIATION
================================ */

case 'programs/recalc':
    ProgramStatusController::recalcOne($pdo);
    break;

case 'programs/recalc-all':
    ProgramStatusController::recalcAll($pdo);
    break;

case 'programs/recalc-library':
    ProgramStatusController::recalcByLibrary($pdo);
    break;

/* ================================
   SOCIAL MEDIA ACTIVITIES
================================ */

case 'socmed/create':
    SocmedActivitiesController::create($pdo);
    break;

case 'socmed/update':
    SocmedActivitiesController::update($pdo);
    break;

case 'socmed/delete':
    SocmedActivitiesController::delete($pdo);
    break;

case 'socmed/view':
    SocmedActivitiesController::view($pdo);
    break;

case 'socmed/list':
    SocmedActivitiesController::listAll($pdo);
    break;

/* ================================
   ANALYTICS / STATS ENDPOINTS
================================ */

case 'stats/program-status':
    Stats_Controller::program_status($pdo);
    break;

case 'stats/program-type':
    Stats_Controller::program_type($pdo);
    break;

case 'stats/scale':
    Stats_Controller::scale($pdo);
    break;

case 'stats/mode':
    Stats_Controller::mode($pdo);
    break;

case 'stats/program-target':
    Stats_Controller::program_target($pdo);
    break;

case 'stats/participants':
    Stats_Controller::participants($pdo);
    break;

case 'stats/search-programs':
    Stats_Controller::search_programs($pdo);
    break;

// (optional)
case 'stats/program-detail':
    Stats_Controller::program_detail($pdo);
    break;

    /* ================================
       LIBRARIES
    ================================ */

    case 'libraries/list':
        LibrariesController::list($pdo);
        break;
    
    case 'libraries/create':
        LibrariesController::create($pdo);
        break;
    
    case 'libraries/update':
        LibrariesController::update($pdo);
        break;
    
    case 'libraries/delete':
        LibrariesController::delete($pdo);
        break;
    
    case 'libraries/deactivate':
        LibrariesController::deactivate($pdo);
        break;
    
    case 'libraries/activate':
        LibrariesController::activate($pdo);
        break;

    case 'libraries/get_all':
        LibrariesController::get_all($pdo);
        break;

    case 'libraries/get_children':
        LibrariesController::get_child_library($pdo);
        break;

/* ============================
   USERS
============================ */
case 'users/datatables':
    UsersController::datatables($pdo);
    break;

case 'users/create':
    UsersController::create($pdo);
    break;

case 'users/update':
    UsersController::update($pdo);
    break;

case 'users/reset_password':
    UsersController::reset_password($pdo);
    break;

case 'users/delete':
    UsersController::delete($pdo);
    break;

case 'users/bulk_import':
    UsersController::bulk_import($pdo);
    break;

case 'users/activate':
    UsersController::activate($pdo);
    break;

case 'users/deactivate':
    UsersController::deactivate($pdo);
    break;
    
case 'users/get':
    UsersController::get($pdo);
    break;


    /* ================================
       CHILD LIBRARIES
    ================================ */
    case 'child/list':
        ChildLibraryController::list($pdo);
        break;

    case 'child/create':
        ChildLibraryController::create($pdo);
        break;

    case 'child/update':
        ChildLibraryController::update($pdo);
        break;

    case 'child/deactivate':
        ChildLibraryController::deactivate($pdo);
        break;

    case 'child/activate':
        ChildLibraryController::activate($pdo);
        break;
        
    case 'child/datatables':
        ChildLibraryController::datatables($pdo);
        break;
    case 'child/bulk_import':
        ChildLibraryController::bulk_import($pdo);
        break;


    /* ================================
       LIBRARY TYPES
    ================================ */
    case 'library_types/list':
        LibraryTypesController::list($pdo);
        break;

    case 'library_types/create':
        LibraryTypesController::create($pdo);
        break;

    case 'library_types/update':
        LibraryTypesController::update($pdo);
        break;


    /* ================================
       PROGRAM TYPES
    ================================ */
    case 'program_types/list':
        ProgramTypesController::list($pdo);
        break;

    case 'program_types/create':
        ProgramTypesController::create($pdo);
        break;

    case 'program_types/update':
        ProgramTypesController::update($pdo);
        break;

    case 'program_types/enable':
        ProgramTypesController::enable($pdo);
        break;

    case 'program_types/disable':
        ProgramTypesController::disable($pdo);
        break;


    /* ================================
       SCALES
    ================================ */
    case 'scales/list':
        ScalesController::list($pdo);
        break;

    case 'scales/create':
        ScalesController::create($pdo);
        break;

    case 'scales/update':
        ScalesController::update($pdo);
        break;

    case 'scales/enable':
        ScalesController::enable($pdo);
        break;

    case 'scales/disable':
        ScalesController::disable($pdo);
        break;


    /* ================================
       PLATFORMS
    ================================ */
    case 'platforms/list':
        PlatformsController::list($pdo);
        break;

    case 'platforms/create':
        PlatformsController::create($pdo);
        break;

    case 'platforms/update':
        PlatformsController::update($pdo);
        break;

    case 'platforms/enable':
        PlatformsController::enable($pdo);
        break;

    case 'platforms/disable':
        PlatformsController::disable($pdo);
        break;


    /* ================================
       TARGET GROUPS
    ================================ */
    case 'target_groups/list':
        TargetGroupsController::list($pdo);
        break;

    case 'target_groups/create':
        TargetGroupsController::create($pdo);
        break;

    case 'target_groups/update':
        TargetGroupsController::update($pdo);
        break;

    case 'target_groups/enable':
        TargetGroupsController::enable($pdo);
        break;

    case 'target_groups/disable':
        TargetGroupsController::disable($pdo);
        break;


    /* ================================
       PROGRAM CRUD
    ================================ */
    case 'programs/create':
        ProgramsController::create($pdo);
        break;

    case 'programs/update':
        ProgramsController::update($pdo);
        break;

    case 'programs/delete':
        ProgramsController::delete($pdo);
        break;

    case 'programs/view':
        ProgramsController::view($pdo);
        break;

    case 'programs/list':
        ProgramsController::list($pdo);
        break;

    /* NEW: DataTables server-side API */
    case 'programs/datatables':
        ProgramsController::datatables($pdo);
        break;


    case "programs/datatables_verify":
        ProgramsController::datatablesVerify($pdo);
        break;

    case "programs/datatables_delete":
        ProgramsController::datatables_deleted_with_notes($pdo);
        break;

    
    case "programs/bulk_import":
        BulkProgramsController::bulk_import($pdo);
        break;


    /* ================================
       PROGRAM NOTES
    ================================ */
    case 'programs/note/create':
        ProgramNotesController::create($pdo);
        break;

    case 'programs/note/list':
        ProgramNotesController::list($pdo);
        break;


    /* ================================
       PROGRAM WORKFLOW
    =============================== */
    case 'programs/verify':
        ProgramWorkflowController::verify($pdo);
        break;
    
    case 'programs/reject':
        ProgramWorkflowController::reject($pdo);
        break;

    case 'programs/remove':
        ProgramWorkflowController::delete($pdo);
        break;
    
    case 'programs/reset':
        ProgramWorkflowController::reset($pdo);
        break;
    
    case 'programs/verify_bulk':
        ProgramWorkflowController::verify_bulk($pdo);
        break;
    
    case 'programs/reject_bulk':
        ProgramWorkflowController::reject_bulk($pdo);
        break;

    case 'programs/remove_bulk':
        ProgramWorkflowController::bulk_remove($pdo);
        break;
    
    case 'programs/set_stage':
        ProgramsController::set_stage($pdo);
        break;
    
    case 'programs/bulk_verify':
        ProgramWorkflowController::bulk_verify($pdo);
        break;
    
    case 'programs/bulk_reject':
        ProgramWorkflowController::bulk_reject($pdo);
        break;
    
    case 'programs/bulk_reset':
        ProgramWorkflowController::bulk_reset($pdo);
        break;
            /* ================================
       Participant
    =============================== */

case 'participants/add':
    ParticipantsController::add($pdo);
    break;

case 'participants/bulk_upload':
    ParticipantsController::bulk_upload($pdo);
    break;

case 'participants/list':
    ParticipantsController::list($pdo);
    break;

case 'participants/rebuild_stats':
    ParticipantsController::rebuild_stats($pdo);
    break;


    /* ================================
       DEFAULT
    ================================ */
    default:
        Response::json(["error" => "Route not found"], 404);
}

?>
