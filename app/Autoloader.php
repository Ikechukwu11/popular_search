<?php

namespace PopularSeach;

class Autoloader
{
  public static function load()
  {
    spl_autoload_register([__CLASS__, 'autoload']);
  }

  private static function autoload($class)
  {
    if (strpos($class, __NAMESPACE__) !== 0) {
      return;
    }

    $classPath = str_replace(__NAMESPACE__ . '\\', '', $class);
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);

    $file = __DIR__ . '/' . $classPath . '.php';
    if (file_exists($file)) {
      require $file;
    }
  }
}
