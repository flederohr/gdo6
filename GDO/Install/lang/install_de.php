<?php
return array(
# Welcome
'install_title_1' => 'Willkommen',
'install_text_1' => 'Willkommen zu GDO6. Bitte fahren Sie hier fort: %s',

# System Test
'install_title_2' => 'System–Test',
'install_title_2_tests' => 'Wichtige Vorraussetzungen',
'install_test_0' => 'Ist PHP-'.PHP_VERSION.' unterstützt?',
'install_test_1' => 'Ist der protected Dateiordner beschreibbar?',
'install_test_2' => 'Ist der files Dateiordner beschreibbar?',
'install_test_3' => 'Ist der temp Dateiordner beschreibbar?',
'install_test_4' => 'Sind nodejs, npm, bower und yarn verfügbar?',
'install_test_5' => 'Ist PHP mbstring installiert?',
'install_title_2_optionals' => 'Optionale Features',
'install_optional_0' => 'Ist PHP gd installiert?',
'install_optional_1' => 'Ist PHP memcached installeiert?',
'install_system_ok' => 'Sie können GDO6 auf diesem System installieren. Sie können hier fortfahren: %s.',
'install_system_not_ok' => 'Sie können GDO6 auf diesem System noch nicht installieren. Sie können erneut den Systemtest durchführen: %s.',

# Config
'install_title_3' => 'GDO Konfiguration',
'ft_install_configure' => 'Konfigurationsdatei schreiben',
'install_config_section_site' => 'Seite',
'cfg_sitename' => 'Kurzer Sitenname',
'language' => 'Hauptsprache',
'themes' => 'Designs',
'install_config_section_http' => 'HTTP',
'install_config_section_files' => 'Dateien',
'enum_448' => '700',
'enum_504' => '770',
'enum_511' => '777',
'enum_en' => 'Englisch',
'enum_de' => 'Deutsch',
'install_config_section_logging' => 'Logging',
'install_config_section_database' => 'Datenbank',
'install_config_section_cache' => 'Cache',
'install_config_section_cookies' => 'Cookies',
'install_config_section_email' => 'Email',
'err_db_connect' => 'Die Verbindung zur Datenbank ist fehlgeschlagen.',
'install_config_boxinfo_success' => 'Dieses System sieht gut aus. Sie können hier fortfahren: %s',

# Modules
'install_title_4' => 'GDO6 Module',
'install_modules_info_text' => 'Hier wählen Sie die Module zum Installieren.',
'install_modules_completed' => 'Ihre Module wurden installiert. Sie können hier fortfahren: %s',
'err_disable_core_module' => 'Sie können Core Module nicht abwählen.',
'err_multiple_site_modules' => 'Sie sollten nicht mehere Seitenmodule anwählen.',
'err_missing_dependency' => 'Es fehlen Abhängigkeiten: ',

# Cronjob
'install_title_5' => 'Cronjob Konfiguration',
'install_cronjob_info' => '
Sie sollten einen Hintergrunddienst auf diesem System einrichten.
Sie können dies in eine crontab Datei einfügen:

%s

Sie können dann hier fortfahren: %s',

# Admins
'install_title_6' => 'Administrator anlegen',
'ft_install_installadmins' => 'Administrator anlegen',
	
'install_title_7' => 'Javascript installieren',
'install_content_7' => '
<p>Sie sollten nun die Javascript abhängigkeiten installieren.</p>
<p>Als Alternative können Sie diese manuell hochladen.</p>
<p>Fürhren Sie folgende Befehle auf einer Debian Machine aus:<p>
<code>
Als root:<br/>
<br/>
aptitude install nodejs nodejs-dev npm # Install javascript<br/>
npm install -g bower # Install bower<br/>
npm install -g yarn # Install yarn<br/>
<br/>
As gdo6/www user:<br/>
<br/>
cd www/gdo6<br/>
./gdo_bower.sh # Install module js dependencies<br/>
./gdo_yarn.sh # Install module js dependencies<br/>
<br/>
Note: Currently bower and yarn are both in use. Bower will be dropped.<br/>
</code>
',
	
'install_title_8' => 'Backup einspielen',
'ft_install_importbackup' => 'Backup einspielen',

'install_title_9' => 'Installation absichern',
'ft_install_security' => 'Installation abschliessen und den Zugang zum Installer und protected Ordner verhindern.',
);