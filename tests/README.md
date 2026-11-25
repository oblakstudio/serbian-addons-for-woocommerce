# Integration tests

PHPUnit integration suite running real WordPress + WooCommerce + this plugin. The database is a
SQLite file provided by the [SQLite Database Integration](https://wordpress.org/plugins/sqlite-database-integration/)
drop-in, so no MySQL server is needed.

```bash
composer install
composer test            # installs WP/WC/SQLite on first run, then runs the suite
composer test:coverage   # needs pcov or xdebug; fails below 100% on the payment gateway code
```

No coverage extension? `phpdbg` works too:

```bash
phpdbg -qrr vendor/bin/phpunit --coverage-clover=.cache/coverage/clover.xml
php bin/check-coverage.php   # per file coverage + uncovered lines for the gateway scope
```

The ImageMagick QR generator is only covered where the `imagick` extension is installed (CI).

## Environment

| Variable           | Default                    | Purpose                                              |
| ------------------ | -------------------------- | ---------------------------------------------------- |
| `WCSRB_TESTS_DIR`  | `$TMPDIR/wcsrb-wp-tests`   | Where WordPress/WooCommerce are installed            |
| `WP_VERSION`       | `latest`                   | WordPress version for `bin/install-wp-tests.sh`      |
| `WC_VERSION`       | `latest-stable`            | WooCommerce version                                  |
| `SQLITE_VERSION`   | `latest-stable`            | SQLite Database Integration version                  |
| `WCSRB_TESTS_HPOS` | `1`                        | `0` runs against legacy (post) order storage         |

WordPress lives outside the checkout on purpose (tools that scan the plugin directory never see
it). Every checkout gets its own SQLite file under `$WCSRB_TESTS_DIR/runs/`, so worktrees can run
in parallel. Use `bin/install-wp-tests.sh --force` to refresh the install.

## Writing tests

Extend `Oblak\WCSRB\Tests\Support\TestCase`. Each test runs in a rolled-back DB transaction; the
base class re-reads gateway settings and removes generated QR codes after each test.

- `create_order( [...] )` saves an order like a checkout would (slip data + IPS QR code generated).
- `update_gateway_settings( [...] )` overrides payment slip gateway settings for one test.
- `Fixtures` holds valid Serbian identifiers (bank account, MB, PIB) and baseline store options.
