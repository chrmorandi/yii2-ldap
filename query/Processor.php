<?php

namespace chrmorandi\ldap\Query;

use chrmorandi\ldap\interfaces\ConnectionInterface;
use chrmorandi\ldap\interfaces\SchemaInterface;
use chrmorandi\ldap\models\Entry;
use chrmorandi\ldap\objects\Paginator;
use Illuminate\Support\Collection;

class Processor
{
    /**
     * @var QueryBuilder
     */
    protected $builder;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Constructor.
     *
     * @param QueryBuilder $builder
     */
    public function __construct(QueryBuilder $builder)
    {
        $this->builder = $builder;
        $this->connection = $builder->getConnection();
        $this->schema = $builder->getSchema();
    }

    /**
     * Processes LDAP search results and constructs their model instances.
     *
     * @param resource $results
     *
     * @return array
     */
    public function process($results)
    {
        $entries = $this->connection->getEntries($results);

        if ($this->builder->isRaw() === true) {
            return $entries;
        } else {
            $models = [];

            if (is_array($entries) && array_key_exists('count', $entries)) {
                for ($i = 0; $i < $entries['count']; $i++) {
                    $models[] = $this->newLdapEntry($entries[$i]);
                }
            }

            // If the current query isn't paginated,
            // we'll sort the models array here.
            if (!$this->builder->isPaginated()) {
                $models = $this->processSort($models);
            }

            return $models;
        }
    }

    /**
     * Processes paginated LDAP results.
     *
     * @param array $pages
     * @param int   $perPage
     * @param int   $currentPage
     *
     * @return Paginator|bool
     */
    public function processPaginated($pages, $perPage = 50, $currentPage = 0)
    {
        // Make sure we have at least one page of results.
        if (count($pages) > 0) {
            $models = [];

            // Go through each page and process the results into an objects array.
            foreach ($pages as $results) {
                $processed = $this->process($results);

                $models = array_merge($models, $processed);
            }

            $models = $this->processSort($models);

            // Return a new Paginator instance.
            return $this->newPaginator($models->toArray(), $perPage, $currentPage, count($pages));
        }

        // Looks like we don't have any results, return false
        return false;
    }

    /**
     * Returns a new LDAP Entry instance.
     *
     * @param array $attributes
     *
     * @return Entry
     */
    public function newLdapEntry(array $attributes = [])
    {
        $attribute = $this->schema->objectClass();

        if (array_key_exists($attribute, $attributes) && array_key_exists(0, $attributes[$attribute])) {
            // Retrieve all of the object classes from the LDAP
            // entry and lowercase them for comparisons.
            $classes = array_map('strtolower', $attributes[$attribute]);

            // Retrieve the model mapping.
            $models = $this->map();

            // Retrieve the object class mappings (with strtolower keys).
            $mappings = array_map('strtolower', array_keys($models));

            // Retrieve the model from the map using the entry's object class.
            $map = array_intersect($mappings, $classes);

            if (count($map) > 0) {
                // Retrieve the objectclass attribute from the map.
                $class = current($map);

                // Retrieve the model from using the object class.
                $model = $models[$class];

                // Construct and return a new model.
                return $this->newModel([], $model)->setRawAttributes($attributes);
            }
        }

        // A default entry model if the object category isn't found.
        return $this->newModel()->setRawAttributes($attributes);
    }

    /**
     * Creates a new model instance.
     *
     * @param array       $attributes
     * @param string|null $model
     *
     * @return mixed|Entry
     */
    public function newModel($attributes = [], $model = null)
    {
        if (!is_null($model) && class_exists($model)) {
            return new $model($attributes, $this->builder);
        }

        return new Entry($attributes, $this->builder);
    }

    /**
     * Returns a new Paginator object instance.
     *
     * @param array $models
     * @param int   $perPage
     * @param int   $currentPage
     * @param int   $pages
     *
     * @return Paginator
     */
    public function newPaginator(array $models, $perPage = 25, $currentPage = 0, $pages = 1)
    {
        return new Paginator($models, $perPage, $currentPage, $pages);
    }

    /**
     * Returns a new doctrine array collection instance.
     *
     * @param array $elements
     *
     * @return Collection
     */
    public function newCollection(array $elements = [])
    {
        return new Collection($elements);
    }

    /**
     * Returns the object category model class mapping.
     *
     * @return array
     */
    public function map()
    {
        return [
            $this->schema->objectClassComputer()    => \Adldap\models\Computer::class,
            $this->schema->objectClassContact()     => \Adldap\models\Contact::class,
            $this->schema->objectClassPerson()      => \Adldap\models\User::class,
            $this->schema->objectClassGroup()       => \Adldap\models\Group::class,
            $this->schema->objectClassContainer()   => \Adldap\models\Container::class,
            $this->schema->objectClassPrinter()     => \Adldap\models\Printer::class,
            $this->schema->objectClassOu()          => \Adldap\models\OrganizationalUnit::class,
        ];
    }

    /**
     * Sorts LDAP search results.
     *
     * @param array $models
     *
     * @return Collection
     */
    protected function processSort(array $models = [])
    {
        $collection = $this->newCollection($models);

        $field = $this->builder->getSortByField();

        $flags = $this->builder->getSortByFlags();

        $direction = $this->builder->getSortByDirection();

        $desc = ($direction === 'desc' ? true : false);

        return $collection->sortBy(function (Entry $model) use ($field) {
            return $model->getAttribute($field, 0);
        }, $flags, $desc);
    }
}
