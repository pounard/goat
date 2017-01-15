# Mapping

select -> immutable objects
form -> mapping object

# Namespaces

Driver
  Connection
  ResultIterator
  WhereBuilder
  Transaction

Core
  Session
  Converter
    Standard[Type]Converter
  Query
    *Query
      // Build all kinds of queries
    Where
      // All methods to build a where
  Impl
    ResultIteratorTrait
    StandardWhereBuilder

Mapper
  Model # Arbitrary user methods, no table
  ResultHydrator
    hydrate(query, class = null)
  TableMapper(table): Model
    findOne(void[] primaryKey)
    findAll(Where where)
  Form
    ClassDataProxy
      __contruct(void object, string[] columns = null)
      __set()
      __get()
      getModifiedProperties()
      getAllProperties()
      getProperties(string[] columns)

Writer
  TableMapperWriter(table): TableMapper(table)
    insert(string table, array|ClassDataProxy data)
    update(string table, array|ClassDataProxy data)
    upsert(string table, array|ClassDataProxy data)
    delete(Where where)

Transaction
  ForUpdateQuery: Query
  Transaction
    forUpdate(): ForUpdateQuery
