<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication\Installer;

use Jet\Application_Modules;
use Jet\DataModel_Helper;
use Jet\Factory_MVC;
use Jet\Exception;
use Jet\Locale;
use Jet\MVC;
use Jet\SysConf_URI;
use Jet\Tr;
use Jet\Translator;
use Jet\UI_messages;
use JetApplication\Application_Admin;
use JetApplication\Application_Web;
use JetApplication\Application_Web_Config;
use JetApplication\Auth_Administrator_Role;
use JetApplication\Auth_Administrator_Role_Privilege;
use JetApplication\Auth_Administrator_User;
use JetApplication\Auth_Administrator_User_Roles;
use JetApplication\Auth_Developer_Role;
use JetApplication\Auth_Developer_Role_Privilege;
use JetApplication\Auth_Developer_User;
use JetApplication\Auth_Developer_User_Roles;
use JetApplication\Deployment;
use JetApplication\Logger_Admin_Event;
use JetApplication\Logger_Web_Event;
use JetApplication\Project;


/**
 *
 */
class Installer_Step_Install_Controller extends Installer_Step_Controller
{

	/**
	 * @var string
	 */
	protected string $label = 'Installation';


	public function main(): void
	{
		if($this->keys()) {
			if($this->database()) {
				if($this->modules()) {
					if($this->bases()) {
						if($this->mainDeveloperRole()) {
							$this->done();
						}
					}
				}
			}
		}
	}
	
	public function keys() : bool
	{
		$app_conf = new Application_Web_Config();
		
		if(!$app_conf->getDeploymentsDir()) {
			$app_conf->setDeploymentsDir( uniqid().uniqid() );
		}
		
		if(!$app_conf->getEncKey()) {
			$app_conf->setEncKey( uniqid().uniqid() );
		}
		
		try {
			$app_conf->saveConfigFile();
		} catch( \Exception $e ) {
			$this->view->setVar('keys_error',
				UI_messages::createDanger(
					Tr::_( 'Something went wrong: %error%', ['error' => $e->getMessage()], Translator::COMMON_DICTIONARY )
				)->setCloseable(false)
			);
			
			$this->render('keys/error');
			
			return false;
		}
		
		$this->render('keys/done');

		return true;
	}
	
	public function modules() : bool
	{
		$all_modules = Application_Modules::allModulesList();
		
		$this->view->setVar( 'modules', $all_modules );
		
		$result = [];
		
		$OK = true;
		
		foreach( $all_modules as $module ) {
			$module_name = $module->getName();
			$result[$module_name] = true;
			
			if( $all_modules[$module_name]->isActivated() ) {
				continue;
			}
			
			try {
				Application_Modules::installModule( $module_name );
			} catch( Exception $e ) {
				$result[$module_name] = $e->getMessage();
				
				$OK = false;
			}
			
			if( $result[$module_name] !== true ) {
				continue;
			}
			
			try {
				Application_Modules::activateModule( $module_name );
			} catch( Exception $e ) {
				$result[$module_name] = $e->getMessage();
				$OK = false;
			}
			
		}
		
		if( !$result ) {
			Installer::goToNext();
		}
		
		$this->view->setVar( 'result', $result );
		$this->view->setVar( 'OK', $OK );
		
		$this->render( 'modules/installation-result' );

		return $OK;
	}
	
	/**
	 *
	 */
	public function database(): bool
	{
		
		
		$classes = [
			Auth_Administrator_Role::class,
			Auth_Administrator_Role_Privilege::class,
			Auth_Administrator_User::class,
			Auth_Administrator_User_Roles::class,
			
			Auth_Developer_Role::class,
			Auth_Developer_Role_Privilege::class,
			Auth_Developer_User::class,
			Auth_Developer_User_Roles::class,
			
			Logger_Admin_Event::class,
			Logger_Web_Event::class,
			
			Project::class,
			Deployment::class,
		];
		
		$result = [];
		$OK = true;
		
		foreach( $classes as $class ) {
			$result[$class] = true;
			try {
				DataModel_Helper::create( $class );
			} catch( \Exception $e ) {
				$result[$class] = $e->getMessage();
				$OK = false;
			}
			
		}
		
		$this->view->setVar( 'result', $result );
		$this->view->setVar( 'OK', $OK );
		
		$this->render( 'database/creation-result' );
		
		return $OK;
	}
	
	
	public static function basesCreated() : bool
	{
		return count( MVC::getBases() ) == 2;
	}
	
	public function bases(): bool
	{
		
		if( static::basesCreated() ) {
			$this->render( 'bases/done' );
			
			return true;
		}
		
		$default_locale = Installer::getCurrentLocale();
		
		$URL = $_SERVER['HTTP_HOST'] . SysConf_URI::getBase();
		$web = Factory_MVC::getBaseInstance();
		$web->setName( 'Web' );
		$web->setId( Application_Web::getBaseId() );
		$web->setIsSecret( true );
		$web->setIsActive( true );
		$web->setIsDefault( true );
		$web->setInitializer([
			Application_Web::class,
			'init'
		]);
		
		$ld = $web->addLocale( $default_locale );
		$ld->setTitle( Tr::_( 'Easy Deployer', [], null, $default_locale ) );
		$ld->setURLs( [$URL] );
		
		
		
		$admin = Factory_MVC::getBaseInstance();
		$admin->setName( 'Administration' );
		$admin->setId( Application_Admin::getBaseId() );
		$admin->setIsSecret( true );
		$admin->setIsActive( true );
		$admin->setInitializer( [
			Application_Admin::class,
			'init'
		] );
		
		$ld = $admin->addLocale( $default_locale );
		$ld->setTitle( Tr::_( 'Administration', [], null, $default_locale ) );
		$ld->setURLs( [$URL . 'admin'] );
		
		
		
		
		$bases = [
			$web->getId() => $web,
			$admin->getId() => $admin,
		];
		
		$ok = true;
		try {
			foreach( $bases as $base ) {
				$base->saveDataFile();
			}
			
		} catch( Exception $e ) {
			$this->view->setVar('bases_error',
				UI_messages::createDanger(
					Tr::_( 'Something went wrong: %error%', ['error' => $e->getMessage()], Translator::COMMON_DICTIONARY )
				)
			);
			
			$ok = false;
		}
		
		if(!$ok) {
			$this->render( 'bases/error' );
			
			return false;
		}
		
		
		$this->render( 'bases/done' );
		
		usleep(500);
		
		return true;
	}
	
	public function mainDeveloperRole() : bool
	{
		$id = 'main';
		$name = 'Main';
		
		if(!Auth_Developer_Role::idExists('main')) {
			$role = new Auth_Developer_Role();
			$role->setId( $id );
			$role->setName($name);
			
			$locale = Locale::getCurrentLocale();
			$base = Application_Web::getBase();
			
			$homepage = $base->getHomepage( $locale );
			
			$pages = [];
			$pages[] = $homepage->getKey();
			foreach($homepage->getChildren() as $ch) {
				$pages[] = $ch->getKey();
			}
			
			$role->setPrivilege(
				Auth_Developer_Role::PRIVILEGE_VISIT_PAGE,
				$pages
			);
			
			$role->save();
		}
		
		
		return true;
	}
	
	public function done() : void
	{
		
		$this->catchContinue();
		
		$this->render( 'done' );
	}
}
