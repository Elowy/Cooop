#!/bin/bash
# PostToolUse hook – azonnali szintaxis-ellenőrzés a frissen szerkesztett fájlra.
# Edit/Write/MultiEdit után fut. A szerkesztett fájl elérési útját a tool
# bemenetéből (stdin JSON) olvassa ki. Hiba esetén exit 2 + stderr, így a
# hibajelzés visszakerül az ügynökhöz, hogy azonnal javítsa.
set -uo pipefail

cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0

# A szerkesztett fájl elérési útja a JSON tool_input-ból (PHP-vel, ami biztosan van).
file=$(php -r '$j=json_decode(file_get_contents("php://stdin"),true); echo $j["tool_input"]["file_path"] ?? "";' 2>/dev/null || echo "")

[ -z "$file" ] && exit 0
[ -f "$file" ] || exit 0

case "$file" in
  *.php)
    if ! out=$(php -l "$file" 2>&1); then
      echo "PHP szintaxishiba a(z) $file fájlban:" >&2
      echo "$out" | grep -i error | head -2 >&2
      exit 2
    fi
    ;;
  *.js)
    if ! out=$(node --check "$file" 2>&1); then
      echo "JS szintaxishiba a(z) $file fájlban:" >&2
      echo "$out" | head -3 >&2
      exit 2
    fi
    ;;
  *.css)
    o=$(grep -o '{' "$file" | wc -l | tr -d ' ')
    c=$(grep -o '}' "$file" | wc -l | tr -d ' ')
    if [ "$o" != "$c" ]; then
      echo "CSS zárójel-eltérés a(z) $file fájlban: { $o vs } $c" >&2
      exit 2
    fi
    ;;
esac

exit 0
