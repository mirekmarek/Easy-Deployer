<?php
namespace JetApplication;


abstract class Deployment_Backend
{
	protected Deployment $deployment;
	
	
	public function __construct( Deployment $deployment )
	{
		$this->deployment = $deployment;
	}
	
	abstract public function prepare() : bool;
	
	abstract public function deploy() : bool;
}
