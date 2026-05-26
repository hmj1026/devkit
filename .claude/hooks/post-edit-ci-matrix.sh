#!/usr/bin/env bash
# post-edit-ci-matrix.sh — devkit project hook.
#
# Goal: route composer.json and .github/workflows/tests.yml edits to the
# ci-matrix-completeness-reviewer agent via .pending-ci-matrix-review
# sentinel. Companion to dhpk's polyfill-reviewer (different sentinel)
# and dhpk's version-matrix-impact-reviewer (no sentinel — manual).
#
# Design:
#   - Self-contained: does NOT depend on dhpk being loaded. Inline payload
#     extraction (jq → python3 → grep fallback).
#   - async-friendly: always exit 0; never blocks the edit pipeline.
#   - Idempotent: same file edited twice in one session = one line.
#   - Cheap: case-pattern path match, no file body read.
#
# Trigger criteria (ALL must hold):
#   1. tool_input.file_path resolves to one of:
#        - composer.json (repo root)
#        - .github/workflows/tests.yml
#        - .github/workflows/*.yml under tests/ci/build keywords
#   2. file physically exists post-edit
#
# Sentinel format (matches dhpk's sixth-color sentinel):
#   <unix-ts> <tool-name> <relative-path>

set -o pipefail

ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"

PAYLOAD="$(cat 2>/dev/null || true)"
[ -z "$PAYLOAD" ] && exit 0

# Extract a top-level or tool_input field from the JSON payload.
# Whitelists field names to a fixed set so no caller can inject regex /
# Python code through the $field argument. Try jq → python3 → fixed grep.
extract_field() {
    local field="$1"
    local payload="$2"

    case "$field" in
        file_path|filePath|tool_name) ;;
        *) return 0 ;;
    esac

    local v=""
    if command -v jq >/dev/null 2>&1; then
        v="$(printf '%s' "$payload" | jq -r --arg f "$field" '.tool_input[$f] // .[$f] // empty' 2>/dev/null)"
    fi
    if [ -z "$v" ] && command -v python3 >/dev/null 2>&1; then
        v="$(printf '%s' "$payload" | python3 - "$field" <<'PYEOF' 2>/dev/null
import json, sys
field = sys.argv[1]
try:
    d = json.load(sys.stdin)
    print(d.get('tool_input', {}).get(field) or d.get(field) or '')
except Exception:
    pass
PYEOF
)"
    fi
    if [ -z "$v" ]; then
        # Grep fallback — fixed-string key match, no regex interpolation.
        local key="\"$field\""
        v="$(printf '%s' "$payload" \
            | grep -oF "$key" -A0 >/dev/null 2>&1 && printf '%s' "$payload" \
            | grep -oE "\"$(printf '%s' "$field" | sed -E 's/[][\\/.^$*+?(){}|]/\\&/g')\"[[:space:]]*:[[:space:]]*\"[^\"]+\"" \
            | head -1 \
            | sed -E 's/.*:[[:space:]]*"([^"]+)".*/\1/')"
    fi
    printf '%s' "$v"
}

FILE_PATH="$(extract_field file_path "$PAYLOAD")"
[ -z "$FILE_PATH" ] && FILE_PATH="$(extract_field filePath "$PAYLOAD")"
[ -z "$FILE_PATH" ] && exit 0

[ -f "$FILE_PATH" ] || exit 0

# Normalise to repo-relative.
REL="${FILE_PATH#$ROOT/}"

# Path filter: only composer.json and workflow files under .github/workflows/.
case "$REL" in
    composer.json) ;;
    .github/workflows/*.yml|.github/workflows/*.yaml) ;;
    *) exit 0 ;;
esac

# Sessions directory — degrade silently if absent.
SESS_DIR="$ROOT/.claude/artifacts/sessions"
[ -d "$SESS_DIR" ] || mkdir -p "$SESS_DIR" 2>/dev/null || exit 0

SENTINEL="$SESS_DIR/.pending-ci-matrix-review"

# Tool name for the sentinel record.
TOOL_NAME=""
if command -v jq >/dev/null 2>&1; then
    TOOL_NAME="$(printf '%s' "$PAYLOAD" | jq -r '.tool_name // empty' 2>/dev/null)"
fi
[ -z "$TOOL_NAME" ] && TOOL_NAME="Edit"

TS="$(date +%s)"

# Idempotent: skip if this exact REL is already a line-suffix in the
# sentinel. Anchor to end-of-line to avoid suffix-collision false
# positives (e.g. tests.yml vs extended-tests.yml).
if [ -f "$SENTINEL" ] && grep -qE "[[:space:]]${REL//./\\.}\$" "$SENTINEL" 2>/dev/null; then
    exit 0
fi

printf '%s %s %s\n' "$TS" "$TOOL_NAME" "$REL" >> "$SENTINEL" 2>/dev/null || true

exit 0
