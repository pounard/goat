<pre>
# Mapping

select -> immutable objects
form -> mapping object

# Namespaces

Driver
  Connection
  ResultIterator
  WhereBuilder
  ProjectionBuilder
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
    StandardProjectionBuilder
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
</pre>
<?php

/** @var \Goat\Core\Client\ConnectionInterface $database */
$database = null;

// From
$proxy = $database
    ->getMapper('name')
    ->findOne() // automatic select query on pkey
    // or ->someCustomMethod() // written by user
    // execute() or map()
    ->map()
;
$form = $controller->createFormBuilder($proxy); // ...
if ($form->isSubmitted() && $form->isValid()) {
    $database->getMapper('name')->upsert($proxy);
}

// Reading
$parameters = [':c' => 'some_value'];
$result = $database
    ->query("SELECT a, b, c FROM a JOIN b WHERE c = :c")
    ->execute($parameters, SomeClass::class /* can be ommited */)
;

// Reading with query builder
$query = $database->createQueryBuilder();
$parameters = [':c' => 'some_value'];
$query->select(['a', 'b', 'c']);
$query->from('a');
$query->join('b');
$query->where('c', ':c');
$result = $database
    ->select($query)
    ->execute($parameters, SomeClass::class /* can be ommited */)
;

// $result is an iterator
foreach ($result as $item) {
    /** @var $result SomeClass */ // Always true
    do_something($item);
}


