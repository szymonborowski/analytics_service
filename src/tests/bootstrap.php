<?php

// Override Docker container env vars for testing
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';

require __DIR__ . '/../vendor/autoload.php';
