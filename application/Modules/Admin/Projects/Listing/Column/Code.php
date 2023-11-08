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

class Listing_Column_Code extends DataListing_Column
{
	public const KEY = 'code';
	
	public function getKey(): string
	{
		return static::KEY;
	}
	
	public function getTitle(): string
	{
		return Tr::_('Code');
	}
	
	
}