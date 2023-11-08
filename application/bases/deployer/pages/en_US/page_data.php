<?php

return [
	'name'               => 'Homepage',
	'title'              => 'Homepage',
	'menu_title'         => 'Homepage',
	'breadcrumb_title'   => 'Homepage',
	'layout_script_name' => 'default',
	'icon'               => 'home',
	'meta_tags'          => [
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

