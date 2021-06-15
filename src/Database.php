<?php declare(strict_types = 1);

namespace Apitin;

use LengthException;
use LogicException;
use PDO;

class Database extends PDO implements DI
{
    public static function factory(): self
    {
        return new static(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8',
                config('DATABASE_HOSTNAME', 'localhost'),
                config('DATABASE_PORT', 3306),
                config('DATABASE_DATABASE', ''),
            ),
            config('DATABASE_USERNAME', ''),
            config('DATABASE_PASSWORD', '')
        );
    }

    public function one($sql, ...$parameters)
    {
        $result = null;
        $smt    = $this->prepare($sql);

        if ($smt->execute($parameters)) {
            $result = $smt->fetchColumn();
            if ($result === false) {
                throw new LengthException("No result");
            }
        }

        return $result;
    }

    public function first($sql, ...$parameters): array
    {
        $result = [];
        $smt    = $this->prepare($sql);

        if ($smt->execute($parameters)) {
            $result = $smt->fetch(self::FETCH_ASSOC);
            if ($result === false) {
                throw new LengthException("No result");
            }
        }
        $smt->closeCursor();

        return $result;
    }

    public function all($sql, ...$parameters): array
    {
        $result = [];
        $smt    = $this->prepare($sql);

        if ($smt->execute($parameters)) {
            while ($row = $smt->fetch(PDO::FETCH_ASSOC)) {
                if ($row === false) {
                    throw new LengthException("No result");
                }
                $result[] = $row;
            }
        }

        return $result;
    }

    public function insert($table, array $data = [])
    {
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            str_replace('`', '``', $table),
            implode(', ', array_map(function($t) { return sprintf('`%s`', str_replace('`', '``', "{$t}"));  }, array_keys($data))),
            implode(', ', array_map(function($t) { return is_null($t) ? 'NULL' : $this->quote("{$t}"); }, array_values($data)))
        );

        if ($this->exec($sql)) {

            return $this->lastInsertId();

        } else {

            throw new LogicException("Exec failed");

        }
    }

    public function update($table, array $data = [], array $where = [])
    {
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            str_replace('`', '``', $table),
            implode(', ', array_map(function($v, $k) { return sprintf('`%s` = %s', str_replace('`', '``', $k), is_null($v) ? 'NULL' : $this->quote("{$v}")); }, $data, array_keys($data))),
            implode(' AND ', array_map(function($v, $k) { return sprintf('`%s` = %s', str_replace('`', '``', $k), is_null($v) ? 'NULL' : $this->quote("{$v}")); }, $where, array_keys($where))),
        );

        return $this->exec($sql);
    }

    public function replace($table, array $data = [])
    {
        $sql = sprintf(
            'REPLACE INTO `%s` (%s) VALUES (%s)',
            str_replace('`', '``', $table),
            implode(', ', array_map(function($t) { return sprintf('`%s`', str_replace('`', '``', "{$t}"));  }, array_keys($data))),
            implode(', ', array_map(function($t) { return is_null($t) ? 'NULL' : $this->quote("{$t}"); }, array_values($data)))
        );

        if ($this->exec($sql)) {

            return $this->lastInsertId();

        } else {

            throw new LogicException("Exec failed");

        }
    }

    public function delete($table, array $where = [])
    {
        $sql = sprintf(
            'DELETE FROM `%s` WHERE %s',
            str_replace('`', '``', $table),
            implode(' AND ', array_map(function($v, $k) { return sprintf('`%s` = %s', str_replace('`', '``', $k), is_null($v) ? 'NULL' : $this->quote("{$v}")); }, $where, array_keys($where))),
        );

        return $this->exec($sql);
    }
}