<?php
namespace JetApplication;


abstract class Deployment_Backend
{
	protected Deployment $deployment;
	
	protected static array $all_backends = [
		'FTP' => Deployment_Backend_FTP::class
	];
	
	abstract public static function isAvailable() : bool;
	
	abstract public static function getLabel() : string;
	
	public static function getAvailableBackends() : array
	{
		$res  = [];
		
		foreach(static::$all_backends as $backend_type=>$backend_class) {
			/**
			 * @var Deployment_Backend $backend_class
			 */
			if($backend_class::isAvailable()) {
				$res[$backend_type] = $backend_class::getLabel();
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
	}
	
	abstract public function prepare() : bool;
	
	abstract public function deploy() : bool;
	
	abstract public function rollback() : bool;
}
