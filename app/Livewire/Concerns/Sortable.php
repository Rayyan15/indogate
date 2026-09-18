<?php

namespace App\Livewire\Concerns;

/**
 * Shared click-to-sort behavior for admin list Livewire components. Pair
 * with x-ui.th's :sortable/:field props (pass $sortField/$sortDirection
 * from the component) and applySort() in render()'s query.
 *
 * Deliberately does NOT support sorting by translatable JSON columns
 * (name/description) — ORDER BY on a raw JSON column sorts by its
 * serialized string, not the locale-appropriate value, which is
 * incorrect rather than just imprecise. Only sort by plain scalar
 * columns (type, dates, counts, etc).
 */
trait Sortable
{
    public string $sortField = '';

    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * @param  array<int, string>  $allowedFields  Whitelist — never pass
     *                                             $this->sortField straight
     *                                             into orderBy().
     */
    protected function applySort($query, array $allowedFields, string $defaultField, string $defaultDirection = 'desc')
    {
        $field = in_array($this->sortField, $allowedFields, true) ? $this->sortField : $defaultField;
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : $defaultDirection;

        return $query->orderBy($field, $this->sortField ? $direction : $defaultDirection);
    }
}
