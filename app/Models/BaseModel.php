<?php

namespace App\Models;

use PDO;

abstract class BaseModel
{
    public $db;
    protected $table;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function findAll($orderBy = 'id DESC')
    {
        $this->validateOrderBy($orderBy);
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findWhere($column, $value)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function findOneWhere($column, $value)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    public function count($where = '1=1', $params = [])
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteWhere($column, $value)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$column} = ?");
        return $stmt->execute([$value]);
    }

    public function search($fields, $query)
    {
        if (empty($query)) return $this->findAll();
        $conditions = [];
        $params = [];
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
            $params[] = "%{$query}%";
        }
        $where = implode(' OR ', $conditions);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where} ORDER BY id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function paginate($page = 1, $perPage = 20, $where = '1=1', $params = [], $orderBy = 'id DESC')
    {
        $this->validateOrderBy($orderBy);
        $total = $this->count($where, $params);
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        return [
            'data' => $stmt->fetchAll(),
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollback()
    {
        $this->db->rollBack();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    protected function buildInsert($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        return [$columns, $placeholders, array_values($data)];
    }

    protected function buildUpdate($data)
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        return [implode(', ', $fields), $params];
    }

    protected function validateOrderBy($orderBy)
    {
        if ($orderBy && !preg_match('/^[a-zA-Z0-9_\s,\.]+$/', $orderBy)) {
            throw new \InvalidArgumentException("Invalid ORDER BY clause");
        }
    }
}
