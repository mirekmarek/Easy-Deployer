<?php
/**
 * 
 */

namespace JetApplication;

use Jet\Auth;
use Jet\DataModel;
use Jet\DataModel_Definition;
use Jet\DataModel_IDController_AutoIncrement;
use Jet\Data_DateTime;
use Jet\IO_Dir;
use Jet\IO_Dir_Exception;
use Jet\IO_File;
use Jet\Locale;
use Jet\SysConf_Path;
use Jet\Tr;

/**
 *
 */
#[DataModel_Definition(
	name: 'deployment',
	database_table_name: 'deployments',
	id_controller_class: DataModel_IDController_AutoIncrement::class,
	id_controller_options: [
		'id_property_name' => 'id'
	]
)]
class Deployment extends DataModel
{
	const FILE_SEPARATOR = '|';
	
	const STATE_PREPARATION_STARTED = 'preparation_started';
	const STATE_PREPARATION_ERROR = 'preparation_error';
	const STATE_PREPARATION_DONE = 'preparation_done';
	const STATE_DEPLOYMENT_STARTED = 'deployment_started';
	const STATE_DEPLOYMENT_ERROR = 'deployment_error';
	const STATE_DEPLOYMENT_DONE = 'deployment_done';

	/**
	 * @var int
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_ID_AUTOINCREMENT,
		is_id: true
	)]
	protected int $id = 0;

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		is_key: true,
		max_len: 255
	)]
	protected string $project_code = '';
	
	/**
	 * @var Project|null
	 */
	protected ?Project $project = null;

