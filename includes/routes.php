<?php
/** Define the default routes. */
$routes = array(
	'client' => array(
		'parse' => 'clientImportParse',
		'preparse' => 'clientImportPreParse',
		'preprocess' => 'clientImportPreprocess',
		'process' => 'clientImportProcess',
		'results' => 'clientImportResults',
		'upload' => 'clientImportUpload',
		'default' => 'clientImport',
		'report' => array(
			'delete' => 'delete',
			'default' => 'reports'
		),
		'tree' => 'clientTree',
	),
	'client-process' => 'clientProcess',
	'client-selection' => 'clientSelection',
	'management' => array(
		'clients' => array(
			'create' => 'create',
			'edit' => 'edit',
			'process' => 'process',
			'select' => 'select',
			'default' => 'clients'
		),
		'users' => array(
			'clients' => 'clients',
			'clients-process' => 'clientsProcess',
			'create' => 'create',
			'edit' => 'edit',
			'process' => 'process',
			'select' => 'select',
			'default' => 'users'
		),
		'default' => 'management'
	),
	'error' => 'error',
	'logging-in' => 'loggingIn',
	'log-out' => 'logOut',
	'password' => array(
		'forgot' => 'forgot',
		'forgot-process' => 'forgotProcess',
		'forgot-results' => 'forgotResults',
		'reset' => 'reset',
		'reset-process' => 'resetProcess',
		'default' => 'forgot'
	),
	'default' => 'clientSelection'
);

/** Define the routes that don't require a logged in user. */
$whitelist = array(
	'error',
	'logging-in',
	'log-out',
	'password'
);
