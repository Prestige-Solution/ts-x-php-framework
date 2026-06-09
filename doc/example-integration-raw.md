# First Steps

This guide explains how to set up and use the TeamSpeak PHP framework in a new or existing PHP project.

## Example Project Structure

The following example shows a simple blank PHP project:

```shell
.
├── autoload.php
├── index.php
└── src
```

Inside the `src` directory, place the required packages:

```shell
.
├── phpseclib
└── ts3phpframework
```

The complete structure should look like this:

```shell
.
├── autoload.php
├── index.php
└── src
    ├── phpseclib
    └── ts3phpframework
```

## Setting Up the Autoloader

Create an `autoload.php` file in your project root.

This autoloader maps PHP namespaces to the corresponding source directories.
It is flexible enough to support additional packages if required.

```php
<?php

spl_autoload_register(function ($class) {
    $prefixes = array(
        'PlanetTeamSpeak\\TeamSpeak3Framework\\' => __DIR__ . '/src/ts3phpframework/',
        'phpseclib3\\' => __DIR__ . '/src/phpseclib/',
    );

    foreach ($prefixes as $prefix => $baseDir) {
        $prefixLength = strlen($prefix);

        if (strncmp($prefix, $class, $prefixLength) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $prefixLength);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }

        return;
    }
});
```

If you need to add more libraries later, extend the `$prefixes` array with another namespace-to-directory mapping.

Example:

```php
<?php

$prefixes = array(
    'PlanetTeamSpeak\\TeamSpeak3Framework\\' => __DIR__ . '/src/ts3phpframework/',
    'phpseclib3\\' => __DIR__ . '/src/phpseclib/',
    'Vendor\\Package\\' => __DIR__ . '/src/package/',
);
```

## Creating the Entry File

Create an `index.php` file in your project root.
This file includes the autoloader and creates a connection to the TeamSpeak server.

```php
<?php

require_once __DIR__ . '/autoload.php';

use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

$TN = array(
    'serveradmin' => 'serveradmin',
    'serverpassword' => 'MYPASSWD',
    'serverhost' => '127.0.0.1',
    'serverport' => '10022'
);

$TS = array(
    'serverport' => '9987'
);

try {
    $ts3 = TeamSpeak3::factory(
        'serverquery://'
        . $TN['serveradmin']
        . ':'
        . $TN['serverpassword']
        . '@'
        . $TN['serverhost']
        . ':'
        . $TN['serverport']
        . '/?server_port='
        . $TS['serverport']
        . '&use_offline_as_virtual=1'
        . '&no_query_clients=1'
    );

    $nodeInfo = $ts3->getInfo();
    echo $nodeInfo['virtualserver_platform'];
    
} catch (Exception $e) {
    echo $e->getMessage();
}
```
