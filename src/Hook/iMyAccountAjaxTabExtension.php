<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MyAccount\Hook;

/**
 * Interface iMyAccountAjaxTabExtension
 * Define extensibility point to MyAccount screen
 */
interface iMyAccountAjaxTabExtension
{
	/**
	 * True if the tab must be displayed or false if the tab must be hidden.
	 * When the tab is not displayed the other methods are not called.
	 *
	 * @return bool
	 */
	public function IsTabPresent(): bool;

	/**
	 * Unique code for the AjaxTab
	 *
	 * @return string
	 */
	public function GetAjaxTabCode(): string;

	/**
	 * URL to the content of the tab
	 *
	 * @return string
	 */
	public function GetAjaxTabUrl(): string;

	/**
	 * True if the tab is cached or false if the tab must be reloaded each time the user click on it
	 *
	 * @return bool
	 */
	public function GetAjaxTabIsCached(): bool;

	/**
	 * Label of the tab
	 *
	 * @return string
	 */
	public function GetAjaxTabLabel(): string;
}