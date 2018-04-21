# Usage

## Connecting
Freezer offers storage adapters for:
1. PDO
2. Doctrine Cache
3. CouchDB

The fourth **ChainStorage** adapter combines any of the previous adapters allowing faster storages (eg. filesystem) to cache slower ones (eg. MySQL).

Each storage adapter is created a bit differently. 

### Pdo
The Pdo storage adapter can accept either a PDO object:
``` php
$pdo = new \PDO('sqlite::memory:');
$storage = new \Freezer\Storage\Pdo($pdo);
```
A [Shinjin\Pdo\Db][link-shinjin-pdo] object:
``` php
$pdo = new \PDO('sqlite::memory:');
$db  = new \Shinjin\Pdo\Db($pdo);
$storage = new \Freezer\Storage\Pdo($db);
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

## Storing and Fetching Objects
Storing and fetching objects is straightforward. To store an object, pass the object to the storage's **store** method. The store method returns the object's Freezer id.
``` php
$storage = new Freezer\Storage\Pdo(array('driver' => 'sqlite'));

$caveman = new class
{
    public $name        = 'Brendan';
    public $nationality = 'Canadian';
};

$id = $storage->store($caveman);

// Freezer stores the object and generates a UUID

```

To retrieve the object, pass the object's Freezer id to the **fetch** method.
``` php
$storage = new Freezer\Storage\Pdo(array('driver' => 'sqlite'));

$caveman = $storage->fetch($id);

print_r($caveman);

// class@anonymous Object
// (
//     [name] => Brendan
//     [nationality] => Canadian
// )
```

Freezer neatly handles object graphs (objects within objects) and circular references.

## Freezer and Storage Options
Specify a custom object id by passing an object property name to Freezer's constructor. Freezer will use this property as the object's Freezer id.
``` php
$freezer = new \Freezer\Freezer('key');

$storage = new Freezer\Storage\Pdo(array('driver' => 'sqlite'), $freezer);

$caveman = new class
{
    public $key         = 1;
    public $name        = 'Brendan';
    public $nationality = 'Canadian';
};

// Freezer will use the object's "key" property (in this case, 1) for the object's id
```

Instruct Freezer to lazy load aggregate objects by setting Storage's **useLazyLoad** argument to **true**. 
``` php
$use_lazyload = true;

$storage = new Freezer\Storage\Pdo(array('driver' => 'sqlite'), null, $use_lazyload);

$caveman = new class
{
    public $name = 'Brendan';
};

$caveman->nationality = new class
{
    public $name = 'Canadian';
}

$id = $storage->store($caveman);

$caveman = $storage->fetch($id);

// $caveman->nationality is an instance of Freezer\LazyProxy

print_r($caveman->nationality->name);

// Referencing a property or calling a method forces Freezer to fetch the (nationality) object from the datastore
```

[link-shinjin-pdo]: https://github.com/shinjin/pdo/
[link-pdo-docs]: https://github.com/shinjin/pdo/blob/master/docs/Usage.md#connecting