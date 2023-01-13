<?php
/**
 * 
 */

namespace JetApplication;

use Jet\Auth;
use Jet\Data_Text;
use Jet\DataModel;
use Jet\DataModel_Definition;
use Jet\DataModel_Fetch_Instances;
use Jet\DataModel_IDController_Passive;
use Jet\Form;
use Jet\Form_Field;
use Jet\Form_Definition;
use Jet\Form_Field_Input;
use Jet\Form_Field_MultiSelect;
use Jet\IO_Dir;
use Jet\IO_File;

/**
 *
 */
#[DataModel_Definition(
	name: 'project',
	database_table_name: 'projects',
	id_controller_class: DataModel_IDController_Passive::class
)]
class Project extends DataModel
{
	/**
	 * @var ?Form
	 */
	protected ?Form $_form_edit = null;
	
	/**
	 * @var ?Form
	 */
	protected ?Form $_form_add = null;
	
	
	/**
	 * @var string
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Project name:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter project name'
		]
	)]
	protected string $name = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_ID,
		is_id: true
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Project code:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter project code'
		]
	)]
	protected string $code = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Source dir:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter source dir',
			'dir_does_not_exit' => 'The directory does not exist',
			'dir_is_not_readable' => 'The directory is not readable',
		]
	)]
	protected string $source_dir = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 1000
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_TEXTAREA,
		label: 'Allowed file extensions:',
		is_required: false,
		error_messages: [
		]
	)]
	protected string $allowed_extensions = 'php
phtml
js
css';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 65536
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_TEXTAREA,
		label: 'Blacklist:',
		is_required: false,
		error_messages: [
		]
	)]
	protected string $blacklist = '_backup
_profiler
_tools
application/config
application/data
js/packages
css/packages
images
cache
logs
tmp';
	
	protected ?array $_blacklist = null;

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 50
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_SELECT,
		label: 'Connection type:',
		is_required: true,
		select_options_creator: [
			Deployment_Backend::class,
			'getAvailableBackends'
		],
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please select connection type',
			Form_Field::ERROR_CODE_INVALID_VALUE => 'Please select connection type'
		]
	)]
	protected string $connection_type = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Host:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter connection host',
			Form_Field::ERROR_CODE_INVALID_FORMAT => 'Please enter valid host name or IP'
		]
	)]
	protected string $connection_host = '';
	
	/**
	 * @var int
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_INT
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INT,
		label: 'Port:',
		is_required: false,
		error_messages: [
		]
	)]
	protected int $connection_port = 0;

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 50
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Username:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter connection username',
			Form_Field::ERROR_CODE_INVALID_FORMAT => 'Please enter connection username'
		]
	)]
	protected string $connection_username = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		do_not_export: true,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_PASSWORD,
		label: 'Password:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter connection password',
			Form_Field::ERROR_CODE_INVALID_FORMAT => 'Please enter connection password'
		]
	)]
	protected string $connection_password = '';
	
	/**
	 * @var string
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Public key file path:',
		is_required: false,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter public key file path',
			'is_not_readable' => 'Key file does not exist or is not readable',
		]
	)]
	protected string $connection_public_key_file_path = '';
	
	/**
	 * @var string
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Private key file path:',
		is_required: false,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter private key file path',
			'is_not_readable' => 'Key file does not exist or is not readable',
		]
	)]
	protected string $connection_private_key_file_path = '';
	
	/**
	 * @var string
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Local username:',
		is_required: false,
		error_messages: [
		]
	)]
	protected string $connection_local_username = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_INPUT,
		label: 'Connection base path:',
		is_required: true,
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please enter connection base path'
		]
	)]
	protected string $connection_base_path = '/www';
	

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 65536
	)]
	#[Form_Definition(
		type: Form_Field::TYPE_TEXTAREA,
		label: 'Notes:',
		is_required: false,
		error_messages: [
		]
	)]
	protected string $notes = '';
	
	
	#[Form_Definition(
		type: Form_Field::TYPE_MULTI_SELECT,
		label: 'Access of developer roles:',
		is_required: false,
		default_value_getter_name: 'getProjectRoleAccess',
		setter_name: 'setProjectRoleAccess',
		select_options_creator: [
			self::class,
			'getRoles'
		],
		error_messages: [
			Form_Field::ERROR_CODE_EMPTY => 'Please select role',
			Form_Field::ERROR_CODE_INVALID_VALUE => 'Please select role'
		]
	)]
	protected array $project_role_access = [];
	
	
	public static function getRoles() : array
	{
		$list = Auth_Developer_Role::getList();
		
		$res = [];
		foreach($list as $role) {
			$res[$role->getId()] = $role->getName();
		}
		
		return $res;
	}

	/**
	 * @return Form
	 */
	public function getEditForm() : Form
	{
		if(!$this->_form_edit) {
			$this->_form_edit = $this->createForm('edit_form');
			$this->_form_edit->field('code')->setIsReadonly( true );
			$this->_form_edit->field('connection_password')->setIsRequired(false);
			
			$this->setupForm( $this->_form_edit );
		}
		
		return $this->_form_edit;
	}

