<?php

$vatid = $_GET['vatNumber'];
$url = "http://ec.europa.eu/taxation_customs/vies/services/checkVatService";
$vatid = str_replace(array(' ', '.', '-', ',', ', '), '', trim($vatid));

$countryCode = substr($vatid, 0, 2);
$vatNumber = substr($vatid, 2);
$content = "<s11:Envelope xmlns:s11='http://schemas.xmlsoap.org/soap/envelope/'>
  <s11:Body>
    <tns1:checkVat xmlns:tns1='urn:ec.europa.eu:taxud:vies:services:checkVat:types'>
      <tns1:countryCode>%s</tns1:countryCode>
      <tns1:vatNumber>%s</tns1:vatNumber>
    </tns1:checkVat>
  </s11:Body>
</s11:Envelope>";

$ctx = stream_context_create(array(
    'http' => array(
        'method' => 'POST',
        'header' => "Content-Type: text/xml; charset=utf-8; SOAPAction: checkVatService",
        'content' => sprintf($content, $countryCode, $vatNumber),
        'timeout' => 30
        )));
$result = file_get_contents($url, false, $ctx);

$result = str_replace("<soap:Body>", "", $result);
$result = str_replace("</soap:Body>", "", $result);

$response = simplexml_load_string($result)->checkVatResponse;

if ($response->valid == 'true') {
    echo $response->requestDate;
}
?>