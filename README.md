# Goat

Database connector, selection immutability, data mapping.


# Driver support

 *  Complete MySQL 5.5 and higher (via `PDO`);
 *  Complete PostgreSQL 9.1 and higher (via `ext_pgsql` or `PDO`);
 *  Partial MySQL 5.1 support (via `PDO`);
 *  Partial PostgreSQL 7.4 support (via `ext_pgsql` or `PDO`);


# Integration

 *  [Experimental Symfony 3.2 bundle](https://github.com/pounard/goat-bundle)


# Status

The following API are available:

 *  connection and session API, allows you to handle dual-connection mode with
    one readonly connection and a write connection;

 *  converter map, allowing native SQL to native PHP type conversion in both
    ways;

 *  complete transaction handling, with isolation level, savepoint and
    constraint deferring support;

 *  raw SQL quering, prepared statements management;

 *  query builder API, supporting select, insert, update and delete.

**This API is highly experimental and subject to change!**


# Concept

This tool aims to cover the same areas as most ORM will do, with a radically
different software design and approach:

 *  you shall not map relations onto objects: objects are mutable graphs while
    relations are a mathematical concept, both do not play well together;

 *  selecting data is projecting a unique set of data at a specific point in
    time: selected data is not the truth, it's only a representation of it;

 *  selected data will always be immutable, you need it for viewing or
    displaying purpose, but since it only represents a degraded, altered
    projection of your data at a specific point in time, you should never
    modify it; as soon as you did selected data, someone else probably already
    modified it!

 *  selected data will always be typed, never cast strings ever again! Your
    database knows better than you the data types it carries, why not trust it
    and let you enjoy what the database really gives to you?

 *  data alteration (insertion, update, merge and deletion) can not happen using
    entity objects, you can not alter something that's already outdated;

 *  everyone needs a query builder; but everyone needs to be able to write real
    SQL queries too;

 *  CRUD is too much of a standard now, but there are so many cases where you
    don't need it.


# Documentation

 *  Get started, installation and configuration guide (@todo)
 *  Write SQL (@todo)
 *  Query builders (@todo)
 *  Transactions (@todo)


# Todolist

 *  [pending] allow named parameters
 *  [pending] mapper: table proxy interface
 *  [postponed] better parameter handling in AbstractConnection
 *  [postponed] DDL ALTER TABLE? - not sure I want this right now
 *  [postponed] DDL CREATE TABLE? - not sure I want this right now
 *  [postponed] ext-pgsql driver
 *  [postponed] INSERT/UPDATE fallback when RETURNING is not supported
 *  [postponed] MERGE queries
 *  [postponed] performance: reduce functions calls for converters
 *  [postponed] SELECT with sub-select at select level
 *  [postponed] UNION queries
 *  [postponed] WITH support
 *  [x] <strike>performance: improve get column metadata for PDO</strike>: impossible due to native calls
 *  [x] converters: change method names to avoid confusion with hydrator
 *  [x] converters: per default better definition
 *  [x] converters: should carry a type and aliases (better auto detection)
 *  [x] DDL TRUNCATE
 *  [x] DDL TRUNCATE testing
 *  [x] DELETE queries
 *  [x] DELETE queries testing
 *  [x] expression vs statement: query builder improvements
 *  [x] ext_pgsql: basic implementation
 *  [x] ext_pgsql: document it is both faster and more secure
 *  [x] ext_pgsql: improve error handling
 *  [x] generic way to dissociate raw SQL string from raw values
 *  [x] improve WHERE builder tests: raw statement / sub where
 *  [x] mapper: basic object mapping (using hydrator)
 *  [x] mapper: object hydration mechanism
 *  [x] mapper: object mapping
 *  [x] move source to src/ folder
 *  [x] parametric testing for backends
 *  [x] RIGHT and FULL JOIN types
 *  [x] security: identifier testing https://github.com/minimaxir/big-list-of-naughty-strings
 *  [x] security: parameter injcetion testing using https://github.com/minimaxir/big-list-of-naughty-strings
 *  [x] session: add basic session support (dual connection handling)
 *  [x] switch to strict types
 *  [x] transaction support
 *  [x] transaction: test allow pending
 *  [x] transaction: test savepoints
 *  [x] transaction: test weak ref handling (only when extension is present)
 *  [x] untangle ArgumentBag
 *  [x] UPDATE queries
 *  [x] UPDATE query testing
 *  [x] WHERE with SELECT within
 *  converters: specific instances per driver
 *  converters: type map per driver
 *  mapper: createSelect()
 *  mapper: findAll() sorting order
 *  mapper: findFirst() tests
 *  mapper: order by
 *  mapper: range
 *  mapper: various getters tests
 *  MySQL default transaction level in configuration
 *  Query cloning does not clone relation (object is immutable)
 *  session: connection: add logger and notifications
 *  session: test with write and read connections
 *  session: write-only/read-only connection support, fallback when transaction
 *  transaction: document deffer helpers
 *  transaction: document immediate per default
 *  transaction: document isolation levels
 *  transaction: FOR UPDATE / FOR SHARE query dissociation from SELECT
 *  travis: basic integration
 *  travis: create a test suite per (driver, database version target) couple
 *  travis: use docker to test
 *  WHERE builder tests: subqueries tests
