<?php
return [
	'Installation finish'                                              => '',
	'Please resolve this issue and try to continue'                    => '',
	'Something went wrong: %error%'                                    => '',
	'DONE_TEXT' => '<p>Installation completed successfully.</p>
<p>In order to use Easy Deployer, <b>it is necessary to perform</b> the following steps:</p>

<ul>
	<li>Log in to the administration and if the tool will be used by more than one developer <a href="%URL_DEVELOPERS%" target="_blank">create user accounts for developers</a>.</li>
	<li>If you want to control which group of developers has access to individual projects, then you can <a href="%URL_ROLES%" target="_blank">create and manage developer roles</a>.</li>
	<li><a href="%URL_PROJECTS%" target="_blank">Create and set up</a> projects.</li>
	<li><a href="%URL_DEPLOYER%" target="_blank">Sign in as a developer</a> and you can start using this tool.</li>
	<li class="text-danger"><b>Delete directory</b> %INSTALL_DIR%.</li>
	<li class="text-danger"><b>Make really sure that this tool is not accessible from the public internet.</b><br>If for some reason the tool is located outside of your internal network, ideally restrict access to IP addresses, or do other some similar measures.</li>
</ul>
',
];
