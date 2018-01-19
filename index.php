<?php
$routes = array(
  'php/info' => 'phpinfo.php',                            'php/test' => 'template.php',                          'setup/run' => 'setup/setup.php',
  'login/auth' => 'login.php',                            'login/register' => 'selfregistration.php',            'login/update' => 'doUpdate.php',
  'login/access' => 'login_tester.php',

  'user/home' => 'home.php',                              'user/time' => 'timeCalcTable.php',                    'user/book' => 'userProjecting.php',
  'user/logout' => 'logout.php',                          'user/request' => 'makeRequest.php',                   'user/ready' => 'readyPlan.php',

  'social/home' => 'socialMedia.php',

  'system/users' => 'editUsers.php',                       'system/saldo' => 'admin_saldoview.php',              'system/register' => 'register.php',
  'system/deactivated' => 'deactivatedUsers.php',          'system/company' => 'editCompanies.php',              'system/new' => 'new_Companies.php',
  'system/teams' => 'teamConfig.php',                      'system/holidays' => 'editHolidays.php',              'system/advanced' => 'advancedOptions.php',
  'system/password' => 'passwordOptions.php',              'system/email' => 'reportOptions.php',                'system/tasks' => 'taskScheduler.php',
  'system/update' => 'pullGitRepo.php',                    'system/backup' => 'system_backup.php',               'system/restore' => 'system_restore.php',
  'system/designer' => 'templateSelect.php',               'system/clients' => 'editCustomers.php',              'system/clientDetail' => 'editCustomer_detail.php',
  'system/previewTem' => 'templatePreview.php',            'system/downloadTem' => 'templateDownload.php',       'system/editTemp' => 'templateEdit.php',
  'system/downloadSql' => 'system_backup_download.php',               'system/cryptlog'=> 'viewCryptLog.php',               'system/restic' => 'resticBackup.php',
  'system/checkinLogs' => 'checkinLogs.php',

  'time/view' => 'time_projects.php',                      'time/corrections' => 'time_adjusts.php',             'time/travels' => 'time_travel.php',
  'time/vacations' => 'time_vacation.php',                 'time/check' => 'adminTodos.php',                     'time/requests' => 'time_requests.php',

  'project/view' => 'project_view.php',                    'project/csvDownload' => 'csvDownload.php',           'project/pdfDownload' => 'pdfDownload.php',
  'project/log' => 'audit_projectBookings.php',

  'dynamic-projects/user' => 'dynamicProjects_user.php',   'dynamic-projects/admin' => 'dynamicProjects_admin.php', 'dynamic-projects/view' => 'dynamicProjects.php',

  'report/send' => 'sendMailReport.php',                   'report/productivity'=>'report_productivity.php',     'report/autotask' => 'autotask.php',

  'erp/view' => 'erp_view.php',                            'erp/articles' => 'product_articles.php',             'erp/taxes' => 'editTaxes.php',
  'erp/units' => 'editUnits.php',                          'erp/payment' => 'editPaymentMethods.php',            'erp/shipping' => 'editShippingMethods.php',
  'erp/representatives' => 'editRepres.php',               'erp/download' => 'download_proposal.php',            'erp/edit' => 'erp_process.php',
  'erp/receipts' => 'receiptBook.php',                     'erp/suppliers' => 'editSuppliers.php',

  'finance/account' => 'accounting.php',                   'finance/plan' => 'accountPlan.php',                  'finance/journal' => 'accountJournal.php',

  'dsgvo/documents' => 'dsgvo_view.php',                   'dsgvo/access' => 'dsgvo_access.php',                 'dsgvo/templates' => 'dsgvo_mail.php',
  'dsgvo/edit' => 'dsgvo_edit.php',                        'dsgvo/vv' => 'dsgvo_vv.php',                         'dsgvo/vtemplates' =>'dsgvo_vv_templates.php',
  'dsgvo/vDetail' => 'dsgvo_vv_detail.php',                'dsgvo/editTemplate' => 'dsgvo_vv_template_edit.php',

  'archive/share' => 'archive_share.php',                   'archive/files' => 'archive_files.php',              'archive/download' => 'archive_download.php',
  'archive/delete' => 'archive_delete.php',                 'archive/upload' => 'archive_upload.php',            'archive/users'    => 'connection_s3admin.php',

  'upload/file' => 'uploadToS3.php',

  'pgp/keygen' => 'pgpKeyGen.php',                 'misc/sharedfiles' => 'getSharedFiles.php'
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
    $this_page = $routes[$route];
    include 'src/'.$this_page;
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
