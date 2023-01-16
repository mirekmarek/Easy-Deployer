<?php
namespace JetApplication;

use Jet\Debug_ErrorHandler;
use Jet\IO_Dir;
use Jet\IO_Dir_Exception;
use Jet\IO_File;
use Jet\IO_File_Exception;
use Jet\Locale;

abstract class Deployment_Backend_SFTP extends Deployment_Backend
{

	/**
	 * @var resource
	 */
	protected $connection;
	
	/**
	 * @var resource
	 */
	protected $sftp;
	
	public static function isAvailable(): bool
	{
		return extension_loaded( 'ssh2' )
			&& function_exists('ssh2_connect')
			&& function_exists('ssh2_sftp');
	}
	
	abstract public static function getLabel() : string;
	
	public function connect( ?string &$error_message ): bool
	{

		$connected = Debug_ErrorHandler::doItSilent(function() : bool {
			
			
			$hostname = $this->project->getConnectionHost();
			$port = $this->project->getConnectionPort( 22 );

			
			$this->connection = ssh2_connect( $hostname, $port );
			
			if($this->connection) {
				if( $this->connect_auth() ) {
					if($this->sftp = ssh2_sftp($this->connection)) {
						return true;
					}
				}
			}
			
			return false;
		});
		
		
		if(!$connected) {
			$error = Debug_ErrorHandler::getLastError();
			
			$error_message = $error->getMessage();
			
			return false;
		}

		return true;
	}
	
	abstract protected function connect_auth() : bool;
	
	protected function connect_auth_password() : bool
	{
		return ssh2_auth_password(
			session: $this->connection,
			username: $this->project->getConnectionUsername(),
			password: $this->project->getConnectionPassword()
		
		);
	}
	
	protected function connect_auth_key() : bool
	{
		
		return ssh2_auth_pubkey_file(
			session: $this->connection,
			username: $this->project->getConnectionUsername(),
			pubkeyfile: $this->project->getConnectionPublicKeyFilePath(),
			privkeyfile: $this->project->getConnectionPrivateKeyFilePath(),
			passphrase: $this->project->getConnectionPassword()
		);
	}

	public function getList( $dir='.' ): array
	{
		$dir = 'ssh2.sftp://'.$this->sftp.'/'.$dir.'/';
		
		$dh   = opendir($dir);
		
		$list = [];
		while( ($name = readdir($dh)) !== false) {
			if ( str_starts_with( $name, '.' ) ){
				continue;
			}
			
			$is_dir = is_dir( $dir.$name );
			
			if(!$is_dir) {
				$pi = pathinfo($name);
				
				if(!isset($pi['extension'])) {
					$pi['extension'] = '';
				}
				
				$extension = strtolower($pi['extension']);
				
				if(!in_array($extension, $this->project->getAllowedExtensions( true ) )) {
					continue;
				}
				
			}
			
			
			$list[] = [
				'name' => $name,
				'is_dir' => $is_dir,
				'size' => $is_dir ? 0 : filesize( $dir.$name )
			];
		}
		
		closedir($dh);
		
		return $list;
	}

	public function downloadFilesFromDir( string $server_dir, string $local_dir ) : bool
	{
		
		if($this->project->dirIsBlacklisted($server_dir)) {
			return true;
		}
		
		$_server_dir = $this->project->getConnectionBasePath().'/'.$server_dir;

		$this->deployment->prepareEvent('Downloading files from dir %DIR%', ['DIR'=>$_server_dir]);

		if($local_dir!='.') {
			try {
				IO_Dir::create( $local_dir );
			} catch( IO_Dir_Exception $e ) {
				$this->deployment->prepareError('Unable to create directory: %DIR%, %ERROR%', [
					'DIR' => $_server_dir,
					'ERROR' => $e->getMessage()
				]);
				
				return false;
			}
		}
		

		$files = $this->getList( $_server_dir );

		$dirs = array();

		foreach( $files as $d ) {
			$name = $d['name'];
			$is_dir = $d['is_dir'];
			$size = $d['size'];

			if($is_dir) {
				$dirs[] = $name;
			} else {
				
				if($this->project->fileIsBlacklisted($server_dir.'/'.$name)) {
					return true;
				}
				
				$this->deployment->prepareEvent('Downloading file %FILE% (%SIZE%)', [
					'FILE'=>$name,
					'SIZE'=>Locale::size( $size )
				]);

				$local_path = $local_dir.$name;
				$remote_path = $_server_dir.$name;
				
				
				if(!Debug_ErrorHandler::doItSilent(function() use ($local_path, $remote_path) {
					return ssh2_scp_recv(
						session: $this->connection,
						remote_file: $remote_path,
						local_file: $local_path
					);
					
				})) {
					$this->deployment->prepareError('File downloading failed. File: %FILE%, Error: %ERROR%', [
						'FILE' => $remote_path,
						'ERROR' => Debug_ErrorHandler::getLastError()->getMessage()
					]);

					return false;
				}
				
				try {
					IO_File::chmod($local_path);
				} catch(IO_File_Exception $e) {
					$this->deployment->prepareError('Unable to save local file: %FILE%, Error: %ERROR%', [
						'FILE' => $local_path,
						'ERROR' => $e->getMessage()
					]);
					
					return false;
				}

			}
		}


		foreach( $dirs as $name ) {
			$next_local_dir = $local_dir.$name.'/';
			
			if(!$this->downloadFilesFromDir( $server_dir.$name.'/', $next_local_dir )) {
				return false;
			}

		}


		return true;
	}
	
