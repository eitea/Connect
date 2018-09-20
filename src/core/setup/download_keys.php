<?php
/*
* Callees: installation_wizard, header, securitySettings
*/
$content_personal = isset($_POST['personal']) ? $_POST['personal'] : '';
$content_company = isset($_POST['company']) ? $_POST['company'] : '';

$zip = new ZipArchive();
$zip_name = 'keys.zip';
if ($zip->open($zip_name, ZIPARCHIVE::CREATE)) {
    if($content_personal) $zip->addFromString('personal.txt', $content_personal);
    if(is_array($content_company)){
        for($i = 0; $i < count($content_company); $i++)
        $zip->addFromString("company_$i.txt", $content_company[$i]);
    } elseif($content_company) {
        $zip->addFromString('company.txt', $content_company);
    }
    $zip->close();
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-Disposition: attachment; filename=\"".$zip_name."\"");
    clearstatcache();
    header("Content-Length: ".filesize($zip_name));
    readfile($zip_name);
    unlink($zip_name);
} else {
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=keys.txt");
    header("Content-Type: text/plain");
    if(is_array($content_company)) $content_company = implode("\n", $content_company);
    echo $content_personal."\n".$content_company;
}
?>