	/**
	 * @return bool
	 */
	public function catchEditForm() : bool
	{
		return $this->getEditForm()->catch();
	}

	protected function setupForm( Form $form ) : void
	{
		if(!$form->field('code')->getIsReadonly()) {
			$form->field('code')->setValidator(
				function( Form_Field_Input $field ) : bool
				{
					$field->setValue(
						Project::generateCode(
							$field->getValue()
						)
					);
					
					return true;
				}
			);
		}
		
		$form->field('source_dir')->setValidator( function( Form_Field_Input $field ) : bool
		{
			
			$dir = $field->getValue();
			if(!IO_Dir::exists( $dir )) {
				$field->setError('dir_does_not_exit');
				return false;
			}
			
			if(!IO_Dir::isReadable( $dir )) {
				$field->setError('dir_is_not_readable');
				return false;
			}
			
			return true;
		} );
		
		$form->field('connection_port')->setHelpText(
			'Custom TCP port - optional'
		);
		
		$form->field('connection_local_username')->setHelpText(
			'Optional'
		);
		
		$form->field('source_dir')->setHelpText(
			"Full path of project directory located on yours localhost or development server."
		);
		
		$form->field('allowed_extensions')->setHelpText(
			"List of file extensions (*.<b>php</b>, *.<b>phtml</b>, *.<b>css</b>, *.<b>js</b>, ...) that will be handled.<br>"
			."<br>"
			."Other files will be ignored.<br>"
			."<br>"
			."Each file extension on new line"
		);
		
		$form->field('blacklist')->setHelpText(
			"List of <b>relative paths</b> of files and/or directories that will be excluded from deployment process.<br>"
			."<br>"
			."Each relative path on new line"
		);

		$form->field('connection_base_path')->setHelpText(
			"Root directory of project on the production server"
		);
		
		$hasField = function( string $fied_name ) use ($form) : bool {
			$type = $form->getField('connection_type')->getValue();
			$fields = Deployment_Backend::getBackendConnectionEditFormFieldNames( $type );
			
			return in_array( $fied_name, $fields );
		};
		
		$form->field( 'connection_public_key_file_path' )->setValidator(function( Form_Field_Input $field ) use ($hasField) : bool {
			if(!$hasField('connection_public_key_file_path')) {
				return true;
			}
			
			$path = $field->getValue();
			if(!$path) {
				$field->setError( Form_Field::ERROR_CODE_EMPTY );
				return false;
			}
			
			if(
				!IO_File::exists($path) ||
				!IO_File::isReadable($path)
			) {
				$field->setError('is_not_readable');
				return false;
			}
			
			return true;
		});
		$form->field( 'connection_private_key_file_path' )->setValidator(function( Form_Field_Input $field ) use ($hasField) : bool {
			if(!$hasField('connection_private_key_file_path')) {
				return true;
			}
			
			$path = $field->getValue();
			if(!$path) {
				$field->setError( Form_Field::ERROR_CODE_EMPTY );
				return false;
			}
			
			if(
				!IO_File::exists($path) ||
				!IO_File::isReadable($path)
			) {
				$field->setError('is_not_readable');
				return false;
			}

			return true;
		});
		
	}
	
	/**
	 * @return Form
	 */
	public function getAddForm() : Form
	{
		if(!$this->_form_add) {
			$this->_form_add = $this->createForm('add_form');
			$this->setupForm( $this->_form_add );
			
			/**
			 * @var Form_Field_MultiSelect $field
			 */
			$field = $this->_form_add->field('project_role_access');
			$field->setDefaultValue(
				array_keys( $field->getSelectOptions() )
			);
		}
		
		return $this->_form_add;
	}

	/**
	 * @return bool
	 */
	public function catchAddForm() : bool
	{
		return $this->getAddForm()->catch();
	}

	/**
	 * @param int|string $id
	 * @return static|null
	 */
	public static function get( int|string $id ) : static|null
	{
		return static::load( $id );
	}

	/**
	 * @noinspection PhpDocSignatureInspection
	 * @return static[]|DataModel_Fetch_Instances
	 */
	public static function getList() : iterable
	{
		$where = [];
		
		return static::fetchInstances( $where );
	}

	/**
	 * @param string $value
	 */
	public function setCode( string $value ) : void
	{
		$this->code = $value;
		
		if( $this->getIsSaved() ) {
			$this->setIsNew();
		}
		
	}

	/**
	 * @return string
	 */
	public function getCode() : string
	{
		return $this->code;
	}

