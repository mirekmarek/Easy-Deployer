<?php /** @noinspection PhpIncludeInspection */

/**
 *
 * @copyright 
 * @license  
 * @author  
 */
namespace JetApplicationModule\Projects\Deployer;

use Jet\Application;
use Jet\Http_Headers;
use Jet\Http_Request;
use Jet\MVC;
use Jet\MVC_Controller_Default;
use Jet\Navigation_Breadcrumb;
use Jet\SysConf_Path;
use Jet\Tr;
use JetApplication\Deployment;
use JetApplication\Project;

use Diff;
use Diff_Renderer_Html_SideBySide;

/**
 *
 */
class Controller_Main extends MVC_Controller_Default
{
	protected ?Project $current_project = null;
	
	protected ?Deployment $current_deployment = null;
	
	public function resolve(): bool|string
	{
		$GET = Http_Request::GET();
		
		if(
			($project_code=$GET->getString('project')) &&
			($project=Project::get($project_code)) &&
			$project->accessAllowed()
		) {
			$this->current_project = $project;
			
			if(
				($deployment_id = $GET->getInt('deployment')) &&
				($deployment = $project->getDeployment( $deployment_id ))
			) {
				$this->current_deployment = $deployment;
			}
		}
		
		$action = $GET->getString('action');
		
		Navigation_Breadcrumb::reset();
		
		Navigation_Breadcrumb::addURL(
			Tr::_('Select project'),
			MVC::getPage()->getURL()
		);
		
		if(!$this->current_project) {
			return 'select_project';
		}
		
		
		
		if($action=='start_new_deployment') {
			return 'start_new_deployment';
		}
		
		if($this->current_deployment) {
			Navigation_Breadcrumb::addURL(
				$this->current_project->getName(),
				MVC::getPage()->getURL( GET_params: [
					'project' => $this->current_project->getCode(),
					'deployment' => $this->current_deployment->getId()
				] )
			);
			
			if($action=='prepare_show') {
				return 'deployment_prepare_show';
			}
			
			if($action=='prepare') {
				return 'deployment_prepare';
			}
			
			if($action=='prepare_again') {
				return 'deployment_prepare_again';
			}
			
			if(
				$action=='deploy_again' &&
				$this->current_deployment->getState()==Deployment::STATE_DEPLOYMENT_ERROR
			) {
				return $action;
			}
			
			if(
				$action=='compare_file' &&
				($file = $GET->getString('file')) &&
				$this->current_deployment->getDiff()->fileIsChanged( $file )
			) {
				return 'compare_file';
			}
			
			if( $this->current_deployment->getState()==Deployment::STATE_PREPARATION_DONE ) {
				if(
					$action=='unselect_file' ||
					$action=='select_file'
				) {
					return $action;
				}
				
				if(
					$this->current_deployment->getSelectedFiles() &&
					(
						$action=='deployment_start_show' ||
						$action=='deploy'
					)
				) {
					return $action;
				}
			}
			
		} else {
			Navigation_Breadcrumb::addURL(
				$this->current_project->getName(),
				MVC::getPage()->getURL( GET_params: [
					'project' => $this->current_project->getCode()
				] )
			);
			
		}
		
		
		return 'deployment_detail';
	}
	
	
	public function getCurrentProject(): ?Project
	{
		return $this->current_project;
	}
	
	public function getCurrentDeployment(): ?Deployment
	{
		return $this->current_deployment;
	}
	
	public function deployment_detail_Action() : void
	{
		switch($this->current_deployment?->getState()) {
			case Deployment::STATE_PREPARATION_STARTED:
				$this->output('deployment/preparation_started');
				break;
			case Deployment::STATE_PREPARATION_ERROR:
				$this->output('deployment/preparation_error');
				break;
			case Deployment::STATE_PREPARATION_DONE:
				$this->output('deployment/preparation_done');
				break;
			case Deployment::STATE_DEPLOYMENT_STARTED:
				$this->output('deployment/deployment_started');
				break;
			case Deployment::STATE_DEPLOYMENT_ERROR:
				$this->output('deployment/deployment_error');
				break;
			case Deployment::STATE_DEPLOYMENT_DONE:
				$this->output('deployment/deployment_done');
				break;
			
			default:
				$this->output('default');
		}
	}
	
