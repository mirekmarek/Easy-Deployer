<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplicationModule\Projects\Admin;

use JetApplication\Project as Project;

use Jet\Data_Listing;
use Jet\DataModel_Fetch_Instances;

/**
 *
 */
class Listing extends Data_Listing
{
	
	
	/**
	 * @var array
	 */
	protected array $grid_columns = [
		'_edit_' => [
			'title'         => '',
			'disallow_sort' => true
		],
		'code'   => ['title' => 'Code'],
		'name'   => ['title' => 'Project'],
		'connection_type' => [
			'title' => 'Connection type',
		],
		'connection_host' => [
			'title' => 'Server',
		],
		'notes'  => [
			'title'         => 'Notes',
			'disallow_sort' => true
		],
	];
	
	/**
	 *
	 */
	protected function initFilters(): void
	{
		$this->filters['search'] = new Listing_Filter_Search( $this );
	}
	
	/**
	 * @return Project[]|DataModel_Fetch_Instances
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function getList(): DataModel_Fetch_Instances
	{
		return Project::getList();
	}
	
}