	/**
	 * @param string $value
	 */
	public function setSourceDir( string $value ) : void
	{
		$value = trim($value);
		$value = str_replace('\\', '/', $value);
		
		$value = rtrim($value, '/');
		
		$value .= '/';
		
		$this->source_dir = $value;
	}

	/**
	 * @return string
	 */
	public function getSourceDir() : string
	{
		return $this->source_dir;
	}
	
	
	protected function _cleanupValue( string|array $value) : string
	{
		if(!is_array($value)) {
			$value = explode("\n", $value);
		}
		
		$_value = $value;
		$value = [];
		foreach($_value as $v) {
			$v = trim($v);
			if($v) {
				$value[] = $v;
			}
		}
		
		if(!$value) {
			return '';
		}
		
		return implode("\n", $value);
	}
	
	
	public function setAllowedExtensions( string|array $value ) : void
	{
		
		$this->allowed_extensions = $this->_cleanupValue($value);
	}

	public function getAllowedExtensions( bool $as_array=false ) : array|string
	{
		if($as_array) {
			return explode("\n", $this->allowed_extensions);
		}
		return $this->allowed_extensions;
	}

	public function setBlacklist( string|array $value ) : void
	{
		$this->blacklist = $this->_cleanupValue($value);
		
		$this->_blacklist = null;
		
		$this->blacklist = implode("\n", $this->getBlacklist());
	}

	public function getBlacklist() : array|string
	{
		if($this->_blacklist===null) {
			$this->_blacklist = [];
			
			$blacklist = explode("\n", $this->blacklist);
			foreach($blacklist as $bl) {
				$bl = trim($bl);
				$bl = str_replace('\\', '/', $bl);
				
				$bl = trim($bl, '/');
				
				if(!$bl) {
					continue;
				}
				
				
				$this->_blacklist[] = $bl;
			}
		}
		
		
		return $this->_blacklist;
	}
	
	public function dirIsBlacklisted( string $dir ) : bool
	{
		$dir = str_replace('\\', '/', $dir);
		$dir = trim($dir, '/');
		
		return in_array($dir, $this->getBlacklist());
	}
	
	public function fileIsBlacklisted( string $path ) : bool
	{
		$path = str_replace('\\', '/', $path);
		$path = trim($path, '/');
		
		return in_array($path, $this->getBlacklist());
	}

	public function setConnectionType( string $value ) : void
	{
		$this->connection_type = $value;
	}

	public function getConnectionType() : string
	{
		return $this->connection_type;
	}

	public function setConnectionHost( string $value ) : void
	{
		$this->connection_host = $value;
	}

	public function getConnectionHost() : string
	{
		return $this->connection_host;
	}
	
	public function setConnectionPort( int $value ) : void
	{
		$this->connection_port = $value;
	}
	
	public function getConnectionPort( int $default_value ) : int
	{
		if(!$this->connection_port) {
			return $default_value;
		}
		return $this->connection_port;
	}
	

	public function setConnectionUsername( string $value ) : void
	{
		$this->connection_username = $value;
	}

	public function getConnectionUsername() : string
	{
		return $this->connection_username;
	}

	public function setConnectionPassword( string $value ) : void
	{
		if($value) {
			
			$key = Application_Deployer_Config::get()->getEncKey();
			
			$iv = openssl_random_pseudo_bytes(
				openssl_cipher_iv_length(Application_Deployer_Config::CIPHER_ALGO)
			
			);
			$tag = '';
			$encrypted = openssl_encrypt(
				data: $value,
				cipher_algo: Application_Deployer_Config::CIPHER_ALGO,
				passphrase: $key,
				iv: $iv,
				tag: $tag
			);
			
			$this->connection_password = base64_encode($encrypted.'|'.$iv.'|'.$tag);
		}
	}

	public function getConnectionPassword() : string
	{
		$key = Application_Deployer_Config::get()->getEncKey();
		
		[$encrypted, $iv, $tag] = explode('|', base64_decode($this->connection_password));
		
		return openssl_decrypt(
			data: $encrypted,
			cipher_algo: Application_Deployer_Config::CIPHER_ALGO,
			passphrase: $key,
			iv:  $iv,
			tag: $tag
		);
	}

	public function setConnectionBasePath( string $value ) : void
	{
		$this->connection_base_path = $value;
	}

	public function getConnectionBasePath() : string
	{
		return $this->connection_base_path;
	}

	public function setName( string $value ) : void
	{
		$this->name = $value;
	}

	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 * @return static[]
	 */
	public static function getListOfCurrentUser() : iterable
	{
		
		$allowed_projects = Auth::getCurrentUser()->getPrivilegeValues( Auth_Developer_Role::PRIVILEGE_USE_PROJECT );
		if(!$allowed_projects) {
			return [];
		}
		
		$list = static::fetchInstances([
			'code' => $allowed_projects
		]);
		$list->getQuery()->setOrderBy(['name']);
		
		return $list;
	}
	
