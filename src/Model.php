<?php

declare(strict_types=1);

namespace Lucite\Model;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Log\LoggerInterface;
use Lucite\Model\Exception\NotFoundException;

abstract class Model
{
    protected PDO $db;
    protected LoggerInterface $logger;
    protected array $warnings = [];
    protected ?Req $request;

    public static string $tableName = 'SET_ON_CHILD_CLASS';
    public static string $primaryKey = 'SET_ON_CHILD_CLASS';
    public static array $columns = [];
    public static array $readonlyColumns = [];

    public function __construct(PDO $db, LoggerInterface $logger, ?Req $request = null)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function addWarning(string $newWarning): void
    {
        $this->warnings[] = $newWarning;
    }

    public function resetWarnings(): Model
    {
        $this->warnings = [];
        return $this;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get list of columns for SELECTing as a string
     * @return string
     */
    protected function selectColumns(): string
    {
        $columns = array_merge([static::$primaryKey], static::$columns);
        $columns = array_map(function (string $column) {
            return '"'.$column.'"';
        }, $columns);
        return implode(', ', $columns);
    }

    /**
     * Fetch one row
     * @param int|sring $id
     * @return array
     */
    public function fetchOne(int|string $id): array
    {
        # Assemble the full query
        $args = [static::$primaryKey => $id];
        $query = 'SELECT '.$this->selectColumns().' FROM '.static::$tableName;
        $query .= ' WHERE "'.static::$primaryKey.'"=:'.static::$primaryKey;
        $query .= $this->getPermissionFilter($args);

        # Log and execute
        $this->logger->debug('db-select-query: '.$query);
        $this->logger->debug('db-select-params: '.json_encode($args));
        $statement = $this->db->query($query);
        $statement->execute($args);
        $result = $statement->fetch();
        if ($result === false) {
            throw new NotFoundException();
        }
        return $result;
    }

    /**
     * Fetch multiple rows
     * @return array
     */
    public function fetchMany(): array
    {
        # Assemble the full query
        $args = [];
        $query = 'SELECT '.$this->selectColumns().' FROM '.static::$tableName;
        $query .= ' WHERE "'.static::$primaryKey.'" IS NOT NULL';
        $query .= $this->getPermissionFilter($args);

        # Log and execute
        $this->logger->debug('db-select-query: '.$query);
        $this->logger->debug('db-select-params: '.json_encode($args));
        $statement = $this->db->query($query);
        $result = $statement->execute($args);

        if ($result === false) {
            throw new NotFoundException();
        }
        return $statement->fetchAll();
    }

    /**
     * Update an existing row
     * @param int|sring $id
     * @param array $data
     * @return array
     */
    public function update(int|string $id, array $data): array
    {
        $current = $this->fetchOne($id);
        $this->applyPermissionValues($data);

        # Build list of args and set statements
        $set_statements = [];
        $args = [];
        foreach ($data as $column => $new_value) {
            if (in_array($column, static::$readonlyColumns)) {
                if ($new_value !== $current[$column]) {
                    $this->addWarning($column.' is readonly.');
                }
            } elseif (in_array($column, static::$columns)) {
                if ($new_value !== $current[$column]) {
                    $set_statements[] = '"'.$column.'"=:'.$column;
                    $args[$column] = $new_value;
                    $current[$column] = $new_value;
                }
            }
        }

        if (count($args) === 0) {
            $this->logger->debug('db-update: No fields changed, skipping update query');
            return $current;
        }

        # Assemble the full query
        $query = 'UPDATE '.static::$tableName.' SET ';
        $query .= implode(',', $set_statements);
        $query .= ' WHERE "'.static::$primaryKey.'"=:'.static::$primaryKey;
        $args[static::$primaryKey] = $id;
        $query .= $this->getPermissionFilter($args);

        # Log and execute
        $this->logger->debug('db-update-query: '.$query);
        $this->logger->debug('db-update-params: '.json_encode($args));
        $statement = $this->db->prepare($query);
        $result = $statement->execute($args);
        if ($result === false) {
            $this->logger->error('sql-update failure: '.json_encode($this->db->errorInfo()));
            throw new NotFoundException();
        }

        # $current was updated while building the set statements, no need
        # to refetch.
        return $current;
    }

    /**
     * Add a new row
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $this->applyPermissionValues($data);

        $columns = [];
        $placeholders = [];
        $args = [];

        foreach ($data as $column => $value) {
            if (in_array($column, static::$readonlyColumns)) {
                $this->addWarning($column.' is readonly.');
            } elseif (in_array($column, static::$columns)) {
                $columns[] = '"'.$column.'"';
                $placeholders[] = ':'.$column;
                $args[$column] = $value;
            }
        }
        if (count($columns) === 0) {
            $this->logger->debug('db-insert: No fields specified, throwing error');
            throw new NotFoundException();
        }

        # Assemble full query
        $query = 'INSERT INTO '.static::$tableName;
        $query .= ' ('.implode(',', $columns).') VALUES';
        $query .= ' ('.implode(',', $placeholders).') RETURNING *;';

        # Log and execute
        $this->logger->debug('db-insert-query: '.$query);
        $this->logger->debug('db-insert-params: '.json_encode($args));
        $statement = $this->db->prepare($query);
        $result = $statement->execute($args);

        if ($result === false) {
            $this->logger->error('sql-insert failure: '.json_encode($this->db->errorInfo()));
            throw new NotFoundException();
        }

        return $statement->fetch();
    }

    /**
     * Delete a row
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void
    {
        # Assemble the full query
        $args = [static::$primaryKey => $id];
        $query = 'DELETE FROM '.static::$tableName;
        $query .= ' WHERE "'.static::$primaryKey.'"=:'.static::$primaryKey;
        $query .= $this->getPermissionFilter($args);

        # Log and execute
        $this->logger->debug('db-delete-query: '.$query);
        $this->logger->debug('db-delete-params: '.json_encode($args));
        $statement = $this->db->query($query);
        $result = $statement->execute($args);
        if ($result === false) {
            throw new NotFoundException();
        }
    }

    /**
     * Modify data to be passed to ->create or ->update to enforce permissions
     * @param array $data
     * @return void
     */
    abstract public function applyPermissionValues(array &$data): void;

    /**
     * Create sql string that applies permissions to queries for a given request
     * @param array $args
     * @return string
     */
    abstract public function getPermissionFilter(array &$args): string;
}
