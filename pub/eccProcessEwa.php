<?php
/**
 * ECC EWA Application Bootstrap
 *
 * @category   Epicor
 * @package    Epicor_Comm
 * @author     Epicor Websales Team
 */

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$object_Manager = $bootstrap->getObjectManager();

$app_state = $object_Manager->get('\Magento\Framework\App\State');
$app_state->setAreaCode('frontend');


/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('\Epicor\Comm\Model\EccEwaApp');
$bootstrap->run($app);
