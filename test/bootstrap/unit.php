<?php
$_SERVER['SYMFONY'] = dirname(__FILE__).'/../../../../lib/vendor/symfony/lib';

if (!isset($_SERVER['SYMFONY']))
{
  throw new RuntimeException('Could not find symfony core libraries.');
}

require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

$configuration = new sfProjectConfiguration(dirname(__FILE__).'/../fixtures/project');
require_once $configuration->getSymfonyLibDir().'/vendor/lime/lime.php';

function stPayFlowProPlugin_autoload_again($class)
{
  $autoload = sfSimpleAutoload::getInstance();
  $autoload->reload();
  return $autoload->autoload($class);
}
spl_autoload_register('stPayFlowProPlugin_autoload_again');

if (file_exists($config = dirname(__FILE__).'/../../config/stPayFlowProPluginConfiguration.class.php'))
{
  require_once $config;
  $plugin_configuration = new stPayFlowProPluginConfiguration($configuration, dirname(__FILE__).'/../..', 'stPayFlowProPlugin');
}
else
{
  $plugin_configuration = new sfPluginConfigurationGeneric($configuration, dirname(__FILE__).'/../..', 'stPayFlowProPlugin');
}
