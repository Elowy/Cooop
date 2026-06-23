#!/bin/bash
# Stop hook – a válasz lezárásakor fut. Két dolgot tesz:
#  1) Leellenőrzi a módosított (követett) kód-fájlok szintaxisát; hiba esetén
#     exit 2-vel visszaadja a vezérlést az ügynöknek, hogy javítsa ki.
#  2) Ha nincs szintaxishiba, de van nem-commitolt változás, halkan emlékeztet.
# Ciklusvédelem: ha már egy stop-hook által kikényszerített folytatásban
# vagyunk, nem blokkol újra.
set -uo pipefail

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

payload=$(cat 2>/dev/null || echo '{}')
active=$(printf '%s' "$payload" | php -r '$j=json_decode(file_get_contents("php://stdin"),true); echo !empty($j["stop_hook_active"]) ? "1":"0";' 2>/dev/null || echo "0")
[ "$active" = "1" ] && exit 0

# Módosított, követett kód-fájlok (staged + unstaged) a HEAD-hez képest.
mapfile -t files < <(git diff --name-only --diff-filter=ACM HEAD 2>/dev/null | grep -E '\.(php|js|css)$' || true)

errors=""
for f in "${files[@]}"; do
  [ -f "$f" ] || continue
  case "$f" in
    *.php) php -l "$f" >/dev/null 2>&1 || errors="${errors}\n  - PHP szintaxishiba: $f" ;;
    *.js)  node --check "$f" >/dev/null 2>&1 || errors="${errors}\n  - JS szintaxishiba: $f" ;;
    *.css)
      o=$(grep -o '{' "$f" | wc -l | tr -d ' ')
      c=$(grep -o '}' "$f" | wc -l | tr -d ' ')
      [ "$o" = "$c" ] || errors="${errors}\n  - CSS zárójel-eltérés: $f" ;;
  esac
done

if [ -n "$errors" ]; then
  printf 'Szintaxishiba maradt a módosított fájlokban – javítsd ki, mielőtt lezárod:%b\n' "$errors" >&2
  exit 2
fi

# Halk emlékeztető nem-commitolt változásról (nem blokkol).
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
  echo "Emlékeztető: van nem-commitolt változás a munkamappában."
fi

exit 0
