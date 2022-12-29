<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication;

use Jet\Logger;

use Jet\MVC;
use Jet\MVC_Base_Interface;
use Jet\MVC_Router;

use Jet\Auth;
use Jet\SysConf_Jet_ErrorPages;
use Jet\SysConf_Jet_Form;
use Jet\SysConf_Jet_UI;
use Jet\SysConf_Path;

/**
 *
 */
class Application_Deployer
{
	/**
	 * @return string
	 */
	public static function getBaseId(): string
	{
		return 'deployer';
	}

	/**
	 * @return MVC_Base_Interface
	 */
	public static function getBase(): MVC_Base_Interface
	{
		return MVC::getBase( static::getBaseId() );
	}


	/**
	 * @param MVC_Router $router
	 */
	public static function init( MVC_Router $router ): void
	{
		Logger::setLogger( new Logger_Deployer() );
		Auth::setController( new Auth_Controller_Deployer() );

		SysConf_Jet_UI::setViewsDir( $router->getBase()->getViewsPath() . 'ui/' );
		SysConf_Jet_Form::setDefaultViewsDir( $router->getBase()->getViewsPath() . 'form/' );
		SysConf_Jet_ErrorPages::setErrorPagesDir( $router->getBase()->getPagesDataPath( $router->getLocale() ) );
	}
	
	
	public static function getDeploymentsRootDir() : string
	{
		return SysConf_Path::getBase().'deployments/';
	}
	
	public static function getDeploymentsDir() : string
	{
		return static::getDeploymentsRootDir().Application_Deployer_Config::get()->getDeploymentsDir().'/';
	}
	
}