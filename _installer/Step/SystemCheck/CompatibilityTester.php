<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace JetApplication\Installer;

use Jet\Tr;
use JetApplication\Application_Deployer_Config;
use JetApplication\Deployment_Backend_FTP;
use JetApplication\Deployment_Backend_SFTP;

require_once 'CompatibilityTester/TestResult.php';

/**
 *
 */
class Installer_CompatibilityTester
{
	/**
	 * @var string
	 */
	protected string $PHP_info = '';

	/**
	 * @var Installer_CompatibilityTester_TestResult[]
	 */
	protected array $test_results = [];

	/**
	 * @var bool|null
	 */
	protected bool|null $is_compatible = null;

	/**
	 * @var bool|null
	 */
	protected bool|null $has_warnings = null;

	/**
	 *
	 */
	public function __construct()
	{
		foreach( [
					 'function_exists',
					 'class_exists',
					 'version_compare',
					 'ini_get',
					 'ob_start',
					 'ob_end_clean',
					 'ob_get_contents',
					 'phpinfo',
				 ] as $required_function ) {

			if( !function_exists( $required_function ) ) {
				trigger_error( 'Error: function \'' . $required_function . '\' is required!', E_USER_ERROR );
			}
		}

		ob_start();
		phpinfo();
		$this->PHP_info = ob_get_contents();
		ob_end_clean();

	}

	/**
	 * @param array $tests
	 *
	 * @return bool
	 */
	public function testSystem( array $tests ): bool
	{

		foreach( $tests as $test ) {
			$this->{$test}();
		}

		$this->is_compatible = true;
		$this->has_warnings = false;

		foreach( $this->test_results as $test_result ) {
			if( $test_result->getIsError() ) {
				$this->is_compatible = false;
			}
			if( $test_result->getIsWarning() ) {
				$this->has_warnings = true;
			}
		}


		return $this->is_compatible;
	}

	/**
	 * @return Installer_CompatibilityTester_TestResult[]
	 */
	public function getTestResults(): array
	{
		return $this->test_results;
	}

	/**
	 * @return bool
	 */
	public function isCompatible(): bool
	{
		return $this->is_compatible;
	}

	/**
	 * @return bool
	 */
	public function hasWarnings(): bool
	{
		return $this->has_warnings;
	}


	/**
	 * @param string $title
	 * @param string $description
	 * @param callable $test
	 *
	 * @return bool
	 */
	public function test( string $title, string $description, callable $test ): bool
	{
		$test_result = new Installer_CompatibilityTester_TestResult( true, $title, $description );
		$test_result->setPassed( $test( $test_result ) );
		$this->test_results[] = $test_result;

		return $test_result->getPassed();
	}

	/**
	 * @param string $title
	 * @param string $description
	 * @param callable $test
	 *
	 * @return bool
	 */
	public function check( string $title, string $description, callable $test ): bool
	{
		$test_result = new Installer_CompatibilityTester_TestResult( false, $title, $description );
		$test_result->setPassed( $test( $test_result ) );
		$this->test_results[] = $test_result;

		return $test_result->getPassed();
	}


	/**
	 *
	 */
	public function test_PHPVersion(): void
	{
		$required_version = '8.0';

		$this->test(
			Tr::_( 'PHP version' ),
			Tr::_( 'PHP %VERSION% or newer is required', ['VERSION' => $required_version] ),
			function( Installer_CompatibilityTester_TestResult $test_result ) use ( $required_version ) {
				$test_result->setResultMessage( Tr::_( 'PHP version: ' ) . PHP_VERSION );

				return version_compare( PHP_VERSION, $required_version, '>=' );
			}
		);

	}

	/**
	 *
	 */
	public function test_PDOExtension(): void
	{
		$this->test(
			Tr::_( 'PDO extension' ),
			Tr::_( 'PHP <a href="https://www.php.net/manual/en/book.pdo.php" target="_blank">PDO extension</a> must be activated' ),
			function() {
				return extension_loaded( 'PDO' );
			}
		);
	}

	/**
	 *
	 */
	public function test_MBStringExtension(): void
	{
		$this->test(
			Tr::_( 'Multibyte String extension' ),
			Tr::_( 'PHP <a href="https://www.php.net/manual/en/book.mbstring.php" target="_blank">Multibyte String</a> extension must be activated' ),
			function() {
				return extension_loaded( 'mbstring' );
			}
		);
	}

	/**
	 *
	 */
	public function test_INTLExtension(): void
	{
		$this->test(
			Tr::_( 'INTL extension' ),
			Tr::_( 'PHP <a href="https://www.php.net/manual/en/book.intl.php" target="_blank">Internationalization Functions extension</a> must be activated' ),
			function( Installer_CompatibilityTester_TestResult $test_result ) {
				return extension_loaded( 'intl' );
			}
		);
	}
	
	/**
	 *
	 */
	public function test_FTPExtension(): void
	{
		$this->test(
			Tr::_( 'FTP and/or SSH2 extension' ),
			Tr::_( 'PHP <a href="https://www.php.net/manual/en/book.ftp.php" target="_blank">FTP extension</a> and/or <a href="https://www.php.net/manual/en/book.ssh2.php" target="_blank">SSH2 extension</a> must be activated' ),
			function( Installer_CompatibilityTester_TestResult $test_result ) {
				$ftp_avl = Deployment_Backend_FTP::isAvailable();
				$sftp_avl = Deployment_Backend_SFTP::isAvailable();
				
				
				if(!$ftp_avl && !$sftp_avl) {
					$test_result->setResultMessage(
						Tr::_(
							'PHP <a href="https://www.php.net/manual/en/book.ftp.php" target="_blank">FTP extension</a> is not available.<br>'
							.'PHP <a href="https://www.php.net/manual/en/book.ssh2.php" target="_blank">SSH2 extension</a> is not available.<br><br>'
							.'<b>At least one of those extension must be available.</b>'
						)
					);
					
					return false;
				}
				
				if(!$ftp_avl) {
					$test_result->setResultMessage(
						Tr::_(
							'PHP <a href="https://www.php.net/manual/en/book.ftp.php" target="_blank">FTP extension</a> is not available.<br><b>FTP support is disabled.</b>'
						)
					);
				}
				
				if(!$sftp_avl) {
					$test_result->setResultMessage(
						Tr::_(
							'PHP <a href="https://www.php.net/manual/en/book.ssh2.php" target="_blank">SSH2 extension</a> is not available.<br><b>SFTP support is disabled.</b>'
						)
					);
				}
				
				return true;
			}
		);
	}
	
	/**
	 *
	 */
	public function test_SSL(): void
	{
		$this->test(
			Tr::_( 'OpenSSL' ),
			Tr::_( 'PHP <a href="https://www.php.net/manual/en/book.openssl.php" target="_blank">OpenSSL support</a> must be available' ),
			function( Installer_CompatibilityTester_TestResult $test_result ) {
				return
					function_exists('openssl_get_cipher_methods') &&
					in_array(Application_Deployer_Config::CIPHER_ALGO, openssl_get_cipher_methods()) &&
					function_exists('openssl_random_pseudo_bytes') &&
					function_exists('openssl_cipher_iv_length') &&
					function_exists('openssl_encrypt')
					;
			}
		);
	}
}
