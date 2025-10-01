<?php

namespace Coachview\Api;

class QueryBuilder {
    private $query = [];

    public function includeFreeFields($include = true) {
        $this->query['InclusiefVrijevelden'] = $include ? 'true' : 'false';
        return $this;
    }

    public function includeExtraFields($include = true) {
        $this->query['InclusiefExtraVelden'] = $include ? 'true' : 'false';
        return $this;
    }

    public function includeDirectRelations($include = true) {
        $this->query['InclusiefDirecteRelaties'] = $include ? 'true' : 'false';
        return $this;
    }

    public function where(string $field, $value, $operator = '=') {
       $where_clause = "$field$operator$value";
        if (isset($this->query['where'])) {
            $this->query['where'] .= ";$where_clause";
        } else {
            $this->query['where'] = $where_clause;
        }
        return $this;
    }

    public function order_by($field, $direction = 'asc') {
        $this->query['orderby'] = "$field $direction";
        return $this;
    }

    public function skip($skip) {
        $this->query['skip'] = $skip;
        return $this;
    }

    public function take($take) {
        $this->query['take'] = $take;
        return $this;
    }

    public function build(): array {
        return $this->query;
    }
}