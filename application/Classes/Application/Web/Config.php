<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication;

use Jet\Config;
use Jet\Config_Definition;

#[Config_Definition(
	name: 'deployer'
)]
class Application_Web_Config extends Config {
	
	const CIPHER_ALGO = 'aes-128-gcm';
	
	#[Config_Definition(
		type: Config::TYPE_STRING,
		is_required: true,
	)]
	protected string $enc_key = '';
	
	#[Config_Definition(
		type: Config::TYPE_STRING,
		is_required: true,
	)]
	protected string $deployments_dir = '';
	
	protected static ?Application_Web_Config $config = null;
	
	public function getEncKey(): string
	{
		return $this->enc_key;
	}
	
	public function setEncKey( string $enc_key ): void
	{
		$this->enc_key = $enc_key;
	}
	
	public function getDeploymentsDir(): string
	{
		return $this->deployments_dir;
	}
	
	public function setDeploymentsDir( string $deployments_dir ): void
	{
		$this->deployments_dir = $deployments_dir;
	}
	
	public static function get() : static
	{
		if(!static::$config) {
			static::$config = new static();
		}
		
		return static::$config;
	}
}