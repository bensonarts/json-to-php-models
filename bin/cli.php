<?php

require_once __DIR__ . '/../src/PhpGenerator.php';

if (empty($argv[1])) {
    die('Supply an input file');
}
if (empty($argv[2])) {
    die('Provide a class name');
}
$className = $argv[2];
$useTypeHinting = false;
$useFluentSetters = false;
if (!empty($argv[3]) && is_numeric($argv[3])) {
    $useTypeHinting = (bool) $argv[3];
}
if (!empty($argv[4]) && is_numeric($argv[4])) {
    $useFluentSetters = (bool) $argv[4];
}
$namespace = null;
if (!empty($argv[5])) {
    $namespace = $argv[5];
}

$generator = new PhpGenerator($useTypeHinting, $useFluentSetters, $namespace);

if (!file_exists($argv[1])) {
    die(sprintf('File does not exist at %s', $argv[1]));
}
$json = file_get_contents($argv[1]);
echo $generator->fromJson($className, $json);
