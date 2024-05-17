<?php
/**
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\MyAccount\Hook;

interface iMyAccountExtension
{
	/**
	 * Get the parameters to be passed to the MyAccount screen
	 *
	 * @return array contains 'sHtmlTwig' => the path to the HTML twig section for this extension (extension-name/templates/file.html.twig)
	 *                        'sReadyJsTwig' => the path to the JS twig section for this extension
	 * and all other parameters that the extension needs to render its content,
	 * they will arrive in the templates as "Section" variable
	 */
	public function GetSectionParams(): array;

	public function GetTemplatePath(): string;

}