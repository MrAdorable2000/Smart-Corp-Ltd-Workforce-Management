<?php
/**
 * Base Model Class
 * Provides common CRUD operations using PDO
 */

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by primary key
     */
    public function find($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Find by column value
     */
    public function findBy($column, $value)
    {
        $sql = "SELECT * FROM `this->table}` WHERE `{$column}` = :val LIMIT 1";
        return $this->db->fetch($sql, ['val' => $value]);
    }

    /**
     * Get all records
     */
    public function all($limit = null, $offset = 0, $orderBy = null, $direction = 'ASC')
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        if ($limit) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * Create new record
     */
    public function create(array $data)
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update record by primary key
     */
    public function update($id, array $data)
    {
        $data = $this->filterFillable($data);
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update(
            $this->table,
            $data,
            "`{$this->primaryKey}` = :id",
            ['id' => $id]
        );
    }

    /**
     * Delete record by primary key
     */
    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            "`{$this->primaryKey}` = :id",
            ['id' => $id]
        );
    }

    /**
     * Count records
     */
    public function count($where = '1=1', $params = [])
    {
        return $this->db->count($this->table, $where, $params);
    }

    /**
     * Custom query - fetch all
     */
    public function query($sql, $params = [])
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Custom query - fetch single row
     */
    public function queryOne($sql, $params = [])
    {
        return $this->db->fetch($sql, $params);
    }

    /**
     * Filter only fillable fields
     */
    protected function filterFillable(array $data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 20, $where = '1=1', $params = [], $orderBy = 'id DESC')
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($where, $params);
        $totalPages = ceil($total / $perPage);

        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY {$orderBy} LIMIT {$offset}, {$perPage}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
            'from'        => $total > 0 ? $offset + 1 : 0,
            'to'          => min($offset + $perPage, $total),
        ];
    }
}
