<?php

require_once __DIR__.'/init.php';
require_once SPC_PATH.'/FileSystem.php';

FileSystem::write(__DIR__.'/dist/b.txt', 'hello');
