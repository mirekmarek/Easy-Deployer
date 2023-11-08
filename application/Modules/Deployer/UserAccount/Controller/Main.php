<?php
/**
 *
 * @copyright 
 * @license  
 * @author  
 */
namespace JetApplicationModule\Deployer\UserAccount;

use Jet\Auth;
use Jet\Http_Headers;
use Jet\Http_Request;
use Jet\Logger;
use Jet\MVC;
use Jet\MVC_Controller_Default;
use Jet\Tr;
use Jet\UI_messages;
use JetApplication\Auth_Administrator_User as User;


/**
 *
 */
class Controller_Main extends MVC_Controller_Default
{

	/**
	 *
	 */
	public function default_Action() : void
	{
		$GET = Http_Request::GET();
		if($GET->exists('logout')) {
			Auth::logout();
			
			Http_Headers::movedTemporary( MVC::getHomePage()->getURL() );
		}
		
		/**
		 * @var Main $module
		 */
		$module = $this->getModule();
		
		$form = $module->getChangePasswordForm();
		
		
		if( $form->catchInput() && $form->validate() ) {
			$data = $form->getValues();
			/**
			 * @var User $user
			 */
			$user = Auth::getCurrentUser();
			
			if( !$user->verifyPassword( $data['current_password'] ) ) {
				UI_messages::danger( Tr::_( 'Current password do not match' ) );
			} else {
				
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
				
				UI_messages::success( Tr::_( 'Your password has been changed' ) );
			}
			
			
			Http_Headers::reload();
		}
		
		$this->view->setVar( 'form', $form );
		
		
		$this->output('default');
	}
}