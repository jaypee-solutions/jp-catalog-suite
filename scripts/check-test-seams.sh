#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

answers_file=.copier-answers.yml
if [ ! -f "$answers_file" ]; then
    echo "Missing $answers_file; cannot determine the configured PHP namespace." >&2
    exit 1
fi

php_namespace=$(awk -F: '
    $1 == "php_namespace" {
        value = substr($0, index($0, ":") + 1)
        sub(/^[[:space:]]+/, "", value)
        sub(/[[:space:]]+$/, "", value)
        if (value ~ /^".*"$/ || value ~ /^\047.*\047$/) {
            value = substr(value, 2, length(value) - 2)
        }
        print value
        exit
    }
' "$answers_file")
if [ -z "$php_namespace" ]; then
    echo "No php_namespace value found in $answers_file." >&2
    exit 1
fi

if [ ! -d tests/integration ]; then
    echo "Missing tests/integration; cannot check integration-test seams." >&2
    exit 1
fi

# Names of the classes declared in includes/, for the instantiation check below.
plugin_classes=
if [ -d includes ]; then
    plugin_classes=$(find includes -type f -name '*.php' -exec sed -n -E \
        's/^[[:space:]]*(abstract[[:space:]]+|final[[:space:]]+)*class[[:space:]]+([A-Za-z_][A-Za-z0-9_]*).*/\2/p' {} + \
        | sort -u | tr '\n' ' ')
fi

# Integration tests exercise the plugin through WordPress, not direct calls to
# namespaced implementation methods. Constants such as ::OPTION and ::class stay valid.
# Imported names (`use Ns\Class;` then `Class::method(`) are not detected.
#
# They also do not build their own instances: hooks bind to the instance that
# Plugin::init() registered, so `new Mailer()` in a test exercises an object WordPress
# never calls. Both `new \Ns\Class` and `new Class` for a class declared in includes/ are
# flagged; a line that needs the real thing anyway says why with `// test-seam: <reason>`.
PHP_NAMESPACE=$php_namespace PLUGIN_CLASSES=$plugin_classes find tests/integration -type f -name '*.php' -exec awk '
    function code_of(text,    cut) {
        cut = match(text, /[[:space:]]\/\//)
        return cut > 0 ? substr(text, 1, cut - 1) : text
    }
    {
        expected = ENVIRON["PHP_NAMESPACE"] "\\"
        line = $0
        while ((position = index(line, expected)) > 0) {
            before = position > 1 ? substr(line, position - 1, 1) : ""
            remainder = substr(line, position + length(expected))
            if (before !~ /[A-Za-z0-9_]/ && remainder ~ /^[A-Za-z_][A-Za-z0-9_]*::[A-Za-z_][A-Za-z0-9_]*[[:space:]]*[(]/) {
                printf "%s:%d: call the plugin through WordPress (hooks, REST, core functions), not a static method.\n", FILENAME, FNR > "/dev/stderr"
                failed = 1
                break
            }
            line = remainder
        }

        if ($0 ~ /^[[:space:]]*(\/\/|\/\*|\*|#)/) {
            next
        }
        reason_given = ($0 ~ /\/\/[[:space:]]*test-seam:[[:space:]]*[^[:space:]]/)
        reason_missing = ($0 ~ /\/\/[[:space:]]*test-seam:[[:space:]]*$/)
        rest = code_of($0)
        while (match(rest, /(^|[^A-Za-z0-9_$>:])new[[:space:]]+/)) {
            rest = substr(rest, RSTART + RLENGTH)
            target = rest
            sub(/^\\/, "", target)
            qualified = (index(target, expected) == 1)
            if (qualified) {
                target = substr(target, length(expected) + 1)
            }
            if (qualified) {
                found = match(target, /^[A-Za-z_][A-Za-z0-9_\\]*/)
            } else {
                found = match(target, /^[A-Za-z_][A-Za-z0-9_]*/)
            }
            if (!found) {
                continue
            }
            name = substr(target, 1, RLENGTH)
            namespaced_elsewhere = (substr(target, RLENGTH + 1, 1) == "\\")
            if (!qualified && (namespaced_elsewhere || index(" " ENVIRON["PLUGIN_CLASSES"], " " name " ") == 0)) {
                continue
            }
            if (!reason_given) {
                printf "%s:%d: do not instantiate the plugin class %s in an integration test; reach it through WordPress (the instance Plugin::init() registers).%s\n", FILENAME, FNR, name, (reason_missing ? " A reason is required after // test-seam:." : " If a real instance is unavoidable, add // test-seam: <reason> to the line.") > "/dev/stderr"
                failed = 1
            }
        }
    }
    END { exit failed }
' {} +

# E2E specs run WP-CLI through tests/e2e/support/wp-cli.ts and log in through
# tests/e2e/support/auth.ts; their own child-process helpers drift apart (docs/agents/testing.md).
if [ -d tests/e2e ] && grep -Hn 'execFileSync' tests/e2e/*.spec.ts >&2; then
    echo "Import wpCli from tests/e2e/support/wp-cli.ts instead of spawning wp-env in a spec." >&2
    exit 1
fi
