<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

use Combodo\iTop\MyAccount\Controller\MyAccountController;
use Combodo\iTop\MyAccount\Helper\MyAccountHelper;
use Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension;
use Combodo\iTop\Service\InterfaceDiscovery\InterfaceDiscovery;

require_once(APPROOT.'application/startup.inc.php');

// Get the additional paths
$aAdditionalPaths = [];
foreach (InterfaceDiscovery::GetInstance()->FindItopClasses(iMyAccountTabContentExtension::class) as $sExtensionClass) {
	/** @var \Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension $oExtension */
	$oExtension = new $sExtensionClass();
	$aAdditionalPaths[] = $oExtension->GetTemplatePath();
}

$oController = new MyAccountController(__DIR__.'/templates', MyAccountHelper::MODULE_NAME, $aAdditionalPaths);
$oController->SetDefaultOperation('MainPage');
$oController->HandleOperation();
