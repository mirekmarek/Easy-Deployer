<?php
return [
	'Installation finish'                                              => 'Dokončení instalace',
	'Please resolve this issue and try to continue'                    => 'Prosím vyřešte tento problém a zkuste pokračovat',
	'Something went wrong: %error%'                                    => 'Něco se nepovedlo: %error%',
	'DONE_TEXT' => '<p>Instalace je úspěšně dokončena.</p>
<p>Aby bylo možné Easy Deployer používat, tak <b>je nutné provést</b> následující kroky:</p>

<ul>
	<li>Přihlaste se do administrace a pokud bude nástroj používate více jak jeden vývojář <a href="%URL_DEVELOPERS%" target="_blank">vytvořte uživatelské účty vývojǎrům</a>.</li>
	<li>Pokud chcete řídit která skupina vývojářů má přístup k jednotlivým projektům, pak můžete <a href="%URL_ROLES%" target="_blank">vytvářet a spravovat vývojářské role</a>.</li>
	<li><a href="%URL_PROJECTS%" target="_blank">Založte a nastavte</a> projekty.</li>
	<li><a href="%URL_DEPLOYER%" target="_blank">Přihlaste se jako vývojář</a> a nástroj můžete začít používat.</li>
	<li class="text-danger"><b>Smažte adresář</b> %INSTALL_DIR%.</li>
	<li class="text-danger"><b>Opravdu důkladně se ujistěte, že nástroj není přístupný z veřejného internetu.</b><br>Pokud se z nějakého důvodu nástroj nalézá mimo vaší vnitřní sít, zajistěte ideálně omezení přístupu na IP adresy, nebo jiné podobné opatření.</li>
</ul>
',
];
