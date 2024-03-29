<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplicationModule\Deployer\Login;

use Jet\Logger;
use Jet\MVC_Layout;
use Jet\Session;
use Jet\Tr;
use Jet\MVC_Controller_Default;
use Jet\Http_Headers;
use Jet\Auth;

use JetApplication\Auth_Developer_User as User;

/**
 *
 */
class Controller_Main extends MVC_Controller_Default
{
	
	public function login_Action(): void
	{

		MVC_Layout::getCurrentLayout()->setScriptName('auth');
		
		/**
		 * @var Main $module
		 */
		$module = $this->getModule();

		$form = $module->getLoginForm();

		if( $form->catchInput() ) {
			if( $form->validate() ) {
				$data = $form->getValues();
				if( Auth::login( $data['username'], $data['password'] ) ) {
					Session::regenerateId();
					Http_Headers::reload();
				} else {
					$form->setCommonMessage( Tr::_( 'Invalid username or password!' ) );
				}
			} else {
				$form->setCommonMessage( Tr::_( 'Please enter username and password' ) );
			}
		}

		$this->view->setVar( 'login_form', $form );

		$this->output( 'login' );
	}


	/**
	 *
	 */
	public function is_not_activated_Action(): void
	{
		MVC_Layout::getCurrentLayout()->setScriptName('auth');
		
		$this->output( 'is-not-activated' );
	}

	/**
	 *
	 */
	public function is_blocked_Action(): void
	{
		MVC_Layout::getCurrentLayout()->setScriptName('auth');
		
		$this->output( 'is-blocked' );
	}

	/**
	 *
	 */
	public function must_change_password_Action(): void
	{
		MVC_Layout::getCurrentLayout()->setScriptName('auth');
		
		/**
		 * @var Main $module
		 */
		$module = $this->getModule();

		$form = $module->getMustChangePasswordForm();

		if( $form->catchInput() && $form->validate() ) {
			$data = $form->getValues();
			/**
			 * @var User $user
			 */
			$user = Auth::getCurrentUser();

			$user->setPassword( $data['password'] );
			$user->setPasswordIsValid( true );
			$user->setPasswordIsValidTill( null );
			$user->save();

			Logger::info(
				event: 'password_changed',
				event_message: 'User ' . $user->getUsername() . ' (id:' . $user->getId() . ') changed password',
				context_object_id: $user->getId(),
				context_object_name: $user->getUsername()
			);

			Http_Headers::reload();
		}

		$this->view->setVar( 'form', $form );

		$this->output( 'must-change-password' );
	}
}