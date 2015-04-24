# Median MongoDB Databases

Median uses two primary databases: `median5` and `median6`. With a little refactoring work, this could be combined into one `median` database, which you're welcome to do.

## median5

The `median5` database has the following collections:

- activity
- alerts
- api
- comments
- download_slots
- downloads
- error_log
- groups
- helpfiles
- increments
- live
- livestats
- mail
- media
- meta
- metafields
- news
- playlists
- users
- views

## median6

The `median6` database has the following collections:

- captions
- file_ops
- log
- servers

## indexes

The indexes for these databases are documented in `mongodb-indexes.md`.