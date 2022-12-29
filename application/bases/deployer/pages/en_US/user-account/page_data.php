<?php
return [
	'id' => 'user-account',
	'name' => 'user-account',
	'is_active' => true,
	'SSL_required' => false,
	'title' => 'User account',
	'icon' => '',
	'menu_title' => 'User account',
	'breadcrumb_title' => 'User account',
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
			'module_name' => 'UserAccount',
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
