<?php
$routes = [ // each route has a path and an optional permission
    'login/auth' => ["path" => 'core/login/login.php'],
    'login/register' => ["path" => 'core/login/selfregistration.php'],
    'login/update' => ["path" => 'core/login/doUpdate.php', "permission" => "WRITE:CORE.SETTINGS"],
    'login/access' => ["path" => 'core/login/login_docker.php', "permission" => "WRITE:CORE.SETTINGS"],

    'php/info' => ["path" => 'core/dev/phpinfo.php', "permission" => "WRITE:CORE.SETTINGS"], 
    'php/debug' => ["path" => 'core/dev/crypt.php', "permission" => "WRITE:CORE.SETTINGS"],

    'user/home' => ["path" => 'core/user/home.php'],
    'user/time' => ["path" => 'core/user/timeCalcTable.php', "permission" => "WRITE:CORE.SETTINGS"],
    'user/book' => ["path" => 'core/user/userProjecting.php', "permission" => "WRITE:CORE.SETTINGS"],
    'user/logout' => ["path" => 'core/user/logout.php'],
    'user/request' => ["path" => 'core/user/makeRequest.php', "permission" => "WRITE:CORE.SETTINGS"],

    'social/post' => ["path" => 'social/social_messages.php', "permission" => "READ:CORE.SETTINGS"],
    'social/profile' => ["path" => 'social/user_profile.php', "permission" => "WRITE:CORE.SETTINGS"],

    'setup/run' => ["path" => 'core/setup/setup.php', "permission" => "WRITE:CORE.SETTINGS"],
    'setup/wizard' => ["path" => 'core/setup/install_wizard.php', "permission" => "WRITE:CORE.SETTINGS"],
    'setup/keys' => ["path" => 'core/setup/download_keys.php', "permission" => "WRITE:CORE.SETTINGS"],

    'system/users' => ["path" => 'core/system/editUsers.php', "permission" => "CORE.USERS"],
    'system/saldo' => ["path" => 'core/system/admin_saldoview.php', "permission" => "CORE.USERS"],
    'system/register' => ["path" => 'core/system/register.php', "permission" => "WRITE:CORE.USERS"],
    'system/deactivated' => ["path" => 'core/system/deactivatedUsers.php', "permission" => "CORE.USERS"],
    'system/company' => ["path" => 'core/system/editCompanies.php', "permission" => "CORE.COMPANIES"],
    'system/new' => ["path" => 'core/system/new_Companies.php', "permission" => "WRITE:CORE.COMPANIES"],
    'system/teams' => ["path" => 'core/system/teamConfig.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/holidays' => ["path" => 'core/system/editHolidays.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/advanced' => ["path" => 'core/system/options_advanced.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/password' => ["path" => 'core/system/options_password.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/email' => ["path" => 'core/system/options_report.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/tasks' => ["path" => 'core/system/taskScheduler.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/backup' => ["path" => 'core/system/system_backup.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/restore' => ["path" => 'core/system/system_restore.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/clients' => ["path" => 'core/system/editCustomers.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/archive' => ["path" => 'core/system/options_archive.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/security' => ["path" => 'core/system/securitySettings.php', "permission" => "CORE.SECURITY"],
    'system/restic' => ["path" => 'core/system/resticBackup.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/downloadSql' => ["path" => 'core/system/backup_download.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/cryptlog' => ["path" => 'core/system/viewCryptLog.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/tags' => ["path" => 'core/system/options_tags.php', "permission" => "WRITE:CORE.SETTINGS"],
    'system/checkinLogs' => ["path" => 'core/system/checkinLogs.php', "permission" => "CORE.USERS"],

    'time/view' => ["path" => 'time/time_projects.php', "permission" => "WRITE:CORE.SETTINGS"],
    'time/corrections' => ["path" => 'time/time_adjusts.php', "permission" => "WRITE:CORE.SETTINGS"],
    'time/travels' => ["path" => 'time/time_travel.php', "permission" => "WRITE:CORE.SETTINGS"],
    'time/vacations' => ["path" => 'time/time_vacation.php', "permission" => "WRITE:CORE.SETTINGS"],
    'time/check' => ["path" => 'time/adminTodos.php', "permission" => "WRITE:CORE.SETTINGS"],
    'time/requests' => ["path" => 'time/time_requests.php', "permission" => "WRITE:CORE.SETTINGS"],

    'project/view' => ["path" => 'project/project_public.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/csvDownload' => ["path" => 'project/csvDownload.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/pdfDownload' => ["path" => 'project/pdfDownload.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/log' => ["path" => 'project/audit_projectBookings.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/options' => ["path" => 'project/options.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/detailDownload' => ["path" => 'project/download_archiveObject.php', "permission" => "WRITE:CORE.SETTINGS"],
    'project/public' => ["path" => 'project/project_public.php', "permission" => "WRITE:CORE.SETTINGS"],

    'report/designer' => ["path" => 'report/templateSelect.php', "permission" => "WRITE:CORE.SETTINGS"],
    'report/downloadTem' => ["path" => 'report/templateDownload.php', "permission" => "WRITE:CORE.SETTINGS"],
    'report/editTemp' => ["path" => 'report/templateEdit.php', "permission" => "WRITE:CORE.SETTINGS"],
    'report/previewTem' => ["path" => 'report/templatePreview.php', "permission" => "WRITE:CORE.SETTINGS"],

    'dynamic-projects/view' => ["path" => 'project/dynamicProjects.php', "permission" => "WRITE:CORE.SETTINGS"],

    'tasks/icalDownload' => ["path" => 'project/download_ical.php', "permission" => "WRITE:CORE.SETTINGS"],

    'report/send' => ["path" => 'schedule/sendMailReport.php', "permission" => "WRITE:CORE.SETTINGS"],
    'report/autotask' => ["path" => 'schedule/autotask.php', "permission" => "WRITE:CORE.SETTINGS"],
    'report/tasks' => ["path" => 'schedule/getAllEmailTasks.php', "permission" => "WRITE:CORE.SETTINGS"],

    'erp/view' => ["path" => 'erp/erp_view.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/articles' => ["path" => 'erp/product_articles.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/taxes' => ["path" => 'erp/editTaxes.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/units' => ["path" => 'erp/editUnits.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/payment' => ["path" => 'erp/editPaymentMethods.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/shipping' => ["path" => 'erp/editShippingMethods.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/representatives' => ["path" => 'erp/editRepres.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/download' => ["path" => 'erp/download_proposal.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/edit' => ["path" => 'erp/erp_process.php', "permission" => "WRITE:CORE.SETTINGS"],
    'erp/receipts' => ["path" => 'erp/receiptBook.php', "permission" => "WRITE:CORE.SETTINGS"],

    'finance/account' => ["path" => 'finance/accounting.php', "permission" => "WRITE:CORE.SETTINGS"],
    'finance/plan' => ["path" => 'finance/accountPlan.php', "permission" => "WRITE:CORE.SETTINGS"],
    'finance/journal' => ["path" => 'finance/accountJournal.php', "permission" => "WRITE:CORE.SETTINGS"],

    'dsgvo/documents' => ["path" => 'dsgvo/dsgvo_view.php', "permission" => "DSGVO.AGREEMENTS"],
    'dsgvo/access' => ["path" => 'dsgvo/dsgvo_access.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/templates' => ["path" => 'dsgvo/dsgvo_mail.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/edit' => ["path" => 'dsgvo/dsgvo_edit.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/vv' => ["path" => 'dsgvo/dsgvo_vv.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/log' => ["path" => 'dsgvo/dsgvo_log.php', "permission" => "DSGVO.LOGS"],
    'dsgvo/vDetail' => ["path" => 'dsgvo/dsgvo_vv_detail.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/editTemplate' => ["path" => 'dsgvo/dsgvo_vv_template_edit.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/training' => ["path" => 'dsgvo/dsgvo_training.php', "permission" => "DSGVO.TRAINING"],
    'dsgvo/data-matrix' => ["path" => 'dsgvo/dsgvo_data_matrix.php', "permission" => "DSGVO.SETTINGS"],
    'dsgvo/pdfDownload' => ["path" => 'dsgvo/dsgvo_vv_pdfDownload.php', "permission" => "DSGVO.SETTINGS"],

    'archive/share' => ["path" => 'archive/archive_share.php', "permission" => "WRITE:CORE.SETTINGS"],
    'archive/files' => ["path" => 'archive/archive_files.php', "permission" => "WRITE:CORE.SETTINGS"],
    'archive/private' => ["path" => 'archive/private_view.php', "permission" => "WRITE:CORE.SETTINGS"],

    'extern/login' => ["path" => 'external/login.php', "permission" => "WRITE:CORE.SETTINGS"],
    'extern/home' => ["path" => 'external/home.php', "permission" => "WRITE:CORE.SETTINGS"]
];

$mime_types = array(
	'.css' => "text/css",                 '.js' => "text/javascript",               '.png' => "image/png",
	'.jpeg' => "image/jpeg",              '.jpg' => "image/jpg",                    '.woff2' => "application/font-woff2",
	'.gif' => "image/gif",                '.woff' => "application/font-woff",       '.ttf' => "font/opentype"
);

function has_permission_for_route($route){
	if(isset($route["permission"]) && $route["permission"]){
    $exploded = explode(":",$route["permission"]);
		if(count($exploded) == 0) return false;
		if(count($exploded) == 1){
			$type = "READ";
			$rest = $exploded[0];
		}else{
			$type = $exploded[0];
			$rest = $exploded[1];
		}
		$exploded = explode(".",$rest);
		if(count($exploded) != 2) return false;
		$group = $exploded[0];
		$permission = $exploded[1];
		require_once "src" . DIRECTORY_SEPARATOR . "validate.php";
		return has_permission($type,$group,$permission);
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
			echo 'Access denied. <a href="../user/logout"> logout</a>';
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
		include '404.html';
	}
} else {
	header('Location: login/auth');
}
