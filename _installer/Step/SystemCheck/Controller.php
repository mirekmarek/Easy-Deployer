<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication\Installer;

require 'CompatibilityTester.php';

/**
 *
 */
class Installer_Step_SystemCheck_Controller extends Installer_Step_Controller
{

	/**
	 * @var string
	 */
	protected string $label = 'Check compatibility';

	/**
	 * @return bool
	 */
	public function getIsAvailable(): bool
	{
		return !Installer_Step_Install_Controller::basesCreated();
	}

	/**
	 *
	 */
	public function main(): void
	{
		$this->catchContinue();

		$tester = new Installer_CompatibilityTester();

		$tester->testSystem(
			[
				'test_PHPVersion',
				'test_PDOExtension',
				'test_FTPExtension',
				'test_MBStringExtension',
				'test_INTLExtension',
				'test_SSL',
			]
		);

		$this->view->setVar( 'tester', $tester );

		$this->render( 'default' );
	}

}
