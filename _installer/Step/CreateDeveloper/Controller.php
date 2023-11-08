<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication\Installer;

use Jet\Application_Modules;
use Jet\Locale;
use JetApplication\Application_Deployer;
use JetApplication\Auth_Developer_User;
use JetApplication\Auth_Developer_Role;

/**
 *
 */
class Installer_Step_CreateDeveloper_Controller extends Installer_Step_Controller
{

	const MAIN_ROLE_ID = 'main';
	const MAIN_ROLE_NAME = 'Main';
	
	/**
	 * @var string
	 */
	protected string $label = 'Create developer account';

	/**
	 *
	 */
	public function main(): void
	{
		$this->createMainDeveloperRole();
		
		$this->catchContinue();

		if( count( Auth_Developer_User::getList() ) > 0 ) {

			$this->render( 'created' );
		} else {

			$developer = new Auth_Developer_User();
			$form = $developer->getRegistrationForm();
			
			$developer->setLocale( Installer::getCurrentLocale() );

			$this->view->setVar( 'form', $form );


			if( $form->catch() ) {
				$developer->save();
				$developer->setRoles([static::MAIN_ROLE_ID]);

				Installer::goToNext();
			}

			$this->render( 'default' );
		}

	}
	
	public function createMainDeveloperRole() : void
	{
		$id = static::MAIN_ROLE_ID;
		$name = static::MAIN_ROLE_NAME;
		
		if( Auth_Developer_Role::idExists( $id ) ) {
			return;
		}
		
		$role = new Auth_Developer_Role();
		$role->setId( $id );
		$role->setName($name);
		
		$locale = Locale::getCurrentLocale();
		$base = Application_Deployer::getBase();
		
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
		
		$module_manifest = Application_Modules::moduleManifest('Deployer.Projects');
		
		$role->setPrivilege(
			Auth_Developer_Role::PRIVILEGE_ACTION,
			array_keys( $module_manifest->getACLActions( false ) )
		);
		
		$role->save();
	}
	
}
