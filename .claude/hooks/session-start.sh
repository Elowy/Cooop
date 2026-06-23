#!/bin/bash
# Net-Trade Hungary – SessionStart hook (Claude Code a weben)
# ----------------------------------------------------------------------------
# A projektnek nincs csomag-függősége (nincs composer/npm), ezért a hook
# feladata a futási környezet előkészítése és egy gyors szintaxis-ellenőrzés,
# hogy minden web-session „indulásra kész” állapotban kezdődjön.
# Idempotens és nem interaktív.
set -uo pipefail

# Csak a remote (web) környezetben fusson le.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

echo "▶ Net-Trade projekt előkészítése…"

# 1) Futási könyvtárak (gitignored, a fájl-módú adattárak és a feltöltések ide írnak)
mkdir -p storage public/uploads/references public/uploads/team
chmod -R 0775 storage public/uploads 2>/dev/null || true

# 2) Elérhető eszközök
echo "  PHP:  $(php -v 2>/dev/null | head -1 || echo 'hiányzik')"
echo "  Node: $(node -v 2>/dev/null || echo 'hiányzik')"

problems=0

# 3) PHP szintaxis-ellenőrzés az egész kódbázison (gyors, nem fatális)
while IFS= read -r f; do
  if ! php -l "$f" >/dev/null 2>&1; then
    echo "  ‼️  PHP szintaxishiba: $f"
    php -l "$f" 2>&1 | grep -i 'error' | head -1 | sed 's/^/      /'
    problems=$((problems + 1))
  fi
done < <(find app public config -name '*.php' 2>/dev/null)

# 4) JS szintaxis (a kliensoldali fő szkript)
if command -v node >/dev/null 2>&1 && [ -f public/assets/js/main.js ]; then
  if ! node --check public/assets/js/main.js 2>/dev/null; then
    echo "  ‼️  JS szintaxishiba: public/assets/js/main.js"
    problems=$((problems + 1))
  fi
fi

# 5) CSS kapcsos zárójelek egyensúlya (a stíluslap épségének gyors jelzője)
if [ -f public/assets/css/style.css ]; then
  open_braces=$(grep -o '{' public/assets/css/style.css | wc -l | tr -d ' ')
  close_braces=$(grep -o '}' public/assets/css/style.css | wc -l | tr -d ' ')
  if [ "$open_braces" != "$close_braces" ]; then
    echo "  ‼️  CSS zárójel-eltérés (style.css): { $open_braces vs } $close_braces"
    problems=$((problems + 1))
  fi
fi

if [ "$problems" -eq 0 ]; then
  echo "✓ Minden ellenőrzés rendben – a session indulásra kész."
else
  echo "⚠ $problems probléma található – érdemes javítani."
fi

exit 0