	public function accessAllowed() : bool
	{
		return Auth::getCurrentUser()->hasPrivilege(
			Auth_Developer_Role::PRIVILEGE_USE_PROJECT,
			$this->code
		);
	}
	
	public function getDeployment( int $id ) : ?Deployment
	{
		$deployment = Deployment::get( $id );
		if(
			!$deployment ||
			$deployment->getProjectCode()!=$this->code
		) {
			return null;
		}
		
		return $deployment;
	}
	
	/**
	 * @return Deployment[]
	 */
	public function getDeployments() : iterable
	{
		return Deployment::getListByProject( $this->code );
	}

	/**
	 */
	public function setProjectRoleAccess( array $allowed_roles ) : void
	{
		foreach(Auth_Developer_Role::getList() as $role) {
			$current_allowed_projects = $role->getPrivilegeValues(Auth_Developer_Role::PRIVILEGE_USE_PROJECT);
			$changed = false;
			
			if(in_array($role->getId(), $allowed_roles)) {
				if(!in_array($this->code, $current_allowed_projects)) {
					$current_allowed_projects[] = $this->code;
					$changed = true;
				}
			} else {
				
				$idx = array_search($this->code, $current_allowed_projects);
				if($idx!==false) {
					unset($current_allowed_projects[$idx]);
					$current_allowed_projects = array_values($current_allowed_projects);
					$changed = true;
				}
			}
			
			if($changed) {
				$role->setPrivilege(
					Auth_Developer_Role::PRIVILEGE_USE_PROJECT,
					$current_allowed_projects
				);
				$role->save();
			}
		}
		
	}

	/**
	 */
	public function getProjectRoleAccess() : array
	{
		$res = [];
		
		foreach(Auth_Developer_Role::getList() as $role) {

			if($role->hasPrivilege(
				Auth_Developer_Role::PRIVILEGE_USE_PROJECT,
				$this->code
			)) {
				$res[] = $role->getId();
			}
		}

		return $res;
	}
	
	public static function generateCode( string $name ) : string
	{
		$code = Data_Text::removeAccents($name);
		$code = str_replace( ' ', '-', $code );
		$code = preg_replace( '~(-{2,})~', '-', $code );
		$code = strtolower($code);
		
		$replace = [
			'!',
			'@',
			'#',
			'$',
			'%',
			'^',
			'&',
			'*',
			'(',
			')',
			'+',
			'=',
			'.',
			'\'',
			'"',
			'/',
			'<',
			'>',
			';',
			'?',
			'{',
			'}',
			'[',
			']',
			'|',
		];
		$code = str_replace( $replace, '', $code );
		
		$max_suffix_no = 9999;
		
		
		if( static::codeExists( $code ) ) {
			$_id = substr( $code, 0, 255 - strlen( (string)$max_suffix_no ) );
			
			for( $c = 1; $c <= $max_suffix_no; $c++ ) {
				$code = $_id . $c;
				
				if( !static::codeExists( $code ) ) {
					break;
				}
			}
		}

		return $code;
	}
	
	public static function codeExists( string $code ) : bool
	{
		return (bool)static::dataFetchRow(
			select: ['code'],
			where: ['code'=>$code]
		);
	}

	/**
	 * @param string $value
	 */
	public function setNotes( string $value ) : void
	{
		$this->notes = $value;
	}

	/**
	 * @return string
	 */
	public function getNotes() : string
	{
		return $this->notes;
	}
	
	public function deploymentPrepareAllowed() : bool
	{
		return (
			$this->accessAllowed() &&
			Auth::getCurrentUserHasPrivilege(
				Auth_Developer_Role::PRIVILEGE_ACTION,
				Deployment::ACTION_PREPARE_DEPLOYMENT
			)
		);
	}

	/**
	 * @param string $value
	 */
	public function setConnectionPublicKeyFilePath( string $value ) : void
	{
		$this->connection_public_key_file_path = $value;
	}

	/**
	 * @return string
	 */
	public function getConnectionPublicKeyFilePath() : string
	{
		return $this->connection_public_key_file_path;
	}

	/**
	 * @param string $value
	 */
	public function setConnectionPrivateKeyFilePath( string $value ) : void
	{
		$this->connection_private_key_file_path = $value;
	}

	/**
	 * @return string
	 */
	public function getConnectionPrivateKeyFilePath() : string
	{
		return $this->connection_private_key_file_path;
	}

	/**
	 * @param string $value
	 */
	public function setConnectionLocalUsername( string $value ) : void
	{
		$this->connection_local_username = $value;
	}

	/**
	 * @return string
	 */
	public function getConnectionLocalUsername() : string
	{
		return $this->connection_local_username;
	}
}
