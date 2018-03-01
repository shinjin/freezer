# Changelog

## [Unreleased]
- Nothing

## [0.1.0] - 2018-02-13
- Initial alpha release

## [0.2.0] - 2018-02-20
- Replace the Storage::store $id param with Freezer $idAttribute param
  to configure the property name Freezer uses for object ids
- Replace __freezer_hash with __freezer property for general use
- Fix CouchDB update bug and update Pdo storage to execute proper upserts

## [0.3.0] - 2018-02-26
- Update Freezer $blacklist param to filter out object properties
- Update LazyProxy magic methods to delegate calls directly rather than using reflection
- Minor bugfixes

## [0.4.0] - 2018-03-01
- Replace Freezer $blacklist param with $propertyReader callback to iterate object properties
- Implement proper storage handling for LazyProxy objects
