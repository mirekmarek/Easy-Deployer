<?php
return [
	'id' => 'user-account',
	'name' => 'user-account',
	'is_active' => true,
	'SSL_required' => false,
	'title' => 'Uživatelský účet',
	'icon' => '',
	'menu_title' => 'Uživatelský účet',
	'breadcrumb_title' => 'Uživatelský účet',
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
			'module_name' => 'Deployer.UserAccount',
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
