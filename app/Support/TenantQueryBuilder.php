<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder;

class TenantQueryBuilder extends Builder
{
    private readonly int $tenantId;

    public function __construct(
        ConnectionInterface $connection,
        ?Grammar $grammar = null,
        ?Processor $processor = null,
        ?int $tenantId = null,
    ) {
        parent::__construct($connection, $grammar, $processor);
        $this->tenantId = $tenantId ?? TenantContext::id()
            ?? throw new \LogicException('A tenant context is required for tenant queries.');
    }

    public function newQuery(): static
    {
        return new static($this->connection, $this->grammar, $this->processor, $this->tenantId);
    }

    public function insert(array $values)
    {
        return parent::insert($this->addTenantId($values));
    }

    public function insertOrIgnore(array $values)
    {
        return parent::insertOrIgnore($this->addTenantId($values));
    }

    public function insertGetId(array $values, $sequence = null)
    {
        return parent::insertGetId($this->addTenantId($values), $sequence);
    }

    public function updateOrInsert(array $attributes, array|callable $values = [])
    {
        if (isset($attributes['tenant_id']) && (int) $attributes['tenant_id'] !== $this->tenantId) {
            throw new \InvalidArgumentException('A record cannot be written to another company.');
        }

        $attributes['tenant_id'] = $this->tenantId;

        if (is_array($values) && isset($values['tenant_id']) && (int) $values['tenant_id'] !== $this->tenantId) {
            throw new \InvalidArgumentException('A record cannot be written to another company.');
        }

        if (is_array($values)) {
            $values['tenant_id'] = $this->tenantId;
        }

        return parent::updateOrInsert($attributes, $values);
    }

    public function upsert(array $values, array|string $uniqueBy, ?array $update = null)
    {
        $values = $this->addTenantId($values);
        $uniqueBy = array_values(array_unique(array_merge((array) $uniqueBy, ['tenant_id'])));

        return parent::upsert($values, $uniqueBy, $update);
    }

    private function addTenantId(array $values): array
    {
        if ($values === []) {
            return $values;
        }

        if (! is_array(reset($values))) {
            if (isset($values['tenant_id']) && (int) $values['tenant_id'] !== $this->tenantId) {
                throw new \InvalidArgumentException('A record cannot be written to another company.');
            }

            $values['tenant_id'] = $this->tenantId;
            return $values;
        }

        foreach ($values as &$row) {
            if (isset($row['tenant_id']) && (int) $row['tenant_id'] !== $this->tenantId) {
                throw new \InvalidArgumentException('A record cannot be written to another company.');
            }

            $row['tenant_id'] = $this->tenantId;
        }
        unset($row);

        return $values;
    }
}
