<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication\Installer;

use Jet\Locale;
use JetApplication\Application_Web;
use JetApplication\Auth_Administrator_User;
use JetApplication\Auth_Developer_Role;

/**
 *
 */
class Installer_Step_CreateAdministrator_Controller extends Installer_Step_Controller
{

	/**
	 * @var string
	 */
	protected string $label = 'Create administrator account';

	/**
	 *
	 */
	public function main(): void
	{
		$this->catchContinue();

		if( count( Auth_Administrator_User::getList() ) > 0 ) {

			$this->render( 'created' );
		} else {

			$administrator = new Auth_Administrator_User();
			$form = $administrator->getRegistrationForm();

			$form->getField( 'username' )->setDefaultValue( 'admin' );


			$administrator->setLocale( Installer::getCurrentLocale() );

			$this->view->setVar( 'form', $form );


			if( $form->catch() ) {
				$administrator->setIsSuperuser( true );
				$administrator->save();
				
				$this->createMainDeveloperRole();

				Installer::goToNext();
			}

			$this->render( 'default' );
		}

	}
	
	public function createMainDeveloperRole() : bool
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
	
}
