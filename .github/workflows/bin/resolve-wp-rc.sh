#!/bin/bash
# Resolves the WordPress version to test against.
#
# Prints the resolved version to stdout, or an empty string when no Release
# Candidate is currently in flight (the normal state between release cycles).
#
# Usage: resolve-wp-rc.sh [requested-version]
#   With an argument, echoes it back unchanged (pinned run).
#   With no argument, queries the wordpress.org beta channel for the latest RC.
set -euo pipefail
IFS=$'\n\t'

REQUESTED="${1:-}"

if [ -n "$REQUESTED" ]; then
	echo "$REQUESTED"
	exit 0
fi

# The default version-check response only advertises stable releases. The beta
# channel additionally returns a "development" offer, which is the current RC.
# version=0.0.1 forces the API to treat us as outdated so it returns all offers.
curl -sf "https://api.wordpress.org/core/version-check/1.7/?channel=beta&version=0.0.1" \
	| python3 -c "
import json, sys

offers = json.load(sys.stdin).get('offers', [])
rc = [o['current'] for o in offers if o.get('response') == 'development']
print(rc[0] if rc else '')
"
