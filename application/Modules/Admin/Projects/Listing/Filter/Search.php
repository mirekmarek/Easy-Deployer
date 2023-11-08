<?php
/**
 *
 * @copyright 
 * @license  
 * @author  
 */

namespace JetApplicationModule\Admin\Projects;


use Jet\DataListing_Filter_Search;

class Listing_Filter_Search extends DataListing_Filter_Search {
	
	public const KEY = 'search';
	
	public function getKey(): string
	{
		return static::KEY;
	}
	
	public function generateWhere(): void
	{
		if($this->search) {
			$search = '%'.$this->search.'%';
			$this->listing->addFilterWhere([
				'name *'   => $search,
			]);
		}
	}
}