#!/bin/sh
# MCP Inspector v2 wrapper — provides an OS keyring inside the container.
#
# v2 moved per-server secrets out of mcp.json into an OS keychain via
# @napi-rs/keyring (libsecret / Secret Service over D-Bus). A plain container has
# neither, so every /api/servers call fails with:
#
#   Failed to read server list: Couldn't access platform storage: PermissionDenied
#
# which leaves the web UI unable to list or add any server. Running the Inspector
# inside a private D-Bus session with an unlocked, in-memory gnome-keyring fixes
# that. The keyring uses an empty passphrase and dies with the container — it is
# a throwaway store for a local dev tool, never a credential vault.
set -e

exec dbus-run-session -- sh -c '
  echo "" | gnome-keyring-daemon --unlock --components=secrets >/dev/null 2>&1
  exec mcp-inspector "$@"
' sh "$@"
