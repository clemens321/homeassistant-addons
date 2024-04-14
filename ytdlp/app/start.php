<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

chdir(__DIR__);
define('PROJECT_ROOT', __DIR__.('/' !== substr(__DIR__, -1) ? '/' : ''));
require_once PROJECT_ROOT.'vendor/autoload.php';

$response = null;

$file = $_SERVER['SCRIPT_NAME'];
$fileParts = explode('/', $file);
array_shift($fileParts);

$classParts = [];
while (($classParts[] = ucfirst(array_shift($fileParts) ?? '')) && isset($fileParts[0])) {
    $className = '\\App\\'.implode('\\', $classParts);
    if (class_exists($className) && method_exists($className, $fileParts[0])) {
        $method = array_shift($fileParts);
        $class = new $className();
        try {
            $response = $class->{$method}(...$fileParts);

            break;
        } catch (HttpExceptionInterface $e) {
            $response = new Response($e->getMessage().\PHP_EOL, $e->getStatusCode());
        } catch (\Exception $e) {
            $response = new Response($e->getMessage().\PHP_EOL, 500);
        } 
    }
}

if ($response) {
    $response->send();
}
