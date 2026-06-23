<?php

namespace App\Map;

/**
 * Térkép-pontok (POI) tára egy JSON fájlban.
 * Egy POI: id, title, lat, lng, description, link, created.
 */
final class PoiStore
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/pois.json';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $poi) {
            if ((int) $poi['id'] === $id) {
                return $poi;
            }
        }
        return null;
    }

    public function save(array $poi): int
    {
        $pois = $this->all();
        if (empty($poi['id'])) {
            $poi['id'] = $this->nextId($pois);
            $poi['created'] = date('c');
            $pois[] = $poi;
        } else {
            $id = (int) $poi['id'];
            $found = false;
            foreach ($pois as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $poi);
                    $found = true;
                    break;
                }
            }
            unset($existing);
            if (!$found) {
                $pois[] = $poi;
            }
        }
        $this->persist($pois);
        return (int) $poi['id'];
    }

    public function delete(int $id): void
    {
        $pois = array_values(array_filter($this->all(), static fn ($p) => (int) $p['id'] !== $id));
        $this->persist($pois);
    }

    /** @param array<int, array<string, mixed>> $pois */
    private function persist(array $pois): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(
            $this->file,
            json_encode(array_values($pois), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function nextId(array $pois): int
    {
        $max = 0;
        foreach ($pois as $p) {
            $max = max($max, (int) $p['id']);
        }
        return $max + 1;
    }
}
