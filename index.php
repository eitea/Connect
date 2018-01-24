<?php
$routes = array(
  'php/info' => 'phpinfo.php',                            'php/test' => 'template.php',                          'setup/run' => 'setup/setup.php',
  'login/auth' => 'login/login.php',                            'login/register' => 'login/selfregistration.php',            'login/update' => 'login/doUpdate.php',
  'login/access' => 'login/login_tester.php',

  'user/home' => 'user/home.php',                              'user/time' => 'user/timeCalcTable.php',                    'user/book' => 'user/userProjecting.php',
  'user/logout' => 'user/logout.php',                          'user/request' => 'user/makeRequest.php',                   'user/ready' => 'user/readyPlan.php',

  'social/home' => 'socialMedia.php',

  'system/users' => 'system/editUsers.php',                       'system/saldo' => 'system/admin_saldoview.php',              'system/register' => 'system/register.php',
  'system/deactivated' => 'system/deactivatedUsers.php',          'system/company' => 'system/editCompanies.php',              'system/new' => 'system/new_Companies.php',
  'system/teams' => 'system/teamConfig.php',                      'system/holidays' => 'system/editHolidays.php',              'system/advanced' => 'system/advancedOptions.php',
  'system/password' => 'system/passwordOptions.php',              'system/email' => 'system/reportOptions.php',                'system/tasks' => 'system/taskScheduler.php',
  'system/update' => 'system/pullGitRepo.php',                    'system/backup' => 'system/system_backup.php',               'system/restore' => 'system/system_restore.php',
  'system/designer' => 'system/templateSelect.php',               'system/clients' => 'system/editCustomers.php',              'system/clientDetail' => 'system/editCustomer_detail.php',
  'system/previewTem' => 'system/templatePreview.php',            'system/downloadTem' => 'system/templateDownload.php',       'system/editTemp' => 'system/templateEdit.php',
  'system/downloadSql' => 'system/system_backup_download.php',               'system/cryptlog'=> 'system/viewCryptLog.php',               'system/restic' => 'system/resticBackup.php',
  'system/checkinLogs' => 'system/checkinLogs.php',

  'time/view' => 'time/time_projects.php',                      'time/corrections' => 'time/time_adjusts.php',             'time/travels' => 'time/time_travel.php',
  'time/vacations' => 'time/time_vacation.php',                 'time/check' => 'time/adminTodos.php',                     'time/requests' => 'time/time_requests.php',

  'project/view' => 'project/project_view.php',                    'project/csvDownload' => 'project/csvDownload.php',           'project/pdfDownload' => 'project/pdfDownload.php',
  'project/log' => 'project/audit_projectBookings.php',             'project/options'    => 'project/options.php',

  'dynamic-projects/view' => 'project/dynamicProjects.php',

  'report/send' => 'schedule/sendMailReport.php',                   'report/productivity'=>'schedule/report_productivity.php',     'report/autotask' => 'schedule/autotask.php',

  'erp/view' => 'erp/erp_view.php',                            'erp/articles' => 'erp/product_articles.php',             'erp/taxes' => 'erp/editTaxes.php',
  'erp/units' => 'erp/editUnits.php',                          'erp/payment' => 'erp/editPaymentMethods.php',            'erp/shipping' => 'erp/editShippingMethods.php',
  'erp/representatives' => 'erp/editRepres.php',               'erp/download' => 'erp/download_proposal.php',            'erp/edit' => 'erp/erp_process.php',
  'erp/receipts' => 'erp/receiptBook.php',                     'erp/suppliers' => 'erp/editSuppliers.php',

  'finance/account' => 'finance/accounting.php',                   'finance/plan' => 'finance/accountPlan.php',                  'finance/journal' => 'finance/accountJournal.php',

  'dsgvo/documents' => 'dsgvo/dsgvo_view.php',                   'dsgvo/access' => 'dsgvo/dsgvo_access.php',                 'dsgvo/templates' => 'dsgvo/dsgvo_mail.php',
  'dsgvo/edit' => 'dsgvo/dsgvo_edit.php',                        'dsgvo/vv' => 'dsgvo/dsgvo_vv.php',                         'dsgvo/vtemplates' =>'dsgvo/dsgvo_vv_templates.php',
  'dsgvo/vDetail' => 'dsgvo/dsgvo_vv_detail.php',                'dsgvo/editTemplate' => 'dsgvo/dsgvo_vv_template_edit.php',

  'archive/share' => 'archive/archive_share.php',                   'archive/files' => 'archive/archive_files.php',              'archive/download' => 'archive/archive_download.php',
  'archive/delete' => 'archive/archive_delete.php',                 'archive/upload' => 'archive/archive_upload.php',

  'upload/file' => 'archive/uploadToS3.php',

  'pgp/keygen' => 'pgp/pgpKeyGen.php',                 'misc/sharedfiles' => 'misc/getSharedFiles.php'
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
