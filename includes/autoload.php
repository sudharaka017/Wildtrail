<?php
spl_autoload_register(function(string $class): void { $prefix='WildTrail\\'; if(strncmp($class,$prefix,strlen($prefix))!==0) return; $relative=substr($class,strlen($prefix)); $file=dirname(__DIR__).'/app/'.str_replace('\\','/',$relative).'.php'; if(is_file($file)) require_once $file; });
