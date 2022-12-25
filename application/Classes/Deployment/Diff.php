<?php

namespace JetApplication;

use Jet\IO_Dir;

class Deployment_Diff
{
	protected Deployment $deployment;
	
	protected Project $project;
	
	protected string $source_dir = '';
	
	protected string $backup_dir = '';
	
	protected array $allowed_extensions = [];
	
	protected array $remote_files_list = [];
	
	protected array $local_files_list = [];
	
	protected array $to_compare_files_list = [];
	
	
	protected array $obsolete_files = [];
	
	protected array $new_files = [];
	
	protected array $changed_files = [];
	
	
	public function __construct( Deployment $deployment )
	{
		$this->deployment = $deployment;
		$this->project = $deployment->getProject();
		
		$this->source_dir = $this->project->getSourceDir();
		$this->backup_dir = $this->deployment->getBackupDirPath();
		$this->allowed_extensions = $this->project->getAllowedExtensions(true);
		
		$this->process();
	}
	
	protected function process() : void
	{
		$this->_getList( $this->backup_dir, '', $this->remote_files_list );
		$this->_getList( $this->source_dir, '', $this->local_files_list );
		
		
		foreach( $this->local_files_list as $file ) {
			if( !in_array( $file, $this->remote_files_list ) ) {
				$this->new_files[] = $file;
			} else {
				$this->to_compare_files_list[] = $file;
			}
		}
		
		foreach( $this->remote_files_list as $file ) {
			if( !in_array( $file, $this->local_files_list ) ) {
				$this->obsolete_files[] = $file;
			}
		}
		

		foreach( $this->to_compare_files_list as $file ) {
			$source_md5 = md5( $this->deployment->readSourceFile( $file ) );
			$backup_md5 = md5( $this->deployment->readBackupFile( $file ) );
			
			if( $source_md5 != $backup_md5 ) {
				$this->changed_files[] = $file;
			}
			
		}
		
	}
	
	protected function _getList( string $base_dir, string $dir, array &$result )
	{
		
		if( $this->project->dirIsBlacklisted( $dir ) ) {
			return;
		}
		
		$files = IO_Dir::getFilesList( $base_dir . $dir );
		
		foreach( $files as $file ) {
			$path_info = pathinfo( $file );
			$ext = isset( $path_info['extension'] ) ? strtolower( $path_info['extension'] ) : '';
			
			if( !in_array( $ext, $this->allowed_extensions ) ) {
				continue;
			}
			
			$path = $dir . $file;
			
			if( $this->project->fileIsBlacklisted($path) ) {
				continue;
			}
			
			$result[] = $path;
		}
		
		$dirs = IO_Dir::getSubdirectoriesList( $base_dir . $dir );

		foreach( $dirs as $sub_dir ) {
			$this->_getList( $base_dir, $dir . $sub_dir . '/', $result );
		}
	}
	
	public function getObsoleteFiles(): array
	{
		return $this->obsolete_files;
	}
	
	public function getNewFiles(): array
	{
		return $this->new_files;
	}
	
	public function getChangedFiles(): array
	{
		return $this->changed_files;
	}
	
	public function getToUploadFilesList(): array
	{
		return array_merge(
			$this->getNewFiles(),
			$this->getChangedFiles()
		);
	}
	
	public function fileIsChanged( string $file ) : bool
	{
		return in_array( $file, $this->changed_files );
	}
	
	public function fileIsNew( string $file ) : bool
	{
		return in_array( $file, $this->new_files );
	}
}