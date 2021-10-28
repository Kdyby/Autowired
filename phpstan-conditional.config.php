<?php
declare(strict_types = 1);

$config = [];

if (PHP_VERSION_ID < 8_00_00) {
    $config['parameters']['ignoreErrors'][] = '~Call to an undefined method ReflectionProperty::getAttributes\(\)~';
}

return $config;
