<?php
namespace JetApplication;


class Deployment_Backend_SFTP_Key extends Deployment_Backend_SFTP
{
	
	public static function getLabel(): string
	{
		return 'SFTP - key authentication';
	}
	
	protected static function getConnectionEditFormFieldNames() : array
	{
		return [
			'connection_host',
			'connection_port',
			'connection_username',
			'connection_password',
			
			'connection_public_key_file_path',
			'connection_private_key_file_path',
			//'connection_local_username',
			
			'connection_base_path',
		];
	}
	
	protected function connect_auth(): bool
	{
		return $this->connect_auth_key();
	}
}