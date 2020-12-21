<?php
use GDO\DB\Database;
use GDO\Install\Installer;
use GDO\Core\Logger;
use PHPUnit\TextUI\Command;
use GDO\Core\Application;
use GDO\Session\GDO_Session;
use GDO\File\FileUtil;
use GDO\UI\GDT_Page;
use GDO\Core\Debug;

if (PHP_SAPI !== 'cli') { die('Tests can only be run from the command line.'); }

require_once 'vendor/autoload.php';
require_once 'protected/config_unit_test.php';
require_once 'GDO6.php';

Logger::init('system', GWF_ERROR_LEVEL);
Debug::init();
Debug::setMailOnError(GWF_ERROR_EMAIL);
// Debug::setDieOnError(GWF_ERROR_DIE);
Debug::enableErrorHandler();
Debug::enableExceptionHandler();
Database::init();
GDO_Session::init();

final class TestApp extends Application
{
    private $cli = false;
    public function cli($cli) { $this->cli = $cli; return $this; }
    public function isCLI() { return $this->cli; } # override CLI mode to test HTML rendering.
    
    public function isUnitTests() { return true; }

}

$app = new TestApp();
GDT_Page::make();

#############################
### Simulate HTTP env a bit #
$_SERVER['SERVER_NAME'] = trim(GWF_DOMAIN, "\r\n\t .");
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Firefox Gecko MS Opera';
$_SERVER['REQUEST_URI'] = '/index.php?mo=' . GWF_MODULE . '&me=' . GWF_METHOD;
$_SERVER['HTTP_REFERER'] = 'http://'.GWF_DOMAIN.'/index.php';
$_SERVER['HTTP_ORIGIN'] = '127.0.0.2';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_SOFTWARE']	= 'Apache/2.4.41 (Win64) PHP/7.4.0';
$_SERVER['HTTP_HOST'] = GWF_DOMAIN;
$_SERVER['HTTPS'] = 'off';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['QUERY_STRING'] = 'mo=' . GWF_MODULE . '&me=' . GWF_METHOD;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7';
#########################################################################

echo "Dropping Test Database: ".GWF_DB_NAME.".\n";
Database::instance()->queryWrite("DROP DATABASE " . GWF_DB_NAME);
Database::instance()->queryWrite("CREATE DATABASE " . GWF_DB_NAME);
Database::instance()->useDatabase(GWF_DB_NAME);

echo "Loading modules from filesystem\n";
$modules = $app->loader->loadModules(false, true);

foreach ($modules as $module)
{
    if ($module->defaultEnabled())
    {
        echo "Installing {$module->getName()}\n";
        Installer::installModule($module);
    }
}

foreach ($modules as $module)
{
    $testDir = $module->filePath('Test');
    if (FileUtil::isDir($testDir))
    {
        if (!$module->isPersisted())
        {
            echo "Installing {$module->getName()}\n";
            Installer::installModule($module);
        }
        
        echo "Running tests for {$module->getName()}\n";
        $command = new Command();
        $command->run(['phpunit', $testDir], false);
        echo "Done with {$module->getName()}\n";
        echo "----------------------------------------\n";
    }
}
