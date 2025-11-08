<?php

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $result = $this->db->fetch($sql, ['id' => $id]);
        
        if ($result) {
            return $this->hideFields($result);
        }
        
        return null;
    }

    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        $result = $this->db->fetch($sql, ['value' => $value]);
        
        if ($result) {
            return $this->hideFields($result);
        }
        
        return null;
    }

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $results = $this->db->fetchAll($sql);
        
        return array_map([$this, 'hideFields'], $results);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $totalResult = $this->db->fetch($countSql, $params);
        $total = $totalResult['total'] ?? 0;
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} LIMIT :limit OFFSET :offset";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        
        $results = $this->db->fetchAll($sql, $params);
        $data = array_map([$this, 'hideFields'], $results);
        
        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function create(array $data): string
    {
        $filteredData = $this->filterFillable($data);
        $filteredData['created_at'] = date('Y-m-d H:i:s');
        $filteredData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $filteredData);
    }

    public function update(int $id, array $data): int
    {
        $filteredData = $this->filterFillable($data);
        $filteredData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update(
            $this->table,
            $filteredData,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hideFields(array $data): array
    {
        if (empty($this->hidden)) {
            return $data;
        }
        
        return array_diff_key($data, array_flip($this->hidden));
    }

    public function where(array $conditions): array
    {
        $whereParts = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereParts[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }
        
        $whereClause = implode(' AND ', $whereParts);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause}";
        
        $results = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'hideFields'], $results);
    }
}