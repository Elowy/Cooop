<?php

namespace App\Core;

/**
 * Egyszerű, fájl-alapú belépés-korlátozó (brute-force ellen). Kulcsonként
 * (jellemzően IP) tárolja a sikertelen próbálkozások időbélyegeit; ha az
 * ablakon belül elér egy küszöböt, a kulcs ideiglenesen zárolt.
 *
 * Nem igényel adatbázist; flock véd a párhuzamos íráskor.
 */
final class LoginThrottle
{
    private string $file;
    private int $max;
    private int $window;

    public function __construct(?string $file = null, int $max = 6, int $window = 900)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/login_attempts.json';
        $this->max = max(1, $max);
        $this->window = max(60, $window);
    }

    /** Igaz, ha a kulcs jelenleg zárolt (túl sok friss sikertelen próbálkozás). */
    public function blocked(string $key): bool
    {
        return count($this->recent($this->load(), $key)) >= $this->max;
    }

    /** Másodperc a zárolás feloldásáig (0, ha nincs zárolva). */
    public function retryAfter(string $key): int
    {
        $times = $this->recent($this->load(), $key);
        if (count($times) < $this->max) {
            return 0;
        }
        sort($times);
        return max(0, ($times[0] + $this->window) - time());
    }

    /** Egy sikertelen próbálkozás rögzítése. */
    public function registerFailure(string $key): void
    {
        $this->mutate(static function (array $all) use ($key): array {
            $all[$key][] = time();
            return $all;
        });
    }

    /** Sikeres belépéskor a kulcs törlése. */
    public function clear(string $key): void
    {
        $this->mutate(static function (array $all) use ($key): array {
            unset($all[$key]);
            return $all;
        });
    }

    /**
     * @param array<string, int[]> $all
     * @return int[]
     */
    private function recent(array $all, string $key): array
    {
        $cutoff = time() - $this->window;
        return array_values(array_filter(
            $all[$key] ?? [],
            static fn ($t) => is_int($t) && $t > $cutoff
        ));
    }

    /** @return array<string, int[]> */
    private function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    /** @param callable(array<string, int[]>): array<string, int[]> $fn */
    private function mutate(callable $fn): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $fp = @fopen($this->file, 'c+');
        if ($fp === false) {
            return;
        }
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $all = $raw !== false && $raw !== '' ? json_decode($raw, true) : [];
        $all = is_array($all) ? $all : [];

        $all = $fn($all);

        // Üres/elavult kulcsok kipucolása, hogy a fájl ne hízzon.
        $cutoff = time() - $this->window;
        foreach ($all as $k => $times) {
            $kept = array_values(array_filter((array) $times, static fn ($t) => is_int($t) && $t > $cutoff));
            if ($kept) {
                $all[$k] = $kept;
            } else {
                unset($all[$k]);
            }
        }

        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, (string) json_encode($all, JSON_UNESCAPED_SLASHES));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
