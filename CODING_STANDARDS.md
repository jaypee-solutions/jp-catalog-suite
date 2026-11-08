# Coding standards

- Hook callbacks use the hook's actual parameter types, verified against WooCommerce/WordPress source, because typed non-nullable parameters crashed when WooCommerce passed false or null.
- `pre_option_*` and `option_*` filters return their incoming `$pre` or value when inactive, never null, because null changes WordPress option resolution.
- `is_admin()` does not mean an action is not an email: admin-triggered emails exist, so use `doing_action()`.
- Tests assert consumer-visible outcomes and never pin hook wiring or defaults; call through `get_option`, REST dispatch, or `template_redirect`, not the callback.
- Persisted option and metadata names match spec #1 and ADRs because renames after release are expensive.
- A feature gated by a module toggle never depends on that module for security or privacy, including protected files, access blocks, and privacy tools.
- Spec-fixed hook signatures are part of the public contract.
- E2E specs restore everything they change and assert on objects they created.
