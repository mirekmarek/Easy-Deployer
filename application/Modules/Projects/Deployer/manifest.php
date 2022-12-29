<?php
return [
	'vendor' => '',
	'version' => '',
	'label' => 'Deployer',
	'description' => '',
	'is_mandatory' => true,
	'ACL_actions' => [
		'prepare_deployment' => 'Prepare deployment',
		'do_deployment' => 'Do deployment',
		'rollback_deployment' => 'Rollback deployment',
		'delete_deployment' => 'Delete deployment',
	],
];
