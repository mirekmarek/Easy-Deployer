<?php
return [
	'id' => '_homepage_',
	'name' => 'Homepage',
	'is_active' => true,
	'SSL_required' => false,
	'title' => 'Hlavní stránka',
	'icon' => 'home',
	'menu_title' => 'Hlavní stránka',
	'breadcrumb_title' => 'Hlavní stránka',
	'order' => 0,
	'is_secret' => false,
	'layout_script_name' => 'default',
	'http_headers' => [
	],
	'parameters' => [
	],
	'meta_tags' => [
	],
	'contents' => [
		[
			'module_name' => 'Deployer.Projects',
			'controller_name' => 'Main',
			'controller_action' => 'default',
			'parameters' => [
			],
			'is_cacheable' => false,
			'output_position' => '__main__',
			'output_position_order' => 1,
		],
	],
];
