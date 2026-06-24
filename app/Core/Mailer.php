<?php

namespace App\Core;

/**
 * Kompakt e-mail küldő, külső függőség nélkül. Két átvitelt támogat:
 *  - 'mail'  : a PHP beépített mail() függvénye (alapértelmezett),
 *  - 'smtp'  : natív SMTP kliens (AUTH LOGIN, STARTTLS/SSL) a megbízhatóbb
 *              kézbesítésért (saját feladó-domain, hitelesített kapcsolat).
 *
 * Minden levél rendes MIME-fejléceket kap (Date, Message-ID, Content-Type),
 * ami szintén javítja a kézbesíthetőséget (kevesebb spam-besorolás).
 */
final class Mailer
{
    /** @param array<string, mixed> $cfg */
    public function __construct(private array $cfg = [])
    {
    }

    /** @param array<string, mixed> $config a teljes alkalmazás-konfiguráció */
    public static function fromConfig(array $config): self
    {
        return new self((array) ($config['mail'] ?? []));
    }

    /**
     * @param array<string, mixed> $opts html(bool), reply_to, from_email,
     *        from_name, list_unsubscribe
     */
    public function send(string $to, string $subject, string $body, array $opts = []): bool
    {
        $fromEmail = (string) ($opts['from_email'] ?? $this->cfg['from_email'] ?? 'no-reply@localhost');
        $fromName = (string) ($opts['from_name'] ?? $this->cfg['from_name'] ?? '');
        $isHtml = !empty($opts['html']);

        $headers = [];
        $headers['From'] = $fromName !== ''
            ? mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromEmail . '>'
            : $fromEmail;
        if (!empty($opts['reply_to'])) {
            $headers['Reply-To'] = (string) $opts['reply_to'];
        }
        if (!empty($opts['list_unsubscribe'])) {
            $headers['List-Unsubscribe'] = '<' . (string) $opts['list_unsubscribe'] . '>';
        }
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
        $headers['Content-Transfer-Encoding'] = '8bit';
        $headers['Date'] = date('r');
        $hostPart = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);
        $headers['Message-ID'] = '<' . bin2hex(random_bytes(12)) . '@' . $hostPart . '>';

        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

        if (($this->cfg['transport'] ?? 'mail') === 'smtp' && !empty($this->cfg['host'])) {
            return $this->sendSmtp($fromEmail, $to, $encodedSubject, $body, $headers);
        }

        // mail(): a From stb. külön fejléc-stringben megy; a tárgyat a mail() kódolja.
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }
        return @mail($to, $encodedSubject, $body, implode("\r\n", $headerLines));
    }

    /**
     * @param array<string, string> $headers
     */
    private function sendSmtp(string $from, string $to, string $encodedSubject, string $body, array $headers): bool
    {
        $secure = (string) ($this->cfg['secure'] ?? '');
        $host = (string) $this->cfg['host'];
        $port = (int) ($this->cfg['port'] ?? ($secure === 'ssl' ? 465 : 587));
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $fp = @stream_socket_client($remote, $errno, $errstr, 15);
        if ($fp === false) {
            error_log("[Mailer] SMTP kapcsolat sikertelen: {$errstr} ({$errno})");
            return false;
        }
        stream_set_timeout($fp, 15);

        $read = static function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                // A többsoros válasz utolsó sorában a 4. karakter szóköz.
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = static function (string $line) use ($fp, $read): string {
            fwrite($fp, $line . "\r\n");
            return $read();
        };
        $ok = static fn (string $r, string $code): bool => str_starts_with($r, $code);

        $fail = static function (string $msg) use ($fp): bool {
            error_log('[Mailer] SMTP hiba: ' . trim($msg));
            @fclose($fp);
            return false;
        };

        if (!$ok($read(), '220')) {
            return $fail('nincs 220 üdvözlés');
        }
        $ehloHost = (string) ($this->cfg['ehlo'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if (!$ok($cmd('EHLO ' . $ehloHost), '250')) {
            return $fail('EHLO elutasítva');
        }
        if ($secure === 'tls') {
            if (!$ok($cmd('STARTTLS'), '220')
                || !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return $fail('STARTTLS sikertelen');
            }
            $cmd('EHLO ' . $ehloHost);
        }
        if (!empty($this->cfg['user'])) {
            if (!$ok($cmd('AUTH LOGIN'), '334')
                || !$ok($cmd(base64_encode((string) $this->cfg['user'])), '334')
                || !$ok($cmd(base64_encode((string) ($this->cfg['pass'] ?? ''))), '235')) {
                return $fail('hitelesítés sikertelen');
            }
        }
        if (!$ok($cmd('MAIL FROM:<' . $from . '>'), '250')) {
            return $fail('MAIL FROM elutasítva');
        }
        if (!$ok($cmd('RCPT TO:<' . $to . '>'), '25')) {
            return $fail('RCPT TO elutasítva');
        }
        if (!$ok($cmd('DATA'), '354')) {
            return $fail('DATA elutasítva');
        }

        $headers['Subject'] = $encodedSubject;
        $headers['To'] = $to;
        $message = '';
        foreach ($headers as $k => $v) {
            $message .= $k . ': ' . $v . "\r\n";
        }
        $message .= "\r\n" . self::dotStuff($body) . "\r\n.";
        if (!$ok($cmd($message), '250')) {
            return $fail('üzenet elutasítva');
        }
        $cmd('QUIT');
        @fclose($fp);
        return true;
    }

    /** CRLF normalizálás + pont-stuffing (a "." kezdetű sorok elé még egy pont). */
    public static function dotStuff(string $body): string
    {
        $body = preg_replace('/\r\n|\r|\n/', "\r\n", $body) ?? $body;
        return preg_replace('/^\./m', '..', $body) ?? $body;
    }
}
