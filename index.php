<?php
$routes = [ // each route has a path and an optional permission
    'login/auth' => ["path" => 'core/login/login.php'],
    'login/register' => ["path" => 'core/login/selfregistration.php'],
    'login/update' => ["path" => 'core/login/doUpdate.php'], // TODO: "permission" => "CORE.SETTINGS" after next update
    'login/access' => ["path" => 'core/login/login_docker.php'],

    'php/info' => ["path" => 'core/dev/phpinfo.php', "permission" => "CORE.SETTINGS"],
    'php/debug' => ["path" => 'core/dev/crypt.php', "permission" => "CORE.SETTINGS"],

    'php/dev' => ["path" => 'development_debug.php', "permission" => "DEV.DEBUG"], // doesn't exist => only accessible for user with id 1

    'user/home' => ["path" => 'core/user/home.php'],
    'user/time' => ["path" => 'core/user/timeCalcTable.php', "permission" => "GENERAL.STAMP"],
    'user/book' => ["path" => 'core/user/userProjecting.php', "permission" => "GENERAL.BOOK"],
    'user/logout' => ["path" => 'core/user/logout.php'],
    'user/request' => ["path" => 'core/user/makeRequest.php', "permission" => "GENERAL.REQUEST"],

    'social/post' => ["path" => 'social/social_messages.php', "permission" => "POST.READ"],
    'social/profile' => ["path" => 'social/user_profile.php', "permission" => "SOCIAL.PROFILE"],

    'setup/run' => ["path" => 'core/setup/setup.php'],
    'setup/wizard' => ["path" => 'core/setup/install_wizard.php'],
    'setup/keys' => ["path" => 'core/setup/download_keys.php'], // not sure

    'system/users' => ["path" => 'core/system/editUsers.php', "permission" => "CORE.USERS"],
    'system/saldo' => ["path" => 'core/system/admin_saldoview.php', "permission" => "CORE.USERS"],
    'system/register' => ["path" => 'core/system/register.php', "permission" => "CORE.USERS"],
    'system/deactivated' => ["path" => 'core/system/deactivatedUsers.php', "permission" => "CORE.USERS"],
    'system/company' => ["path" => 'core/system/editCompanies.php', "permission" => "CORE.COMPANIES"],
    'system/new' => ["path" => 'core/system/new_Companies.php', "permission" => "CORE.COMPANIES"],
    'system/teams' => ["path" => 'core/system/teamConfig.php', "permission" => "CORE.TEAMS"],
    'system/holidays' => ["path" => 'core/system/editHolidays.php', "permission" => "CORE.SETTINGS"],
    'system/advanced' => ["path" => 'core/system/options_advanced.php', "permission" => "CORE.SETTINGS"],
    'system/password' => ["path" => 'core/system/options_password.php', "permission" => "CORE.SETTINGS"],
    'system/email' => ["path" => 'core/system/options_report.php', "permission" => "CORE.SETTINGS"],
    'system/tasks' => ["path" => 'core/system/taskScheduler.php', "permission" => "CORE.SETTINGS"],
    'system/backup' => ["path" => 'core/system/system_backup.php', "permission" => "CORE.SETTINGS"],
    'system/restore' => ["path" => 'core/system/system_restore.php', "permission" => "CORE.SETTINGS"],
    'system/clients' => ["path" => 'core/system/editCustomers.php', "permission" => "ERP.CLIENTS"],
    'system/archive' => ["path" => 'core/system/options_archive.php', "permission" => "CORE.SETTINGS"],
    'system/security' => ["path" => 'core/system/securitySettings.php', "permission" => "CORE.SECURITY"],
    'system/restic' => ["path" => 'core/system/resticBackup.php', "permission" => "CORE.SETTINGS"],
    'system/downloadSql' => ["path" => 'core/system/backup_download.php', "permission" => "CORE.SETTINGS"],
    'system/cryptlog' => ["path" => 'core/system/viewCryptLog.php', "permission" => "CORE.SETTINGS"],
    'system/tags' => ["path" => 'core/system/options_tags.php', "permission" => "CORE.SETTINGS"],
    'system/checkinLogs' => ["path" => 'core/system/checkinLogs.php', "permission" => "CORE.USERS"],

    'time/view' => ["path" => 'time/time_projects.php', "permission" => "TIMES.READ"],
    'time/corrections' => ["path" => 'time/time_adjusts.php', "permission" => "TIMES.READ"],
    'time/travels' => ["path" => 'time/time_travel.php', "permission" => "TIMES.READ"],
    'time/vacations' => ["path" => 'time/time_vacation.php'],
    'time/check' => ["path" => 'time/adminTodos.php', "permission" => "TIMES.READ"],
    'time/requests' => ["path" => 'time/time_requests.php', "permission" => "TIMES.READ"],

    'project/view' => ["path" => 'project/project_public.php', "permission" => "PROJECTS.USE"],
    'project/csvDownload' => ["path" => 'project/csvDownload.php', "permission" => "PROJECTS.USE"],
    'project/pdfDownload' => ["path" => 'project/pdfDownload.php', "permission" => "PROJECTS.USE"],
    'project/log' => ["path" => 'project/audit_projectBookings.php', "permission" => "PROJECTS.LOGS"],
    'project/options' => ["path" => 'project/options.php', "permission" => "WORKFLOW.READ"],
    'project/detailDownload' => ["path" => 'project/download_archiveObject.php', "permission" => "PROJECTS.USE"],
    'project/public' => ["path" => 'project/project_public.php', "permission" => "PROJECTS.USE"], // same as project/view?

    'report/designer' => ["path" => 'report/templateSelect.php', "permission" => "CORE.TEMPLATES"],
    'report/downloadTem' => ["path" => 'report/templateDownload.php', "permission" => "CORE.TEMPLATES"],
    'report/editTemp' => ["path" => 'report/templateEdit.php', "permission" => "CORE.TEMPLATES"],
    'report/previewTem' => ["path" => 'report/templatePreview.php', "permission" => "CORE.TEMPLATES"],

    'dynamic-projects/view' => ["path" => 'project/dynamicProjects.php', "permission" => "TASKS.READ"],

    'tasks/icalDownload' => ["path" => 'project/download_ical.php', "permission" => "PROJECTS.USE"],

    'report/send' => ["path" => 'schedule/sendMailReport.php', "permission" => "CORE.SETTINGS"],
    'report/autotask' => ["path" => 'schedule/autotask.php'],
    'report/tasks' => ["path" => 'schedule/getAllEmailTasks.php', "permission" => "CORE.SETTINGS"],

    'erp/view' => ["path" => 'erp/erp_view.php', "permission" => "ERP.PROCESS"],
    'erp/articles' => ["path" => 'erp/product_articles.php', "permission" => "ERP.ARTICLE"],
    'erp/taxes' => ["path" => 'erp/editTaxes.php', "permission" => "ERP.SETTINGS"],
    'erp/units' => ["path" => 'erp/editUnits.php', "permission" => "ERP.SETTINGS"],
    'erp/payment' => ["path" => 'erp/editPaymentMethods.php', "permission" => "ERP.SETTINGS"],
    'erp/shipping' => ["path" => 'erp/editShippingMethods.php', "permission" => "ERP.SETTINGS"],
    'erp/representatives' => ["path" => 'erp/editRepres.php', "permission" => "ERP.SETTINGS"],
    'erp/download' => ["path" => 'erp/download_proposal.php', "permission" => "ERP.SETTINGS"],
    'erp/edit' => ["path" => 'erp/erp_process.php', "permission" => "ERP.PROCESS"],
    'erp/receipts' => ["path" => 'erp/receiptBook.php', "permission" => "ERP.RECEIPT_BOOK"],

    'finance/account' => ["path" => 'finance/accounting.php', "permission" => "FINANCES.ACCOUNTING_PLAN"],
    'finance/plan' => ["path" => 'finance/accountPlan.php', "permission" => "FINANCES.ACCOUNTING_PLAN"],
    'finance/journal' => ["path" => 'finance/accountJournal.php', "permission" => "FINANCES.ACCOUNTING_PLAN"],
	'finance/taxes' => ["path" => 'erp/editTaxes.php', "permission" => "FINANCES.TAX_RATES"],

    'dsgvo/documents' => ["path" => 'dsgvo/dsgvo_view.php', "permission" => "AGREEMENTS.READ"],
    'dsgvo/access' => ["path" => 'dsgvo/dsgvo_access.php', "permission" => "PROCEDURE_DIRECTORY.READ"],
    'dsgvo/templates' => ["path" => 'dsgvo/dsgvo_mail.php', "permission" => "DSGVO.EMAIL_TEMPLATES"],
    'dsgvo/edit' => ["path" => 'dsgvo/dsgvo_edit.php', "permission" => "AGREEMENTS.WRITE"],
    'dsgvo/vv' => ["path" => 'dsgvo/dsgvo_vv.php', "permission" => "PROCEDURE_DIRECTORY.READ"],
    'dsgvo/log' => ["path" => 'dsgvo/dsgvo_log.php', "permission" => "DSGVO.LOGS"],
    'dsgvo/vDetail' => ["path" => 'dsgvo/dsgvo_vv_detail.php', "permission" => "PROCEDURE_DIRECTORY.READ"],
    'dsgvo/editTemplate' => ["path" => 'dsgvo/dsgvo_vv_template_edit.php', "permission" => "PROCEDURE_DIRECTORY.WRITE"],
    'dsgvo/training' => ["path" => 'dsgvo/dsgvo_training.php', "permission" => "TRAINING.READ"],
    'dsgvo/data-matrix' => ["path" => 'dsgvo/dsgvo_data_matrix.php', "permission" => "DSGVO.PROCEDURE_DIRECTORY"],
    'dsgvo/pdfDownload' => ["path" => 'dsgvo/dsgvo_vv_pdfDownload.php', "permission" => "DSGVO.PROCEDURE_DIRECTORY"],

    'archive/share' => ["path" => 'archive/archive_share.php', "permission" => "ARCHIVE.SHARE"],
    // 'archive/files' => ["path" => 'archive/archive_files.php', "permission" => "CORE.SETTINGS"], // doesn't exist
    'archive/private' => ["path" => 'archive/private_view.php', "permission" => "ARCHIVE.PRIVATE"],

    'extern/login' => ["path" => 'external/login.php', "permission" => "CORE.SETTINGS"],
    'extern/home' => ["path" => 'external/home.php', "permission" => "CORE.SETTINGS"]
];