	public function prepare() : bool
	{
		$this->deployment->prepareEvent('Connecting to a server');
		
		
		if(!$this->connect( $error_message )) {
			$this->deployment->prepareError('Unable to connect server: %ERROR%', [
				'ERROR' => $error_message
			]);
			
			return false;
		}
		
		$this->deployment->prepareEvent('Connected ...');
		
		
		$this->deployment->prepareEvent('Downloading started');
		$backup_dir = $this->deployment->getBackupDirPath( true );
		if(!$backup_dir) {
			return false;
		}
		
		if(!$this->downloadFilesFromDir(
			server_dir: '',
			local_dir: $backup_dir
		)) {
			return false;
		}
		
		$this->deployment->prepareEvent('Downloading done');
		
		return true;
	}
	
	protected function getCreateDirMode() : int
	{
		return 0744;
	}
	
	protected function getCreateFileMode() : int
	{
		return 0644;
	}
	
	
	protected function _upload( string $local_base_dir, array $files, callable $logEvent, callable $logError, callable $addUploadedFile ) : bool
	{
		$logEvent('Connecting to a server');
		
		
		if(!$this->connect( $error_message )) {
			$logError('Unable to connect server: %ERROR%', [
				'ERROR' => $error_message
			]);
			
			return false;
		}
		
		$logEvent('Connected ...');

		foreach( $files as $file ) {
			$local_path = $local_base_dir.$file;
			$remote_path = $this->project->getConnectionBasePath().'/'.$file;

			$logEvent('Uploading file: %LOCAL_PATH% -> %REMOTE_PATH%', [
				'LOCAL_PATH' => $local_path,
				'REMOTE_PATH' => $remote_path
			]);

			$dirs = array();

			$dir_name = $file;

			while( ($dir_name = dirname($dir_name)) ) {
				if($dir_name=='.') {
					break;
				}

				$dirs[] = $dir_name;
			}

			if($dirs) {
				foreach( $dirs as $dir ) {
					$created = Debug_ErrorHandler::doItSilent(function() use ($dir) {
						$dir_path = $this->project->getConnectionBasePath().'/'.$dir;
						
						
						if( !file_exists('ssh2.sftp://'.$this->sftp.'/'.$dir_path) ) {
							return ssh2_sftp_mkdir(
								sftp: $this->sftp,
								dirname: $dir_path,
								mode: $this->getCreateDirMode(),
								recursive: true
							);
						} else {
							return true;
						}
					});
					
					if(!$created) {
						$logError('Unable to create target directory %DIR%, Error: %ERROR%', [
							'DIR' => $dir,
							'ERROR' => Debug_ErrorHandler::getLastError()?->getMessage()
						]);
						
						return false;
					}
				}
			}
			
			$addUploadedFile( $file );
			
			$res = Debug_ErrorHandler::doItSilent(function() use ($remote_path, $local_path) {
				return ssh2_scp_send(
					session: $this->connection,
					local_file: $local_path,
					remote_file: $remote_path,
					create_mode: $this->getCreateFileMode()
				);
			});
			
			if(!$res) {
				$logError('File uploading failed! File: %REMOTE_PATH%', [
					'REMOTE_PATH' => $remote_path
				]);
				
				return false;
			}
			
			
			$logEvent('OK');
		}
		
		return true;
	}
	
	public function deploy() : bool
	{
		return $this->_upload(
			local_base_dir: $this->project->getSourceDir(),
			files: $this->deployment->getSelectedFiles(),
			logEvent: function( string $event, array $event_data=[] ) {
				$this->deployment->deployEvent( $event, $event_data );
			},
			logError: function( string $message, array $error_data=[] ) {
				$this->deployment->deployError( $message, $error_data );
			},
			addUploadedFile: function($file) : void {
				$this->deployment->addDeployedFile( $file );
			}
		);
	}
	
	
	public function rollback(): bool
	{
		return $this->_upload(
			local_base_dir: $this->deployment->getBackupDirPath(),
			files: $this->deployment->getRollbackFiles(),
			logEvent: function( string $event, array $event_data=[] ) {
				$this->deployment->rollbackEvent( $event, $event_data );
			},
			logError: function( string $message, array $error_data=[] ) {
				$this->deployment->rollbackError( $message, $error_data );
			},
			addUploadedFile: function($file) : void {
				$this->deployment->addRollbackedFile( $file );
			}
		);
	}
}
