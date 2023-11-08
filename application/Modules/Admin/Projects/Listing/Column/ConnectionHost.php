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

class Listing_Column_ConnectionHost extends DataListing_Column
{
	public const KEY = 'connection_host';
	
	public function getKey(): string
	{
		return static::KEY;
	}
	
	public function getTitle(): string
	{
		return Tr::_('Server');
	}
	
	
}