$mime_types = array(
	'.css' => "text/css",                 '.js' => "text/javascript",               '.png' => "image/png",
	'.jpeg' => "image/jpeg",              '.jpg' => "image/jpg",                    '.woff2' => "application/font-woff2",
	'.gif' => "image/gif",                '.woff' => "application/font-woff",       '.ttf' => "font/opentype"
);

function has_permission_for_route($route)
{
    if (isset($route["permission"]) && $route["permission"]) {
        require_once "src" . DIRECTORY_SEPARATOR . "validate.php";
        return Permissions::has($route["permission"]);
    }
    return true;
}

//url must end like this:  / ACCESS / PAGE
$url = strtok($_SERVER['REQUEST_URI'], '?');
$params = explode('/', $url);
$l = count($params) -1 ;
if($l > 1){
	$route = strtok($params[$l - 1].'/'.$params[$l], '?'); //clean get params
	if(array_key_exists($route, $routes)){
		session_start();
		$this_page = basename($routes[$route]["path"]);
		$this_route = $routes[$route];
		$this_url = $route;
		$has_permission_to_view_page = has_permission_for_route($this_route);
		if(!$has_permission_to_view_page){
            header('HTTP/1.0 401 Unauthorized');
            $error_message = "Unauthorized";
            $permission_name = $this_route["permission"];
            $error_explanation =  "You or your team doesn't have the permission $permission_name.";
            $error_code = "401";
		    include 'error.php';
			die();
		}
		include 'src/'.$routes[$route]["path"];
	} elseif(preg_match("/(images|plugins|modules)(\/.*)(\/[A-Za-z0-9\.]*)*(\.css|\.js|\.png|\.jpg|\.woff2|\.woff|\.ttf|\.gif)$/", $url, $matches)){
		if(array_key_exists($matches[4], $mime_types)){
			header('Content-Type: '. $mime_types[$matches[4]]);
		}
		echo file_get_contents($matches[0]);
	} elseif($params[$l -1] == 'ajaxQuery'){
		include 'src/'.$route;
	} else {
        header('HTTP/1.0 404 Not Found');
        $error_message = "SOMETHING JUST WENT WRONG!";
        $permission_name = $this_route["permission"];
        $error_explanation =  "The page you requested could not be found on our servers.";
        $error_code = "404";
        include 'error.php';
	}
} else {
	header('Location: login/auth');
}
