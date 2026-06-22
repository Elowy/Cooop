<?php

namespace App\Catalog;

/**
 * Kategóriafa-szolgáltatás: a hierarchikus kategóriastruktúrát teszi
 * kereshetővé (levél/ág/útvonal lekérdezések a szűréshez és a morzsamenühöz).
 */
final class Categories
{
    /** @var array<int, array<string, mixed>> Eredeti fa. */
    private array $tree;

    /** @var array<string, array{key:string,name:string,parent:?string,depth:int,children:string[]}> */
    private array $flat = [];

    /** @param array<int, array<string, mixed>>|null $tree */
    public function __construct(?array $tree = null)
    {
        $this->tree = $tree ?? (array) require dirname(__DIR__, 2) . '/config/categories.php';
        $this->index($this->tree, null, 0);
    }

    /** @param array<int, array<string, mixed>> $nodes */
    private function index(array $nodes, ?string $parent, int $depth): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            $this->flat[$node['key']] = [
                'key' => $node['key'],
                'name' => $node['name'],
                'parent' => $parent,
                'depth' => $depth,
                'children' => array_map(static fn ($c) => $c['key'], $children),
            ];
            if ($children) {
                $this->index($children, $node['key'], $depth + 1);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function tree(): array
    {
        return $this->tree;
    }

    /** @return array<int, array{key:string,name:string,parent:?string,depth:int,children:string[]}> */
    public function topLevel(): array
    {
        return array_values(array_filter($this->flat, static fn ($n) => $n['parent'] === null));
    }

    public function find(string $key): ?array
    {
        return $this->flat[$key] ?? null;
    }

    public function name(string $key): string
    {
        return $this->flat[$key]['name'] ?? $key;
    }

    /** Az adott kategória és minden leszármazottja (szűréshez). @return string[] */
    public function branch(string $key): array
    {
        if (!isset($this->flat[$key])) {
            return [];
        }
        $keys = [$key];
        foreach ($this->flat[$key]['children'] as $child) {
            $keys = array_merge($keys, $this->branch($child));
        }
        return $keys;
    }

    /** Útvonal a gyökértől a kategóriáig (morzsamenü). @return string[] */
    public function path(string $key): array
    {
        $path = [];
        $current = $key;
        while ($current !== null && isset($this->flat[$current])) {
            array_unshift($path, $current);
            $current = $this->flat[$current]['parent'];
        }
        return $path;
    }
}
