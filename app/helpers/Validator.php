<?php
/**
 * Validation Helper
 */

class Validator
{
    private $errors = [];
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function required($field, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->errors[$field][] = "{$label} is required";
        }
        return $this;
    }

    public function email($field, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} must be a valid email address";
        }
        return $this;
    }

    public function min($field, $length, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = "{$label} must be at least {$length} characters";
        }
        return $this;
    }

    public function max($field, $length, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = "{$label} must not exceed {$length} characters";
        }
        return $this;
    }

    public function numeric($field, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = "{$label} must be a number";
        }
        return $this;
    }

    public function in($field, $values, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field][] = "{$label} is invalid";
        }
        return $this;
    }

    public function date($field, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        if (!empty($this->data[$field])) {
            $d = DateTime::createFromFormat('Y-m-d', $this->data[$field]);
            if (!$d || $d->format('Y-m-d') !== $this->data[$field]) {
                $this->errors[$field][] = "{$label} must be a valid date (YYYY-MM-DD)";
            }
        }
        return $this;
    }

    public function unique($field, $table, $column = null, $exceptId = null, $label = null)
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $column = $column ?? $field;
        if (!empty($this->data[$field])) {
            $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
            $params = ['val' => $this->data[$field]];
            if ($exceptId) {
                $sql .= " AND id != :id";
                $params['id'] = $exceptId;
            }
            $count = Database::getInstance()->fetchColumn($sql, $params);
            if ($count > 0) {
                $this->errors[$field][] = "{$label} already exists";
            }
        }
        return $this;
    }

    public function passes()
    {
        return empty($this->errors);
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function firstError()
    {
        foreach ($this->errors as $messages) {
            if (!empty($messages)) return $messages[0];
        }
        return null;
    }
}
