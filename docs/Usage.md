# Usage

## Connecting
Freezer offers storage adapters for:
1. PDO
2. Doctrine Cache
3. CouchDB

The fourth **ChainStorage** adapter combines any of the previous adapters allowing faster storages (eg. filesystem) to cache slower ones (eg. MySQL).

Each storage adapter is created a bit differently. 

### Pdo
The Pdo storage adapter can accept either a PDO object.
``` php
$pdo = new \PDO('sqlite::memory:');
$storage = new \Freezer\Storage\Pdo($pdo);
```
Or a list of parameters. The parameters can specify the PDO dsn.
``` php
$params  = array('dsn' => 'sqlite::memory:');
$storage = new \Freezer\Storage\Pdo($params);
```
Or a list of conventional PDO db parameters.
``` php
$params = array(
    'driver'   => 'mysql',
    'dbname'   => 'myapp',
    'tblname'  => 'freezer',
    'host'     => 'localhost',
    'port'     => 3306,
    'user'     => 'shinjin',
    'password' => 'awesomepasswd'    
);
$storage = new \Freezer\Storage\Pdo($params);
```
See the [shinjin/pdo docs][link-pdo-docs] for more details.

### Doctrine Cache
The Doctrine Cache adapter accepts an instance of the Doctrine Cache provider.
``` php
$cache_provider = new \Doctrine\Common\Cache\ArrayCache;
$storage = new \Freezer\Storage\DoctrineCache($cache_provider);
```

### CouchDB
The Doctrine Cache adapter accepts the database name and optionally, host and port.
``` php
$storage = new \Freezer\Storage\CouchDB('myapp', null, null, 'localhost', 5984);
```
### ChainStorage
The ChainStorage adapter accepts an array containing an arbitrary list of freezer storage objects.
``` php
$storages = array(
    new \Doctrine\Common\Cache\ArrayCache,
    new \Freezer\Storage\Pdo(
        new \PDO('sqlite::memory:')
    )
);

$storage = new \Freezer\Storage\ChainStorage($storages);
```
The order of the storage objects is important. The adapter loops through the storage chain starting with the first object. Therefore it makes sense order the storages from fastest to slowest.


[link-pdo-docs]: https://github.com/shinjin/pdo/blob/master/docs/Usage.md#connecting