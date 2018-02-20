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
