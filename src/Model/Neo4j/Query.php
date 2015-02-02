<?php

namespace Model\Neo4j;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class Query extends \Everyman\Neo4j\Cypher\Query
{

    public function getExecutableQuery()
    {

        $query = $this->getQuery();

        foreach ($this->getParameters() as $parameter => $value) {

            $replace = null;

            switch (gettype($value)) {
                case 'boolean':
                    $replace = $value ? 'true' : 'false';
                    break;
                case 'integer':
                case 'double':
                    $replace = $value;
                    break;
                case 'string':
                    $replace = '"' . $value . '"';
                    break;
                case 'array':
                    $replace = is_string(current($value)) ? '"' . implode('", "', $value) . '"' : implode(', ', $value);
                    break;
            }

            if ($replace) {
                $pattern = sprintf('/{\s?%s\s?}/', $parameter);
                $query = preg_replace($pattern, $replace, $query);
            }
        }

        return $query;
    }
}