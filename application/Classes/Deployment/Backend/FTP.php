<?php
namespace JetApplication;

use FTP\Connection as FTP_Connection;
use Jet\Debug_ErrorHandler;
use Jet\IO_Dir;
use Jet\IO_Dir_Exception;
use Jet\IO_File;
use Jet\IO_File_Exception;
use Jet\Locale;

class Deployment_Backend_FTP extends Deployment_Backend
{

	/**
	 * @var resource|FTP_Connection
	 */
	protected $connection;
	
	public static function isAvailable(): bool
	{
		return extension_loaded( 'ftp' );
	}
	
	public static function getLabel() : string
	{
		return 'FTP';
	}
	
	protected static function getConnectionEditFormFieldNames() : array
	{
		return [
			'connection_host',
			'connection_port',
			'connection_username',
			'connection_password',
			'connection_base_path',
		];
	}
	
	public function connect( ?string &$error_message ): bool
	{

		$connected = Debug_ErrorHandler::doItSilent(function() : bool {
			
			$hostname = $this->project->getConnectionHost();
			$port = $this->project->getConnectionPort( 21 );
			
			if( str_contains( $hostname, ':' ) ) {
				[$hostname, $port] = explode(':', $hostname);
			}
			
			$this->connection = ftp_connect( $hostname, $port );
			
			if($this->connection) {
				if( ftp_login(
					$this->connection,
					$this->project->getConnectionUsername(),
					$this->project->getConnectionPassword()
				) ) {
					if(ftp_pasv( $this->connection, true )) {
						if(ftp_chdir( $this->connection, $this->project->getConnectionBasePath() )) {
							return true;
						}
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

	public function getList( $dir='.' ): array
	{
		$raw= ftp_rawlist( $this->connection, $dir, false );


		$list = array();
		foreach( $raw as $l ) {

			if(!preg_match_all('/^([drwxs+-]{10})\s+(\d+)\s+(\w+)\s+(\w+)\s+(\d+)\s+(.{12}) (.*)$/m', $l, $matches, PREG_SET_ORDER)) {
				//var_dump( $l );
				continue;
			}

			$params = $matches[0][1];
			$size = $matches[0][5];
			$name = $matches[0][7];

			if($name=='.' || $name=='..') {
				continue;
			}

			$is_dir = $params[0]=='d';

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
				'size' => $size
			];
		}


		return $list;
	}

	public function downloadFilesFromDir( string $ftp_dir, string $local_dir ) : bool
	{
		
		if($this->project->dirIsBlacklisted($ftp_dir)) {
			return true;
		}
		
		$_ftp_dir = $this->project->getConnectionBasePath().'/'.$ftp_dir;

		$this->deployment->prepareEvent('Downloading files from dir %DIR%', ['DIR'=>$_ftp_dir]);

		if($local_dir!='.') {
			try {
				IO_Dir::create( $local_dir );
			} catch( IO_Dir_Exception $e ) {
				$this->deployment->prepareError('Unable to create directory: %DIR%, %ERROR%', [
					'DIR' => $_ftp_dir,
					'ERROR' => $e->getMessage()
				]);
				
				return false;
			}
		}

		if(!Debug_ErrorHandler::doItSilent( function() use ($_ftp_dir) {
			return ftp_chdir( $this->connection, $_ftp_dir );
		} )) {
			$this->deployment->prepareError('Unable to change FTP directory: %DIR%, %ERROR%', [
				'DIR' => $_ftp_dir,
				'ERROR' => Debug_ErrorHandler::getLastError()->getMessage()
			]);
			
			return false;
		}
		
		

		$files = $this->getList();

		$dirs = array();

		foreach( $files as $d ) {
			$name = $d['name'];
			$is_dir = $d['is_dir'];
			$size = $d['size'];

			if($is_dir) {
				$dirs[] = $name;
			} else {
				
				if($this->project->fileIsBlacklisted($ftp_dir.'/'.$name)) {
					return true;
				}
				
				$this->deployment->prepareEvent('Downloading file %FILE% (%SIZE%)', [
					'FILE'=>$name,
					'SIZE'=>Locale::size( $size )
				]);

				$local_path = $local_dir.$name;
				
				
				if(!Debug_ErrorHandler::doItSilent(function() use ($local_path, $name) {
					return ftp_get( $this->connection, $local_path, $name, FTP_BINARY );
				})) {
					$this->deployment->prepareError('File downloading failed. File: %FILE%, Error: %ERROR%', [
						'FILE' => $this->project->getConnectionBasePath().'/'.$ftp_dir.'/'.$name,
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
			
			if(!$this->downloadFilesFromDir( $ftp_dir.$name.'/', $next_local_dir )) {
				return false;
			}

		}


		return true;
	}
	
	public function prepare() : bool
	{
		$this->deployment->prepareEvent('Connecting to a FTP');
		
		
		if(!$this->connect( $error_message )) {
			$this->deployment->prepareError('Unable to connect FTP: %ERROR%', [
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
			ftp_dir: '',
			local_dir: $backup_dir
		)) {
			return false;
		}
		
		$this->deployment->prepareEvent('Downloading done');
		
		return true;
	}
	
	
	protected function _upload( string $local_base_dir, array $files, callable $logEvent, callable $logError, callable $addUploadedFile ) : bool
	{
		$logEvent('Connecting to a FTP');
		
		
		if(!$this->connect( $error_message )) {
			$logError('Unable to connect FTP: %ERROR%', [
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
						
						
						if(!ftp_chdir( $this->connection, $this->project->getConnectionBasePath() )) {
							return false;
						}
						
						$sub_dirs = explode('/',$dir);
						
						foreach($sub_dirs as $sub_dir){
							if(!ftp_chdir($this->connection, $sub_dir)){
								if(!ftp_mkdir($this->connection, $sub_dir)) {
									return false;
								}
								ftp_chdir($this->connection, $sub_dir);
							}
						}
						
						if(!ftp_chdir($this->connection, $this->project->getConnectionBasePath())) {
							return false;
						}
						
						return true;
					});
					
					if(!$created) {
						$logError('Unable to create target directory %DIR%, Error: %ERROR%', [
							'DIR' => $dir,
							'ERROR' => Debug_ErrorHandler::getLastError()->getMessage()
						]);
						
						return false;
					}
				}
			}
			
			$addUploadedFile( $file );
			
			$res = Debug_ErrorHandler::doItSilent(function() use ($remote_path, $local_path) {
				return ftp_put( $this->connection, $remote_path, $local_path, FTP_BINARY );
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
