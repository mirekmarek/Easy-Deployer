<?php
/**
 *
 * @copyright
 * @license
 * @author  Miroslav Marek
 */
namespace JetApplicationModule\Admin\Projects;

use Jet\DataListing_Column;
use Jet\Tr;

class Listing_Column_ConnectionType extends DataListing_Column
{
	public const KEY = 'connection_type';
	
	public function getKey(): string
	{
		return static::KEY;
	}
	
	public function getTitle(): string
	{
		return Tr::_('Connection type');
	}
	
	
}