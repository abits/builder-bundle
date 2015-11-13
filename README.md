# builder-bundle

## Tools for Symfony Projects

This package is supposed to collect several tools for interfacing a Symfony
application. It provides "missing" tasks for automation and deployments.  The
package relies heavily on the symfony/console and symfony/process components.

## Installation

### Step 1: Download FufBuilderBundle using composer

```shell
$ composer require fuf/builder-bundle "dev-master"
```

You might consider using a tagged version for your project.

### Step 2: Enable the bundle

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Fuf\BuilderBundle\FufBuilderBundle(),
        // ...
    );
}
```


## Requirements

The package uses some common tools which should be available on your system:

1. mysqldump
2. gzip

These executables should be accessible for the user running the commands. Be
aware that mysqldump has to be installed separately from mysql-server and
mysql-client components on some systems.


## Usage

For now the bundle provides two tasks.

```shell
$ php app/console fuf:db-conn
symfony;root;%
```

This command returns the database connection data as a machine readable
string. This is primarily a helper tasks for consumption by build or
deployment tools, where we would not want to engage a full-on yaml parser.
Empty fields are delivered as empty strings.

The second command allows you to quickly dump a MySQL (or MariaDB) database
for your project.  

```shell
$ php app/console fuf:sql-dump
Dumped database to symfony_20151113_161108.sql. Resulting file size: 0.0022 MB.
```

You may add the flag `--compress` in order to gzip the dump file. The dump
file name is composed of the database name and a timestamp. The result file
size is calculated and printed, so that you can check if it matches your
expectations. The flag `--skip` allows you to specify a comma separated list of
names for tables which are not exported.  

```shell
$ php app/console fuf:sql-dump --skip=cache,log
Dumped database to symfony_20151113_161108.sql. Resulting file size: 0.0007 MB.
```

The `--debug` flag gives some additional output.


