<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace Jet;

/**
 *
 */
class Application_Modules_Exception extends Exception
{

	public const CODE_MODULE_DOES_NOT_EXIST = 2;
	public const CODE_MANIFEST_IS_NOT_READABLE = 3;
	public const CODE_MANIFEST_NONSENSE = 4;


	public const CODE_MODULE_IS_NOT_COMPATIBLE = 99999;

	public const CODE_MODULE_ALREADY_INSTALLED = 5;
	public const CODE_MODULE_IS_NOT_INSTALLED = 6;

	public const CODE_FAILED_TO_INSTALL_MODULE = 7;
	public const CODE_FAILED_TO_UNINSTALL_MODULE = 8;

	public const CODE_ERROR_CREATING_MODULE_INSTANCE = 10;

	public const CODE_UNKNOWN_ACL_ACTION = 5000;
}