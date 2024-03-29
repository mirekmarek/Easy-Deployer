<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplicationModule\Admin\Projects;

use JetApplication\Project as Project;

use Jet\MVC_View;
use Jet\DataListing;
use Jet\DataModel_Fetch_Instances;

/**
 *
 */
class Listing extends DataListing
{
	
	protected MVC_View $column_view;
	protected MVC_View $filter_view;
	
	public function __construct( MVC_View $column_view, MVC_View $filter_view )
	{
		$this->column_view = $column_view;
		$this->filter_view = $filter_view;
		
		$this->addColumn( new Listing_Column_Edit() );
		$this->addColumn( new Listing_Column_Code() );
		$this->addColumn( new Listing_Column_Name() );
		$this->addColumn( new Listing_Column_ConnectionType() );
		$this->addColumn( new Listing_Column_ConnectionHost() );
		$this->addColumn( new Listing_Column_Notes() );
		
		$this->setDefaultSort( 'name' );
		
		$this->addFilter( new Listing_Filter_Search() );
	}
	
	
	
	protected function getItemList(): DataModel_Fetch_Instances
	{
		return Project::getList();
	}
	
	protected function getIdList(): array
	{
		$ids = Project::fetchIDs( $this->getFilterWhere() );
		$ids->getQuery()->setOrderBy( $this->getQueryOrderBy() );
		
		return $ids->toArray();
	}
	
	public function getFilterView(): MVC_View
	{
		return $this->filter_view;
	}
	
	public function getColumnView(): MVC_View
	{
		return $this->column_view;
	}
	
	public function itemGetter( int|string $id ): ?Project
	{
		return Project::get( $id );
	}
}