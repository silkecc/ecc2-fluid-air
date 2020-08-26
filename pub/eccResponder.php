<?php ini_set('memory_limit', '512M');

/**
 * Comm/data/Responder action standalone file for Magento 2.1
 * @author Epicor.ECC.Team
 * Curl/ fiddler/ HTTP requester URL @url
 * @url: http://ecc.magento2.dev/eccResponder.php
 */

use Magento\Framework\App\Bootstrap;
 
require __DIR__ . '/../app/bootstrap.php';
 
$params = $_SERVER;
 
$bootstrap = Bootstrap::create(BP, $params);
 
$obj = $bootstrap->getObjectManager();
 
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$message_helper = $obj->get('Epicor\Comm\Helper\Messaging');
/* @var $message_helper Epicor_Comm_Helper_Messaging */
$xml = $message_helper->formatXml(trim(file_get_contents('php://input')));

$response = $message_helper->processSingleMessage($xml, true);

if ($response->getIsAuthorized()) {
    $httpStatusCode = 'HTTP/1.1 200 OK';// 200;
} else {
    $httpStatusCode = 'HTTP/1.1 403 Forbidden';// 403;
}

//header('Content-type: text/xml');
header($httpStatusCode);
header('Pragma: public');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Length: '.strlen($response->getXmlResponse()));

echo $response->getXmlResponse();
exit;
