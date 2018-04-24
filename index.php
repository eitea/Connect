<?php
$routes = array(
	'login/auth' => 'core/login/login.php',                           'login/register' => 'core/login/selfregistration.php',       'login/update' => 'core/login/doUpdate.php',
	'login/access' => 'core/login/login_docker.php',

	'php/info' => 'core/dev/phpinfo.php',                             'php/ext' => 'core/dev/phpext.php',

	'user/home' => 'core/user/home.php',                              'user/time' => 'core/user/timeCalcTable.php',                'user/book' => 'core/user/userProjecting.php',
	'user/logout' => 'core/user/logout.php',                          'user/request' => 'core/user/makeRequest.php',               'user/ready' => 'core/user/readyPlan.php',

	'social/post' => 'core/system/social_messages.php',               'social/home' => 'core/system/socialMedia.php',
	'setup/run' => 'core/setup/setup.php',                            'setup/wizard' => 'core/setup/install_wizard.php',          'setup/keys' => 'core/setup/download_keys.php',

	'system/users' => 'core/system/editUsers.php',                    'system/saldo' => 'core/system/admin_saldoview.php',         'system/register' => 'core/system/register.php',
	'system/deactivated' => 'core/system/deactivatedUsers.php',       'system/company' => 'core/system/editCompanies.php',         'system/new' => 'core/system/new_Companies.php',
	'system/teams' => 'core/system/teamConfig.php',                   'system/holidays' => 'core/system/editHolidays.php',         'system/advanced' => 'core/system/options_advanced.php',
	'system/password' => 'core/system/options_password.php',          'system/email' => 'core/system/options_report.php',          'system/tasks' => 'core/system/taskScheduler.php',
	'system/backup' => 'core/system/system_backup.php',               'system/restore' => 'core/system/system_restore.php',        'system/clients' => 'core/system/editCustomers.php',
	'system/clientDetail' => 'core/system/editCustomer_detail.php',   'system/archive' => 'core/system/options_archive.php',       'system/security' => 'core/system/securitySettings.php',
	'system/downloadSql' => 'core/system/backup_download.php',        'system/cryptlog'=> 'core/system/viewCryptLog.php',          'system/restic' => 'core/system/resticBackup.php',
	'system/checkinLogs' => 'core/system/checkinLogs.php',

	'time/view' => 'time/time_projects.php',                          'time/corrections' => 'time/time_adjusts.php',               'time/travels' => 'time/time_travel.php',
	'time/vacations' => 'time/time_vacation.php',                     'time/check' => 'time/adminTodos.php',                       'time/requests' => 'time/time_requests.php',

	'project/view' => 'project/project_view.php',                     'project/csvDownload' => 'project/csvDownload.php',          'project/pdfDownload' => 'project/pdfDownload.php',
	'project/log' => 'project/audit_projectBookings.php',             'project/options' => 'project/options.php',                  'project/detail' => 'project/project_detail.php',
	'project/detailDownload' => 'project/download_archiveObject.php', 'project/public' => 'project/project_public.php',

	'report/designer' => 'report/templateSelect.php',                 'report/downloadTem' => 'report/templateDownload.php',       'report/editTemp' => 'report/templateEdit.php',
	'report/previewTem' => 'report/templatePreview.php',

	'dynamic-projects/view' => 'project/dynamicProjects.php',         'tasks/icalDownload' => 'project/download_ical.php',

	'report/send' => 'schedule/sendMailReport.php',                   'report/autotask' => 'schedule/autotask.php',                'report/tasks' => 'schedule/getAllEmailTasks.php',

	'erp/view' => 'erp/erp_view.php',                                 'erp/articles' => 'erp/product_articles.php',                'erp/taxes' => 'erp/editTaxes.php',
	'erp/units' => 'erp/editUnits.php',                               'erp/payment' => 'erp/editPaymentMethods.php',               'erp/shipping' => 'erp/editShippingMethods.php',
	'erp/representatives' => 'erp/editRepres.php',                    'erp/download' => 'erp/download_proposal.php',               'erp/edit' => 'erp/erp_process.php',
	'erp/receipts' => 'erp/receiptBook.php',

	'finance/account' => 'finance/accounting.php',                    'finance/plan' => 'finance/accountPlan.php',                 'finance/journal' => 'finance/accountJournal.php',

	'dsgvo/documents' => 'dsgvo/dsgvo_view.php',                      'dsgvo/access' => 'dsgvo/dsgvo_access.php',                  'dsgvo/templates' => 'dsgvo/dsgvo_mail.php',
	'dsgvo/edit' => 'dsgvo/dsgvo_edit.php',                           'dsgvo/vv' => 'dsgvo/dsgvo_vv.php',                          'dsgvo/log' => 'dsgvo/dsgvo_log.php',
	'dsgvo/vDetail' => 'dsgvo/dsgvo_vv_detail.php',                   'dsgvo/editTemplate' => 'dsgvo/dsgvo_vv_template_edit.php',  'dsgvo/training' => 'dsgvo/dsgvo_training.php',
	'dsgvo/data-matrix' => 'dsgvo/dsgvo_data_matrix.php',

	'archive/share' => 'archive/archive_share.php',                   'archive/files' => 'archive/archive_files.php',              'archive/download' => 'archive/archive_download.php',
	'archive/delete' => 'archive/archive_delete.php',                 'archive/upload' => 'archive/archive_upload.php',            'archive/private'  => 'archive/private_view.php',
	'upload/file' => 'archive/uploadToS3.php',                        'archive/getFolderContent' => 'archive/getFolderContent.php','private/files'   => 'archive/private_files.php',

	'extern/login' => 'external/login.php',                           'extern/home' => 'external/home.php'
);

$mime_types = array(
	'.css' => "text/css",                 '.js' => "text/javascript",               '.png' => "image/png",
	'.jpeg' => "image/jpeg",              '.jpg' => "image/jpg",                    '.woff2' => "application/font-woff2",
	'.gif' => "image/gif",                '.woff' => "application/font-woff",       '.ttf' => "font/opentype"
);

//url must end like this:  / ACCESS / PAGE
$url = strtok($_SERVER['REQUEST_URI'], '?');
$params = explode('/', $url);
$l = count($params) -1 ;
if($l > 1){
	$route = strtok($params[$l - 1].'/'.$params[$l], '?'); //clean get params
	if(array_key_exists($route, $routes)){
		$this_page = basename($routes[$route]);
		include 'src/'.$routes[$route];
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
