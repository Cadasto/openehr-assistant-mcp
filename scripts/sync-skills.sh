#!/usr/bin/env bash
# Sync canonical skills/ into .cursor/skills/ and .claude/skills/ so Cursor and Claude Code discover them.
# Run from repository root.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SKILLS_SRC="$ROOT_DIR/skills"

if [[ ! -d "$SKILLS_SRC" ]]; then
  echo "Error: skills directory not found at $SKILLS_SRC" >&2
  exit 1
fi

for TARGET in ".cursor/skills" ".claude/skills"; do
  DEST="$ROOT_DIR/$TARGET"
  rm -rf "$DEST"
  mkdir -p "$DEST"
  for SKILL_DIR in "$SKILLS_SRC"/*/; do
    [[ -d "$SKILL_DIR" ]] || continue
    name="$(basename "$SKILL_DIR")"
    cp -r "$SKILL_DIR" "$DEST/$name"
  done
  echo "Synced skills -> $TARGET"
done
