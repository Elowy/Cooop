<?php

namespace App\Reference;

/**
 * Referenciák tára (egy JSON fájl). Első használatkor a korábbi partnerlistából
 * (config/partners.php) tölti fel magát, hogy a meglévő partnerek ne vesszenek el.
 *
 * Egy referencia: id, name, logo (fájlnév a public/uploads/references alatt),
 *                 short, long, created.
 */
final class ReferenceStore
{
    private string $file;
    private string $seedFile;

    public function __construct(?string $file = null, ?string $seedFile = null)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/references.json';
        $this->seedFile = $seedFile ?? dirname(__DIR__, 2) . '/config/partners.php';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if (!is_file($this->file)) {
            $seed = $this->seed();
            $this->persist($seed);
            return $seed;
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $ref) {
            if ((int) $ref['id'] === $id) {
                return $ref;
            }
        }
        return null;
    }

    /** Létrehoz vagy frissít; visszaadja az azonosítót. */
    public function save(array $ref): int
    {
        $refs = $this->all();
        if (empty($ref['id'])) {
            $ref['id'] = $this->nextId($refs);
            $ref['created'] = date('c');
            $refs[] = $ref;
        } else {
            $id = (int) $ref['id'];
            $found = false;
            foreach ($refs as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $ref);
                    $found = true;
                    break;
                }
            }
            unset($existing);
            if (!$found) {
                $refs[] = $ref;
            }
        }
        $this->persist($refs);
        return (int) $ref['id'];
    }

    public function delete(int $id): void
    {
        $refs = array_values(array_filter($this->all(), static fn ($r) => (int) $r['id'] !== $id));
        $this->persist($refs);
    }

    /** @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $rows = is_file($this->seedFile) ? (array) require $this->seedFile : [];
        $out = [];
        $i = 0;
        foreach ($rows as $p) {
            $out[] = [
                'id' => ++$i,
                'name' => (string) ($p['name'] ?? ''),
                'logo' => '',
                'short' => (string) ($p['note'] ?? ''),
                'long' => '',
                'created' => date('c'),
            ];
        }
        return $out;
    }

    /** @param array<int, array<string, mixed>> $refs */
    private function persist(array $refs): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(
            $this->file,
            json_encode(array_values($refs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function nextId(array $refs): int
    {
        $max = 0;
        foreach ($refs as $r) {
            $max = max($max, (int) $r['id']);
        }
        return $max + 1;
    }
}
