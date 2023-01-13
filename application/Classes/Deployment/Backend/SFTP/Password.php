<?php
namespace JetApplication;


class Deployment_Backend_SFTP_Password extends Deployment_Backend_SFTP
{
	
	public static function getLabel(): string
	{
		return 'SFTP - password authentication';
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
	
	protected function connect_auth(): bool
	{
		return $this->connect_auth_password();
	}
}