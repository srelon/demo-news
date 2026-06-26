<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminAccesses;
use App\Models\Admin\AdminRules;

class AdminRuleService
{
    public function rules()
    {
        return AdminRules::where('active', 1)->get();
    }

    public function accesses(int $id): ?array
    {
        $rule = AdminRules::select(['id', 'name', 'accesses_id', 'active'])->find($id);

        if (!$rule) {
            return null;
        }

        return [
            'accesses' => AdminAccesses::select(['id', 'key', 'descriptions'])->get(),
            'rule' => $rule,
        ];
    }

    public function createAccess(string $key, ?string $descriptions): AdminAccesses
    {
        return AdminAccesses::create([
            'key' => $key,
            'descriptions' => $descriptions,
        ]);
    }

    public function accessesList(): array
    {
        return AdminAccesses::select(['id', 'key', 'descriptions'])->get()->toArray();
    }

    public function createRule(string $name, array $accesses): array
    {
        $accesses_id = collect($accesses)
            ->mapWithKeys(fn($item) => [
                $item['key'] => [
                    'edit' => filter_var($item['edit'], FILTER_VALIDATE_BOOLEAN),
                    'view' => filter_var($item['view'], FILTER_VALIDATE_BOOLEAN),
                ],
            ])
            ->toArray();

        $rule = AdminRules::create([
            'name' => $name,
            'accesses_id' => $accesses_id,
            'active' => 1,
        ]);

        return $this->accesses($rule->id);
    }

    public function editAccesses(int $id, string $name, array $accesses): ?array
    {
        $rule = AdminRules::find($id);

        if (!$rule) {
            return null;
        }

        $accesses_id = collect($accesses)
            ->mapWithKeys(fn($item) => [
                $item['key'] => [
                    'edit' => filter_var($item['edit'], FILTER_VALIDATE_BOOLEAN),
                    'view' => filter_var($item['view'], FILTER_VALIDATE_BOOLEAN),
                ],
            ])
            ->toArray();

        $rule->name        = $name;
        $rule->accesses_id = $accesses_id;
        $rule->save();

        foreach ($accesses as $item) {
            $access = AdminAccesses::where('id', $item['id'])
                ->where(fn($q) => $q->whereNull('descriptions')
                    ->orWhere('descriptions', '!=', $item['descriptions']))
                ->first();

            if ($access) {
                $access->descriptions = $item['descriptions'];
                $access->save();
            }
        }

        return $this->accesses($id);
    }
}
