<?php
/**
 *
 * @copyright Copyright (c) Miroslav Marek <mirek.marek@web-jet.cz>
 * @license http://www.php-jet.net/license/license.txt
 * @author Miroslav Marek <mirek.marek@web-jet.cz>
 */

namespace Jet;

/**
 *
 */
class UI_badge extends UI_Renderer_Single
{
	public const PRIMARY = 'primary';
	public const SECONDARY = 'secondary';
	public const WARNING = 'warning';
	public const INFO = 'info';
	public const SUCCESS = 'success';
	public const DANGER = 'danger';
	public const LIGHT = 'light';
	public const DARK = 'dark';
	
	protected string $text;
	
	protected string $type;
	
	/**
	 * @param string $type
	 * @param string $text
	 */
	public function __construct( string $type, string $text )
	{
		$this->type = $type;
		$this->text = $text;
		$this->view_script = SysConf_Jet_UI_DefaultViews::get('badge');
	}
	
	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}
	
	
	/**
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}
	
}