#!/bin/bash
set -euo pipefail

plugin_dir="${1:-/usr/local/emhttp/plugins/scriptlogs}"

for installed_file in "${plugin_dir}"/*; do
  [[ -e "${installed_file}" ]] || continue

  case "${installed_file##*/}" in
    Scriptlogs.page|ScriptlogsSettings.page)
      rm -f -- "${installed_file}"
      ;;
  esac
done
