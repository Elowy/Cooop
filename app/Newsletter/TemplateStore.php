<?php

namespace App\Newsletter;

use PDO;

/**
 * Hírlevél-sablonok tára. Telepítés után adatbázis (newsletter_templates tábla),
 * előtte JSON fájl.
 *
 * Sablon: id, name, subject, body, created.
 */
final class TemplateStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/newsletter_templates.json';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, name, subject, body FROM newsletter_templates ORDER BY id');
            foreach ($rows as $r) {
                $out[] = ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'subject' => (string) $r['subject'], 'body' => (string) $r['body']];
            }
            return $out;
        }
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function find(int $id): ?array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, name, subject, body FROM newsletter_templates WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            return $r ? ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'subject' => (string) $r['subject'], 'body' => (string) $r['body']] : null;
        }
        foreach ($this->all() as $t) {
            if ((int) $t['id'] === $id) {
                return $t;
            }
        }
        return null;
    }

    public function save(array $tpl): int
    {
        if ($this->pdo) {
            if (!empty($tpl['id'])) {
                $this->pdo->prepare('UPDATE newsletter_templates SET name=?, subject=?, body=? WHERE id=?')
                    ->execute([$tpl['name'] ?? '', $tpl['subject'] ?? '', $tpl['body'] ?? '', (int) $tpl['id']]);
                return (int) $tpl['id'];
            }
            $this->pdo->prepare('INSERT INTO newsletter_templates (name, subject, body, created_at) VALUES (?, ?, ?, ?)')
                ->execute([$tpl['name'] ?? '', $tpl['subject'] ?? '', $tpl['body'] ?? '', date('c')]);
            return (int) $this->pdo->lastInsertId();
        }
        return $this->fileSave($tpl);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM newsletter_templates WHERE id = ?')->execute([$id]);
            return;
        }
        $rows = array_values(array_filter($this->all(), static fn ($t) => (int) $t['id'] !== $id));
        $this->persist($rows);
    }

    private function fileSave(array $tpl): int
    {
        $rows = $this->all();
        if (empty($tpl['id'])) {
            $tpl['id'] = $this->nextId($rows);
            $tpl['created'] = date('c');
            $rows[] = $tpl;
        } else {
            $id = (int) $tpl['id'];
            foreach ($rows as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $tpl);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($rows);
        return (int) $tpl['id'];
    }

    private function persist(array $rows): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $rows): int
    {
        $max = 0;
        foreach ($rows as $r) {
            $max = max($max, (int) $r['id']);
        }
        return $max + 1;
    }
}
