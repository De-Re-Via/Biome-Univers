<?php
// Autoloader PSR-4 très simple pour le namespace App\
spl_autoload_register(function(string $class){
  $prefix = 'App\\';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $path = __DIR__ . '/' . str_replace('\\','/',$rel) . '.php';
  if (is_file($path)) require $path;
});

// charge la config
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));
