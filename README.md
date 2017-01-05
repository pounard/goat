# Goat

Database connector, selection immutability, project mapping.

This tool aims to cover the same areas as most ORM will do, with a radically
different software design and approach:

 *  you shall not map relations onto objects: objects are mutable graphs while
    relations are a mathematical concept, both do not play well together;

 *  selecting data is projecting a unique set of data at a specific point in
    time: selected data is not truth, it's only a representation of it;

 *  selected data will always be immutable, you need it for view or display
    purpose, but since it only represents a degraded, altered projection of your
    data at a specific point in time, you should never modify it; as soon as
    you did select data, someone else probably already did modify it!

 *  selected data will always be typed, never cast strings ever again! Your
    database knows better than you the data types it carries, why not trust it
    and let you enjoy what the database really gave to you?

 *  data alteration (ie. insertion, updates and deletion) can not happen using
    objects as references, you can not alter something that's already outdated;

 *  everyone needs a query builder; but everyone needs to be able to write real
    SQL queries too;

 *  CRUD is a bitch and too much a standard now, consider yourself as being
    lobotomized each time you forget that other solutions exists. Never trust
    CRUD.

# Low-level software design

 *  Connection: gives you a bare API to implement if you need to plug this onto
    another database and write plain SQL queries, this where lies the data type
    conversion API too;

 *  Query writer: tied to the connection namespace, this is where you need to
    plug yourself if you need to write your own connector;

 *  Query builder: relies on the query writer for SQL formatting, gives you a
    nice API that makes your life easy for modifying dynamic SQL queries;

 *  Selection: write any kind of mental-illed SQL, or use the query builder for
    this, and fetch and map all your data onto temporary immutable object
    representation of your data;

 *  Transaction: provide an easy enough context switching helper that will allow
    you to write safe and fun transations;

 *  Writer: go and modify all the thing.
