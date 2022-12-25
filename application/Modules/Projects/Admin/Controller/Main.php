<?php
/**
 *
 * @copyright 
 * @license  
 * @author  
 */
namespace JetApplicationModule\Projects\Admin;

use Jet\AJAX;
use JetApplication\Project as Project;

use Jet\MVC_Controller_Router_AddEditDelete;
use Jet\MVC_Controller_Default;
use Jet\UI_messages;
use Jet\Http_Headers;
use Jet\Http_Request;
use Jet\Tr;
use Jet\Navigation_Breadcrumb;
use Jet\Logger;

/**
 *
 */
class Controller_Main extends MVC_Controller_Default
{

	/**
	 * @var ?MVC_Controller_Router_AddEditDelete
	 */
	protected ?MVC_Controller_Router_AddEditDelete $router = null;

	/**
	 * @var ?Project
	 */
	protected ?Project $project = null;

	/**
	 *
	 * @return MVC_Controller_Router_AddEditDelete
	 */
	public function getControllerRouter() : MVC_Controller_Router_AddEditDelete
	{
		if( !$this->router ) {
			$this->router = new MVC_Controller_Router_AddEditDelete(
				$this,
				function($id) {
					return (bool)($this->project = Project::get($id));
				},
				[
					'listing'=> Main::ACTION_GET_PROJECT,
					'view'   => Main::ACTION_GET_PROJECT,
					'add'    => Main::ACTION_ADD_PROJECT,
					'edit'   => Main::ACTION_UPDATE_PROJECT,
					'delete' => Main::ACTION_DELETE_PROJECT,
				]
			);
			
			$this->router->addAction('generate_code')
				->setResolver(function() {
					return (bool)Http_Request::GET()->getString('generate_code');
				});
		}

		return $this->router;
	}


	/**
	 *
	 */
	public function listing_Action() : void
	{
		$listing = new Listing();
		$listing->handle();

		$this->view->setVar( 'filter_form', $listing->getFilterForm());
		$this->view->setVar( 'grid', $listing->getGrid() );

		$this->output( 'list' );
	}

	/**
	 *
	 */
	public function add_Action() : void
	{
		Navigation_Breadcrumb::addURL( Tr::_( 'Create a new Project' ) );

		$project = new Project();


		$form = $project->getAddForm();

		if( $project->catchAddForm() ) {
			$project->save();

			Logger::success(
				event: 'project_created',
				event_message: 'Project created',
				context_object_id: $project->getCode(),
				context_object_name: $project->getName(),
				context_object_data: $project
			);


			UI_messages::success(
				Tr::_( 'Project <b>%ITEM_NAME%</b> has been created', [ 'ITEM_NAME' => $project->getName() ] )
			);

			Http_Headers::reload( ['id'=>$project->getCode()], ['action'] );
		}

		$this->view->setVar( 'form', $form );
		$this->view->setVar( 'project', $project );

		$this->output( 'edit' );

	}

	/**
	 *
	 */
	public function edit_Action() : void
	{
		$project = $this->project;

		Navigation_Breadcrumb::addURL( Tr::_( 'Edit project <b>%ITEM_NAME%</b>', [ 'ITEM_NAME' => $project->getName() ] ) );

		$form = $project->getEditForm();

		if( $project->catchEditForm() ) {

			$project->save();

			Logger::success(
				event: 'project_updated',
				event_message: 'Project updated',
				context_object_id: $project->getCode(),
				context_object_name: $project->getName(),
				context_object_data: $project
			);

			UI_messages::success(
				Tr::_( 'Project <b>%ITEM_NAME%</b> has been updated', [ 'ITEM_NAME' => $project->getName() ] )
			);

			Http_Headers::reload();
		}

		$this->view->setVar( 'form', $form );
		$this->view->setVar( 'project', $project );

		$this->output( 'edit' );

	}

	/**
	 *
	 */
	public function view_Action() : void
	{
		$project = $this->project;

		Navigation_Breadcrumb::addURL(
			Tr::_( 'Project detail <b>%ITEM_NAME%</b>', [ 'ITEM_NAME' => $project->getName() ] )
		);

		$form = $project->getEditForm();

		$form->setIsReadonly();

		$this->view->setVar( 'form', $form );
		$this->view->setVar( 'project', $project );

		$this->output( 'edit' );

	}

	/**
	 *
	 */
	public function delete_Action() : void
	{
		$project = $this->project;

		Navigation_Breadcrumb::addURL(
			Tr::_( 'Delete project  <b>%ITEM_NAME%</b>', [ 'ITEM_NAME' => $project->getName() ] )
		);

		if( Http_Request::POST()->getString( 'delete' )=='yes' ) {
			$project->delete();

			Logger::success(
				event: 'project_deleted',
				event_message: 'Project deleted',
				context_object_id: $project->getCode(),
				context_object_name: $project->getName(),
				context_object_data: $project
			);

			UI_messages::info(
				Tr::_( 'Project <b>%ITEM_NAME%</b> has been deleted', [ 'ITEM_NAME' => $project->getName() ] )
			);

			Http_Headers::reload([], ['action', 'id']);
		}


		$this->view->setVar( 'project', $project );

		$this->output( 'delete-confirm' );
	}
	
	public function generate_code_Action(): void
	{
		$name = Http_Request::GET()->getString('generate_code');
		
		AJAX::commonResponse([
			'code' => Project::generateCode( $name )
		]);
	}

}