	/**
	 * @var int
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_INT,
		is_key: true
	)]
	protected int $user_id = 0;

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	protected string $user_name = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 50
	)]
	protected string $state = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 255
	)]
	protected string $notes = '';
	
	
	/**
	 * @var ?Data_DateTime
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_DATE_TIME
	)]
	protected ?Data_DateTime $prepare_date_time = null;

	
	/**
	 * @var ?Data_DateTime
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_DATE_TIME
	)]
	protected ?Data_DateTime $start_date_time = null;

	/**
	 * @var ?Data_DateTime
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_DATE_TIME
	)]
	protected ?Data_DateTime $done_date_time = null;

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 9999999
	)]
	protected string $prepare_log = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 999999
	)]
	protected string $deploy_log = '';
	
	/**
	 * @var string
	 */
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 99999
	)]
	protected string $selected_files = '';

	/**
	 * @var string
	 */ 
	#[DataModel_Definition(
		type: DataModel::TYPE_STRING,
		max_len: 99999
	)]
	protected string $deployed_files = '';
	
	protected string $backup_dir_path = '';
	
	protected ?Deployment_Backend $backend = null;
	
	protected ?Deployment_Diff $diff = null;

	/**
	 * @param int|string $id
	 * @return static|null
	 */
	public static function get( int|string $id ) : static|null
	{
		return static::load( $id );
	}

	/**
	 * @return static[]
	 */
	public static function getList() : iterable
	{
		$where = [];
		
		return static::fetchInstances( $where );
	}
	
	/**
	 * @param string $project_code
	 * @return static[]
	 */
	public static function getListByProject( string $project_code ) : iterable
	{
		$where = [
			'project_code' => $project_code
		];
		
		$list = static::fetchInstances( $where );
		$list->getQuery()->setOrderBy(['-id']);
		
		return $list;
	
	}

	/**
	 * @return int
	 */
	public function getId() : int
	{
		return $this->id;
	}

	/**
	 * @param string $value
	 */
	public function setProjectCode( string $value ) : void
	{
		$this->project_code = $value;
	}

	/**
	 * @return string
	 */
	public function getProjectCode() : string
	{
		return $this->project_code;
	}
	
	public function getProject() : ?Project
	{
		if($this->project===null) {
			$this->project = Project::get( $this->project_code );
		}
		
		return $this->project;
	}

	/**
	 * @param int $value
	 */
	public function setUserId( int $value ) : void
	{
		$this->user_id = $value;
	}

	/**
	 * @return int
	 */
	public function getUserId() : int
	{
		return $this->user_id;
	}

	/**
	 * @param string $value
	 */
	public function setUserName( string $value ) : void
	{
		$this->user_name = $value;
	}

	/**
	 * @return string
	 */
	public function getUserName() : string
	{
		return $this->user_name;
	}

	/**
	 * @param string $value
	 */
	public function setState( string $value ) : void
	{
		$this->state = $value;
	}

	/**
	 * @return string
	 */
	public function getState() : string
	{
		return $this->state;
	}
	
	public function getStateLabel() : string
	{
		return $this->state;
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

	/**
	 * @param Data_DateTime|string|null $value
	 */
	public function setStartDateTime( Data_DateTime|string|null $value ) : void
	{
		if( $value===null ) {
			$this->start_date_time = null;
			return;
		}
		
		if( !( $value instanceof Data_DateTime ) ) {
			$value = new Data_DateTime( (string)$value );
		}
		
		$this->start_date_time = $value;
	}

	/**
	 * @return Data_DateTime|null
	 */
	public function getStartDateTime() : Data_DateTime|null
	{
		return $this->start_date_time;
	}

	/**
	 * @param Data_DateTime|string|null $value
	 */
	public function setPrepareDateTime( Data_DateTime|string|null $value ) : void
	{
		if( $value===null ) {
			$this->prepare_date_time = null;
			return;
		}
		
		if( !( $value instanceof Data_DateTime ) ) {
			$value = new Data_DateTime( (string)$value );
		}
		
		$this->prepare_date_time = $value;
	}

	/**
	 * @return Data_DateTime|null
	 */
	public function getPrepareDateTime() : Data_DateTime|null
	{
		return $this->prepare_date_time;
	}

	/**
	 * @param Data_DateTime|string|null $value
	 */
	public function setDoneDateTime( Data_DateTime|string|null $value ) : void
	{
		if( $value===null ) {
			$this->done_date_time = null;
			return;
		}
		
		if( !( $value instanceof Data_DateTime ) ) {
			$value = new Data_DateTime( (string)$value );
		}
		
		$this->done_date_time = $value;
	}

	/**
	 * @return Data_DateTime|null
	 */
	public function getDoneDateTime() : Data_DateTime|null
	{
		return $this->done_date_time;
	}

	/**
	 * @param string $value
	 */
	public function setPrepareLog( string $value ) : void
	{
		$this->prepare_log = $value;
	}

	/**
	 * @return string
	 */
	public function getPrepareLog() : string
	{
		return $this->prepare_log;
	}

	/**
	 * @param string $value
	 */
	public function setDeployLog( string $value ) : void
	{
		$this->deploy_log = $value;
	}

	/**
	 * @return string
	 */
	public function getDeployLog() : string
	{
		return $this->deploy_log;
	}

	public function resetDeployedFiles() : void
	{
		$this->deployed_files = '';
	}
	
	public function getDeployedFiles() : array
	{
		if(!$this->deployed_files) {
			return [];
		}
		
		return explode(static::FILE_SEPARATOR, $this->deployed_files);
	}
	
	public function addDeployedFile( string $path ) : void
	{
		$deployed_files = $this->getDeployedFiles();
		if(!in_array($path, $deployed_files)) {
			$deployed_files[] = $path;
		}
		
		$this->deployed_files = implode(static::FILE_SEPARATOR, $deployed_files);
		$this->save();
	}
	
	public function resetSelectedFiles() : void
	{
		$this->selected_files = '';
	}
	
	
	public function getSelectedFiles() : array
	{
		if(!$this->selected_files) {
			return [];
		}
		
		return explode(static::FILE_SEPARATOR, $this->selected_files);
	}
	
	public function addSelectedFile( string $path ) : void
	{
		$selected_files = $this->getSelectedFiles();
		if(!in_array($path, $selected_files)) {
			$selected_files[] = $path;
		}
		
		$this->selected_files = implode(static::FILE_SEPARATOR, $selected_files);
	}
	
	
	public function removeSelectedFile( string $path ) : void
	{
		$_selected_files = $this->getSelectedFiles();
		$selected_files = [];
		foreach($_selected_files as $f) {
			if($f!=$path) {
				$selected_files[] = $f;
			}
		}
		
		$this->selected_files = implode(static::FILE_SEPARATOR, $selected_files);
	}
	
	
	
	public function getBackupDirPath() : string|bool
	{
		if(!$this->backup_dir_path) {
			$this->backup_dir_path = Application_Web::getDeploymentsDir().$this->project_code.'/'.$this->id.'_'.$this->start_date_time->format('YmdHis').'/';
			

			$this->prepareEvent('Creating backup directory');
			
			try {
				/*
				if(IO_Dir::exists($this->backup_dir_path)) {
					IO_Dir::remove($this->backup_dir_path);
				}
				*/
				
				IO_Dir::create($this->backup_dir_path);
			} catch(IO_Dir_Exception $e ) {
				$this->prepareError('Unable to create directory %DIR%, Error message: %ERROR%', [
					'DIR' => $this->backup_dir_path,
					'ERROR' => $e->getMessage()
				]);
				
				return false;
			}
			
			$this->prepareEvent('Backup directory has been created');
		}
		
		return $this->backup_dir_path;
	}
	
	public function getBackend() : Deployment_Backend
	{
		if( !$this->backend ) {
			$class = __NAMESPACE__.'\Deployment_Backend_'.$this->getProject()->getConnectionType();
			
			$this->backend = new $class( $this );
		}
		
		return $this->backend;
	}
	
	public static function initPreparation( Project $project ) : static
	{
		$user = Auth::getCurrentUser();
		
		$deployment = new static();
		$deployment->setProjectCode( $project->getCode() );
		$deployment->project = $project;
		
		$deployment->setState( static::STATE_PREPARATION_STARTED );
		
		$deployment->user_id = $user->getId();
		$deployment->user_name = $user->getName();
		$deployment->start_date_time = Data_DateTime::now();
		
		$deployment->save();
		
		return $deployment;
	}
	
	public function resetPrepareLog() : void
	{
		$this->prepare_log = '';
	}
	
	public function prepareEvent( string $message, array $event_data=[] ) : void
	{
		$message = Tr::_($message, $event_data);
		
		$this->prepare_log .= '<p class="prepare-event">['.Locale::dateAndTime(Data_DateTime::now()).'] '.$message.'</p>'."\n";
		
		$this->save();
	}
	
	public function prepareError( string $message, array $error_data=[] ) : void
	{
		$message = Tr::_($message, $error_data);
		
		$this->prepare_log .= '<p class="prepare-error">['.Locale::dateAndTime(Data_DateTime::now()).'] '.$message.'</p>'."\n";
		
		$this->save();
	}
	
	
	
	public function prepare() : bool
	{
		if($this->state!=static::STATE_PREPARATION_STARTED) {
			return false;
		}
		set_time_limit(-1);
		
		
		$this->resetDeployedFiles();
		$this->resetSelectedFiles();
		$this->resetPrepareLog();
		$this->save();
		
		if(!$this->getBackend()->prepare()) {
			$this->state = static::STATE_PREPARATION_ERROR;
			$this->save();
			
			return false;
		}

		$this->state = static::STATE_PREPARATION_DONE;
		$this->prepare_date_time = Data_DateTime::now();
		
		$this->save();
		
		
		return true;
	}
	
	public function resetDeployLog() : void
	{
		$this->deploy_log = '';
	}
	
	
	public function deployEvent( string $message, array $event_data=[] ) : void
	{
		$message = Tr::_($message, $event_data);
		
		$this->deploy_log .= '<p class="deploy-event">['.Locale::dateAndTime(Data_DateTime::now()).'] '.$message.'</p>'."\n";
		
		$this->save();
	}
	
	public function deployError( string $message, array $error_data=[] ) : void
	{
		$message = Tr::_($message, $error_data);
		
		$this->deploy_log .= '<p class="deploy-error">['.Locale::dateAndTime(Data_DateTime::now()).'] '.$message.'</p>'."\n";
		
		$this->save();
	}
	
	public function deploy() : bool
	{
		if(
			$this->state!=static::STATE_PREPARATION_DONE ||
			!$this->getSelectedFiles()
		) {
			return false;
		}

		set_time_limit(-1);
		
		$this->state = static::STATE_DEPLOYMENT_STARTED;
		$this->save();
		
		$this->resetDeployLog();
		
		if(!$this->getBackend()->deploy()) {
			$this->state = static::STATE_DEPLOYMENT_ERROR;
			$this->save();
			
			return false;
		}
		
		$this->deployEvent('DONE!');
		
		$this->state = static::STATE_DEPLOYMENT_DONE;
		$this->done_date_time = Data_DateTime::now();
		
		$this->save();
		
		return true;
	}
	
	
	public function retryDeploy() : void
	{
		if($this->state!=static::STATE_DEPLOYMENT_ERROR) {
			return;
		}
		
		$this->state = static::STATE_PREPARATION_DONE;
		$this->resetDeployLog();
		
		$this->save();
		
	}
	
	
	public function currentUserIsOwner() : bool
	{
		return $this->user_id == Auth::getCurrentUser()->getId();
	}
	
	public function rollback() : bool
	{
		//TODO:
		return false;
	}
	
	public function deleteDeployment() : bool
	{
		//TODO:
		
		$this->delete();
		
		return false;
	}
	
	public function prepareAgain() : bool
	{
		if( in_array($this->state, [
			static::STATE_DEPLOYMENT_STARTED,
			static::STATE_DEPLOYMENT_DONE
		]) ) {
			return false;
		}
		
		$this->state = static::STATE_PREPARATION_STARTED;
		$this->start_date_time = Data_DateTime::now();
		$this->resetPrepareLog();
		$this->resetDeployedFiles();
		$this->resetSelectedFiles();
		$this->save();
		
		return true;
	}
	
	
	public function getDiff() : Deployment_Diff
	{
		if(!$this->diff) {
			$this->diff = new Deployment_Diff($this);
		}
		
		return $this->diff;
	}
	
	public function readBackupFile( string $file ) : string
	{
		return str_replace( "\r", "", IO_File::read($this->getBackupDirPath().$file) );
	}
	
	public function readSourceFile( string $file ) : string
	{
		return str_replace( "\r", "", IO_File::read($this->getProject()->getSourceDir().$file) );
	}
	
	public function fileIsSelected( string $file ) : bool
	{
		return in_array($file, $this->getSelectedFiles());
	}
	
	public function unselectFile( string $file ) : void
	{
		if($this->state!=static::STATE_PREPARATION_DONE) {
			return;
		}
		
		$this->removeSelectedFile( $file );
		$this->save();
	}
	
	public function selectFile( string $file ) : void
	{
		if($this->state!=static::STATE_PREPARATION_DONE) {
			return;
		}
		
		$diff = $this->getDiff();
		if(
			!$diff->fileIsChanged( $file ) &&
			!$diff->fileIsNew( $file )
		) {
			return;
		}
		
		$this->addSelectedFile( $file );
		$this->save();
	}
}
