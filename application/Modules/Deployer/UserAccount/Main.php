<?php
/**
 *
 * @copyright 
 * @license  
 * @author  
 */
namespace JetApplicationModule\Deployer\UserAccount;

use Jet\Application_Module;
use Jet\Form;
use Jet\Form_Field;
use Jet\Form_Field_Password;
use JetApplication\Auth_Administrator_User as Administrator;

/**
 *
 */
class Main extends Application_Module
{
	public function getChangePasswordForm() : Form
	{
		$current_password = new Form_Field_Password( 'current_password', 'Current password' );
		$current_password->setIsRequired( true );
		$current_password->setErrorMessages(
			[
				Form_Field::ERROR_CODE_EMPTY => 'Please enter new password',
			
			]
		);
		
		$new_password = new Form_Field_Password( 'password', 'New password' );
		$new_password->setIsRequired( true );
		$new_password->setErrorMessages(
			[
				Form_Field::ERROR_CODE_EMPTY         => 'Please enter new password',
				Form_Field::ERROR_CODE_WEAK_PASSWORD => 'Password is not strong enough',
			]
		);
		
		$new_password->setValidator( function( Form_Field_Password $field ) : bool {
			if(!Administrator::verifyPasswordStrength($field->getValue())) {
				$field->setError( Form_Field::ERROR_CODE_WEAK_PASSWORD);
				return false;
			}
			
			return true;
		} );
		
		
		$new_password_check = $new_password->generateCheckField(
			field_name: 'password_check',
			field_label: 'Confirm new password',
			error_message_empty: 'Please confirm new password',
			error_message_not_match: 'Password confirmation do not match'
		);
		
		
		return new Form(
			'change_password', [
				$current_password,
				$new_password,
				$new_password_check,
			]
		);
	}

}