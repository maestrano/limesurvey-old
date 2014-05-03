<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require_once MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
chdir(APP_DIR);
//require MY_APP_DIR . '/include/some_class_file.php';
//require MY_APP_DIR . '/config/some_database_config_file.php';

$system_path = APP_DIR . "/framework";
$application_folder = APP_DIR . "/application";
  
/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */
if (realpath($system_path) !== FALSE)
{
	$system_path = realpath($system_path).'/';
}

// ensure there's a trailing slash
$system_path = rtrim($system_path, '/').'/';

// Is the system path correct?
if (!is_dir($system_path))
{
	exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(APP_DIR, PATHINFO_BASENAME));
}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */


// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

define('ROOT', dirname(APP_DIR));

// The PHP file extension
define('EXT', '.php');

// Path to the system folder
define('BASEPATH', str_replace("\\", "/", $system_path));

// Path to the front controller (this file)
define('FCPATH', str_replace(SELF, '', APP_DIR));

// Name of the "system folder"
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));


// The path to the "application" folder
if (is_dir($application_folder))
{
	define('APPPATH', $application_folder.'/');
}
else
{
	if (!is_dir(BASEPATH . $application_folder . '/'))
	{
		exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
	}

	define('APPPATH', BASEPATH . $application_folder . '/');
}

if (file_exists(APPPATH.'config'.DIRECTORY_SEPARATOR.'config.php'))
{
    $aSettings= include(APPPATH.'config'.DIRECTORY_SEPARATOR.'config.php');
}
else
{
    $aSettings=array();
}
/*
if (isset($aSettings['config']['debug']) && $aSettings['config']['debug']>0)
{
    define('YII_DEBUG', true);
    error_reporting(E_ALL);
}
else
{
    define('YII_DEBUG', false);
    error_reporting(0);
}
 * 
 */
define('YII_DEBUG', true);
    error_reporting(E_ALL);

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */

require_once BASEPATH . 'yii' . EXT;
require_once APPPATH . 'core/LSYii_Application' . EXT;

Yii::createApplication('LSYii_Application', APPPATH . 'config/config' . EXT);
Yii::import('application.helpers.common_helper', true);

require_once APPPATH . 'helpers/globalsettings_helper.php';

$opts['db_connection'] = Yii::app()->db;

MnoSoaDB::initialize($opts['db_connection']);
MnoSoaLogger::initialize();

