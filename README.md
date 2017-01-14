# Goat

Database connector, selection immutability, project mapping.

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

# Status

As of now, the following API are available (but highly subject to change):

 *  connection and session API, allows you to handle dual-connection mode with
    one readonly connection and a write connection;

 *  converter map, allowing native SQL to native PHP type conversion in both
    ways;

 *  complete transaction handling, with isolation level, savepoint and
    constraint deferring support;

 *  raw SQL quering, prepared statements management;

 *  query builder API.

# Documentation

 *  Get started, installation and configuration guide (@todo)
 *  Write SQL (@todo)
 *  Query builders (@todo)
 *  Transactions (@todo)

# Todolist

 *  [pending] allow named parameters
 *  [postponed] DDL ALTER TABLE? - not sure I want this right now
 *  [postponed] DDL CREATE TABLE? - not sure I want this right now
 *  [postponed] ext-pgsql driver
 *  [postponed] INSERT/UPDATE fallback when RETURNING is not supported
 *  [postponed] MERGE queries
 *  [postponed] SELECT with sub-select at select level
 *  [postponed] UNION queries
 *  [postponed] WITH support
 *  [x] converters: should carry a type and aliases (better auto detection)
 *  [x] DDL TRUNCATE
 *  [x] DDL TRUNCATE testing
 *  [x] DELETE queries
 *  [x] DELETE queries testing
 *  [x] expression vs statement: query builder improvements
 *  [x] ext_pgsql: basic implementation
 *  [x] generic way to dissociate raw SQL string from raw values
 *  [x] improve WHERE builder tests: raw statement / sub where
 *  [x] mapper: object hydration mechanism
 *  [x] parametric testing for backends
 *  [x] RIGHT and FULL JOIN types
 *  [x] session: add basic session support (dual connection handling)
 *  [x] transaction support
 *  [x] transaction: test allow pending
 *  [x] transaction: test savepoints
 *  [x] transaction: test weak ref handling (only when extension is present)
 *  [x] untangle ArgumentBag
 *  [x] UPDATE queries
 *  [x] UPDATE query testing
 *  [x] WHERE with SELECT within
 *  better parameter handling in AbstractConnection
 *  converters: change method signature ('hydrate' is wrong)
 *  converters: per default better definition (session builder?)
 *  converters: specific instances per driver
 *  converters: type map per driver
 *  ext_pgsql: document it is both faster and more secure
 *  ext_pgsql: improve error handling
 *  mapper: advanced object mapping
 *  mapper: basic object mapping
 *  MySQL default transaction level in configuration
 *  performance: improve get column metadata for PDO
 *  performance: reduce functions calls for converters
 *  Query cloning does not clone relation (object is immutable)
 *  session: test with write and read connections
 *  session: write-only/read-only connection support, fallback when transaction
 *  transaction: document deffer helpers
 *  transaction: document immediate per default
 *  transaction: document isolation levels
 *  transaction: FOR UPDATE / FOR SHARE query dissociation from SELECT
 *  WHERE builder tests: subqueries tests

# Driver support

 *  Complete MySQL 5.5 and higher (via PDO);
 *  Complete PostgreSQL 9.1 and higher (via ext_pgsql or PDO);
 *  Partial MySQL 5.1 support (via PDO);
 *  Partial PostgreSQL 7.4 support (via ext_pgsql or PDO);

# Documentation

## Writing SQL

### Parameters handling

This API doesn't support named parameters. You may use either ordered or
anonymous parameters:

 *  ordered parameters must all be of the form ``$N`` where N is a positive
    integer, if you use identified parameters, you need to identify all of them
    without any exception, count must start with 1 and there must be NO holes
    in the numbering; this allows you to re-use the same parameter more than
    once, the numbering matches the ``index - 1`` in the ``$parameters``
    array sent to the ``query()`` or ``perform()`` method;

 *  anonymous parameters are all written using ``$*``, parameters sent to the
    database will be the same than the one in the ``$parameters`` array sent to
    the ``query()`` or ``perform()`` method.

**You cannot mix ordered and anonymous parameters**

It's also important to notice that you cannot use ordered parameters when using
a query builder, the query builder will manage its parameters by itself.

#### Anonymous parameters usage example

#### Ordered parameters usage example