	public function select_project_Action() : void
	{
		$this->output('select_project');
	}
	
	public function start_new_deployment_Action() : void
	{
		$deployment = Deployment::initPreparation( $this->current_project );
		$this->current_deployment = $deployment;
		

		Http_Headers::movedTemporary(
			MVC::getPage()->getURL(GET_params: [
				'project' => $this->current_project->getCode(),
				'deployment' => $deployment->getId(),
				'action' => 'prepare_show'
			])
		);
	}
	
	public function deployment_prepare_show_Action() : void
	{
		$this->view->setVar( 'prepare_URL', MVC::getPage()->getURL(GET_params: [
			'project' => $this->current_project->getCode(),
			'deployment' => $this->current_deployment->getId(),
			'action' => 'prepare'
		]) );
		
		$this->output('deployment/preparation_in_progress');
	}
	
	public function deployment_prepare_Action() : void
	{
		$this->current_deployment->prepare();
		
		Http_Headers::movedTemporary(
			MVC::getPage()->getURL(GET_params: [
				'project' => $this->current_project->getCode(),
				'deployment' => $this->current_deployment->getId()
			])
		);
	}
	
	public function deployment_prepare_again_Action() : void
	{
		if(!$this->current_deployment->prepareAgain()) {
			Http_Headers::movedTemporary(
				MVC::getPage()->getURL(GET_params: [
					'project' => $this->current_project->getCode(),
					'deployment' => $this->current_deployment->getId()
				])
			);
		}
		
		$this->view->setVar( 'prepare_URL', MVC::getPage()->getURL(GET_params: [
			'project' => $this->current_project->getCode(),
			'deployment' => $this->current_deployment->getId(),
			'action' => 'prepare'
		]) );
		
		$this->output('deployment/preparation_in_progress');
	}
	
	public function compare_file_Action() : void
	{
		Navigation_Breadcrumb::addURL(Tr::_('File change'));
		
		require SysConf_Path::getLibrary().'Diff/Diff.php';
		require SysConf_Path::getLibrary().'Diff/Diff/Renderer/Html/SideBySide.php';
		
		$file = Http_Request::GET()->getString('file');
		
		$new = explode("\n", $this->current_deployment->readSourceFile( $file ) );
		$old = explode("\n", $this->current_deployment->readBackupFile( $file ) );
		
		$diff = new Diff( $old, $new, [
			//'ignoreWhitespace' => true,
			//'ignoreCase' => true,
		
		]);
		
		$renderer = new Diff_Renderer_Html_SideBySide();
		
		
		$this->view->setVar( 'file', $file );
		$this->view->setVar( 'diff', $diff );
		$this->view->setVar( 'renderer', $renderer );
		
		$this->output('file_diff');

	}
	
	public function unselect_file_Action() : void
	{
		$file = Http_Request::GET()->getString('file');
		
		$this->current_deployment->unselectFile( $file );
		
		echo $this->view->render('deployment/selected_files');
		Application::end();
	}
	
	public function select_file_Action() : void
	{
		$file = Http_Request::GET()->getString('file');
		
		$this->current_deployment->selectFile( $file );
		
		echo $this->view->render('deployment/selected_files');
		Application::end();
	}
	
	public function deployment_start_show_Action() : void
	{
		$this->view->setVar( 'deploy_URL', MVC::getPage()->getURL(GET_params: [
			'project' => $this->current_project->getCode(),
			'deployment' => $this->current_deployment->getId(),
			'action' => 'deploy'
		]) );
		
		$this->output('deployment/deployment_in_progress');
	}
	
	public function deploy_Action() : void
	{
		$this->current_deployment->deploy();
		
		Http_Headers::movedTemporary(
			MVC::getPage()->getURL(GET_params: [
				'project' => $this->current_project->getCode(),
				'deployment' => $this->current_deployment->getId()
			])
		);
	}
	
	public function deploy_again_Action() : void
	{
		$this->current_deployment->retryDeploy();
		
		Http_Headers::movedTemporary(
			MVC::getPage()->getURL(GET_params: [
				'project' => $this->current_project->getCode(),
				'deployment' => $this->current_deployment->getId(),
				'action' => 'deployment_start_show',
			])
		);
		
	}
	
}