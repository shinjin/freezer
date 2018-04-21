# Freezer

[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-coveralls]][link-coveralls]

A cool object storage library.

Use freezer if you need:
* an easy way to store and fetch objects and object graphs
* a schemaless datastore
with drivers for PDO, DoctrineCache, and CouchDB
## Install

Via Composer

``` bash
$ composer require shinjin/freezer
```

## Usage

``` php
use Freezer\Storage\Pdo;

$storage = new Pdo(array('driver' => 'sqlite'));

$caveman = new class
{
    public $name        = 'Brendan';
    public $nationality = 'Canadian';
};

$id = $storage->store($caveman);

// wait 2 million years

$caveman = $storage->fetch($id);

print_r($caveman);

// class@anonymous Object
// (
//     [name] => Brendan
//     [nationality] => Canadian
// )
```
See [Usage](docs/Usage.md) and [Old README](docs/OldREADME.md) for the original writeup.

## Change log

See [CHANGELOG](CHANGELOG.md).

## Testing

``` bash
$ composer test
```

## Contributing

Bugfixes are welcome. Please submit pull requests to [Github][link-github].

## Authors

- [Rick Shin][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Acknowledgements

Freezer is based on php-object-freezer by [Sebastian Bergmann][link-sebastian]. Most of freezer's core and test code is his. Sebastian Bergmann is not affiliated with this project in any way, shape, or form.

[ico-coveralls]: https://coveralls.io/repos/github/shinjin/freezer/badge.svg
[ico-travis]: https://img.shields.io/travis/shinjin/freezer/master.svg?style=flat-square

[link-author]: https://github.com/shinjin
[link-github]: https://github.com/shinjin/freezer
[link-coveralls]: https://coveralls.io/github/shinjin/freezer
[link-travis]: https://travis-ci.org/shinjin/freezer
[link-sebastian]: https://github.com/sebastianbergmann 
