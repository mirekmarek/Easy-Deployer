<?php
/**
 *
 * @copyright
 * @license
 * @author  Miroslav Marek
 */
namespace JetApplicationModule\Admin\EventViewer\Deployer;

use Jet\DataListing_Column;
use Jet\Tr;
use JetApplication\Logger_Admin_Event as Event;

class Listing_Column_ContextObjectName extends DataListing_Column
{
	public const KEY = 'context_object_name';
	
	public function getKey(): string
	{
		return static::KEY;
	}
	
	public function getTitle(): string
	{
		return Tr::_('Context object name');
	}
	
	public function getExportHeader(): string
	{
		return $this->getKey();
	}
	
	/**
	 * @param Event $item
	 * @return string
	 */
	public function getExportData( mixed $item ): string
	{
		return $item->getContextObjectName();
	}
}