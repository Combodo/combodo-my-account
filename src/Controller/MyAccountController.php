<?php

namespace Combodo\iTop\MyAccount\Controller;

use AjaxPage;
use Combodo\iTop\Application\Helper\Session;
use Combodo\iTop\Application\TwigBase\Controller\Controller;
use Combodo\iTop\Application\UI\Base\Component\Button\Button;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\DataTable\StaticTable\FormTable\FormTable;
use Combodo\iTop\Application\UI\Base\Component\DataTable\StaticTable\FormTableRow\FormTableRow;
use Combodo\iTop\Application\UI\Base\Component\Panel\Panel;
use Combodo\iTop\Application\UI\Base\Component\Toolbar\Toolbar;
use Combodo\iTop\Application\UI\Base\Component\Toolbar\ToolbarUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\Object\ObjectDetails;
use Combodo\iTop\Application\UI\Base\Layout\TabContainer\TabContainer;
use Combodo\iTop\Application\UI\Base\UIBlock;
use Combodo\iTop\AuthentToken\Helper\TokenAuthHelper;
use Combodo\iTop\AuthentToken\Helper\TokenAuthLog;
use Combodo\iTop\MyAccount\Helper\MyAccountHelper;
use Combodo\iTop\MyAccount\Hook\iMyAccountExtension;
use Combodo\iTop\Renderer\BlockRenderer;
use DBObject;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use MetaModel;
use UserRights;
use utils;
use const ITOP_DESIGN_LATEST_VERSION;

/**
 * PersonalToken objects are protected and writable only as Administrator
 * Lot of operations are done here as a workaround to bypass the rights to let user handle his own tokens only from MyAccount menu:
 * creation / modification / refresh / deletion
 */
class MyAccountController extends Controller
{
	public const ROUTE_NAMESPACE = MyAccountHelper::ROUTE_NAMESPACE;

	public function OperationMainPage()
	{
		$aParams = [];
		/** @var \User $oUser */
		$oUser = UserRights::GetUserObject();

		$this->ProvideHtmlUserInfo($oUser, $aParams);
		$this->ProvideHtmlContactInfo($oUser, $aParams);

		//adding all below js. some in order to avoid a js console error. which is not functional even when displaying token forms

		if (version_compare(ITOP_DESIGN_LATEST_VERSION, '3.2', '<')) { // N°7251 iTop 3.2.0 deprecated lib
			$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/json.js');
		}
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/forms-json-utils.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/wizardhelper.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/wizard.utils.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/linkswidget.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/linksdirectwidget.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/extkeywidget.js');
		$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().'js/jquery.blockUI.js');

		foreach (TabContainer::DEFAULT_JS_FILES_REL_PATH as $sJsFile) {
			$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().$sJsFile);
		}

		$aJsFilesArrays = [
			UIBlock::DEFAULT_JS_FILES_REL_PATH,
			Panel::DEFAULT_JS_FILES_REL_PATH,
			TabContainer::DEFAULT_JS_FILES_REL_PATH,
			ObjectDetails::DEFAULT_JS_FILES_REL_PATH,
		];

		foreach ($aJsFilesArrays as $aJsFilesArray) {
			foreach ($aJsFilesArray as $sJsRelFile) {
				$this->AddLinkedScript(utils::GetAbsoluteUrlAppRoot().$sJsRelFile);
			}
		}

		$aSections = [];

		foreach (utils::GetClassesForInterface(iMyAccountExtension::class, '', ['[\\\\/]lib[\\\\/]', '[\\\\/]node_modules[\\\\/]', '[\\\\/]test[\\\\/]', '[\\\\/]tests[\\\\/]']) as $sExtensionClass) {
			/** @var \Combodo\iTop\MyAccount\Hook\iMyAccountExtension $oExtension */
			$oExtension = new $sExtensionClass();
			$aSections[] = $oExtension->GetSectionParams();
		}

		$aParams['aSections'] = $aSections;

		$this->DisplayPage(['Params' => $aParams], 'main');
	}

	private function GetEditLink(DBObject $oObject): ?string
	{
		$sClass = get_class($oObject);
		if (\UserRights::IsActionAllowed($sClass, UR_ACTION_MODIFY, \DBObjectSet::FromObject($oObject)) != UR_ALLOWED_YES) {
			return false;
		}

		return sprintf("%spages/UI.php?operation=modify&class=%s&id=%s",
			utils::GetAbsoluteUrlAppRoot(), $sClass, $oObject->GetKey());
	}

	public function ProvideHtmlUserInfo(\User $oUser, &$aParams): void
	{
		if (is_null($oUser)) {
			return;
		}

		$aParams['user_link'] = $this->GetEditLink($oUser);

		$oProfileSet = $oUser->Get('profile_list');
		$aProfiles = [];
		while (($oProfile = $oProfileSet->Fetch()) != null) {
			$aProfiles[] = $oProfile->GetAsHTML('profile');
		}
		$sProfileListHtml = implode('<BR>', $aProfiles);

		$oAllowedOrgList = $oUser->Get('allowed_org_list');
		$aAllowedOrgs = [];
		while (($oUserOrg = $oAllowedOrgList->Fetch()) != null) {
			$aAllowedOrgs[] = $oUserOrg->GetAsHTML('allowed_org_name');
		}
		$sAllowedOrgHtml = implode('<BR>', $aAllowedOrgs);

		$aUserInfo = [
			'login' => null,
			'profile_list' => $sProfileListHtml,
			'org_id' => null,
			'allowed_org_list' => $sAllowedOrgHtml,
		];

		$this->ConvertToHtml($aParams, $aUserInfo, 'user', $oUser);
	}

	public function ProvideHtmlContactInfo(\User $oUser, &$aParams): void
	{
		if (is_null($oUser)) {
			return;
		}

		$iPersonId = $oUser->Get('contactid');
		if (0 === $iPersonId) {
			return;
		}

		$oPerson = MetaModel::GetObject('Person', $iPersonId);

		$aParams['contact_link'] = $this->GetEditLink($oPerson);
		$aContactInfo = [
			'first_name' => null,
			'name' => null,
			'email' => null,
			'phone' => null,
			'location_name' => null,
		];

		$aParams['contact']['picture'] = UserRights::GetUserPictureAbsUrl($oUser->Get('login'));//$oPerson->GetAsHTML('picture');
		$this->ConvertToHtml($aParams, $aContactInfo, 'contact', $oPerson);
	}

	public function ConvertToHtml(&$aParams, $aData, $sKey, DBObject $oObject)
	{
		foreach ($aData as $sAttCode => $sAttHtml) {
			if ($sAttHtml) {
				$aParams[$sKey][MetaModel::GetLabel(get_class($oObject), $sAttCode)] = $sAttHtml;
			} else {
				$aParams[$sKey][MetaModel::GetLabel(get_class($oObject), $sAttCode)] = $oObject->GetAsHTML($sAttCode);
			}
		}
	}

}
