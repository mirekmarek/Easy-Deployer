<?php
/**
 *
 * @copyright 
 * @license  
 * @author  
 */
namespace JetApplicationModule\Projects\Admin;

use JetApplication\Project as Project;

use Jet\Data_Listing;
use Jet\DataModel_Fetch_Instances;

/**
 *
 */
class Listing extends Data_Listing {


	/**
	 * @var array
	 */
	protected array $grid_columns = [
		'_edit_'     => [
			'title'         => '',
			'disallow_sort' => true
		],
		'code'         => ['title' => 'Code'],
		'name'   => ['title' => 'Project'],
		'notes'   => [
			'title' => 'Notes',
			'disallow_sort' => true
		],
	];

	/**
	 *
	 */
	protected function initFilters(): void
	{
		$this->filters['search'] = new Listing_Filter_Search($this);
	}

	/**
	 * @return Project[]|DataModel_Fetch_Instances
	 */
	protected function getList() : DataModel_Fetch_Instances
	{
		return Project::getList();
	}

}