<?php
namespace JetApplication;


use Jet\Tr;
use Jet\Translator;

abstract class Deployment_Backend
{
	protected Deployment $deployment;
	
	protected Project $project;
	
	protected static array $all_backends = [
		'FTP' => Deployment_Backend_FTP::class,
		'SFTP_Key' => Deployment_Backend_SFTP_Key::class,
		'SFTP_Password' => Deployment_Backend_SFTP_Password::class
	];
	
	abstract public static function isAvailable() : bool;
	
	abstract public static function getLabel() : string;
	
	abstract protected static function getConnectionEditFormFieldNames() : array;

	public static function getBackendConnectionEditFormFieldNames( string $backend_type ) : array
	{
		return static::$all_backends[$backend_type]::getConnectionEditFormFieldNames();
	}
	
	public static function getAvailableBackends() : array
	{
		$res  = [];
		
		foreach(static::$all_backends as $backend_type=>$backend_class) {
			/**
			 * @var Deployment_Backend $backend_class
			 */
			if($backend_class::isAvailable()) {
				$res[$backend_type] = Tr::_(
					text: $backend_class::getLabel(),
					dictionary: Translator::COMMON_DICTIONARY
				);
			}
		}
		
		return $res;
	}
	
	public static function get( $type, Deployment $deployment ) : Deployment_Backend
	{
		$class = static::$all_backends[$type];
		
		return new $class( $deployment );
	}
	
	public function __construct( Deployment $deployment )
	{
		$this->deployment = $deployment;
		$this->project = $deployment->getProject();
	}
	
	abstract public function prepare() : bool;
	
	abstract public function deploy() : bool;
	
	abstract public function rollback() : bool;
	
}
