# ci-cd-demo

A Laravel application built specifically to learn and demonstrate a real, working CI/CD pipeline using GitHub Actions — including testing, static analysis, security scanning, code style enforcement, Docker builds, and gated deployment.

## What this pipeline does

Every push to `main` (and every pull request) triggers a pipeline with the following stages:

### 1. Testing — matrix across PHP 8.3 and 8.4
The test suite runs twice in parallel, once per PHP version, against a real MySQL 8.0 service container (not SQLite) — catching version- and database-specific bugs that wouldn't otherwise surface until production.

### 2. Security audit
`composer audit` checks every dependency against known CVE advisories before anything else runs.

### 3. Static analysis — Larastan / PHPStan (level 5)
Catches real logic bugs by reading the code, without executing it — undefined methods, type mismatches, unreachable code.

### 4. Custom rule enforcement
A project-specific check (plain `grep`, no extra dependency) blocks any `dd()`, `dump()`, `var_dump()`, or `console.log()` calls from being merged — a common source of accidental debug leftovers reaching production.

### 5. Code style — Laravel Pint
Enforces consistent formatting (spacing, blank lines, quote style) automatically.

### 6. Coding standards — PHP_CodeSniffer (PSR-12)
Enforces naming conventions (camelCase methods, etc.) and other PSR-12 rules that Pint doesn't cover, since renaming code safely isn't a pure formatting operation.

### 7. Code coverage
Tests run with coverage measurement (PCOV) and the build fails if coverage drops below a 25% minimum threshold — a deliberately realistic baseline for an early-stage project, intended to be raised over time as the test suite grows.

### 8. Docker image build
A separate job builds the application into a Docker image and verifies it actually boots (`php-fpm` reaches "ready to handle connections"), not just that it compiles.

### 9. Deployment (simulated)
A final job only runs if every job above succeeds (`needs: test`), and only on a direct push to `main` — never on pull requests. This is the actual mechanism that prevents broken or unreviewed code from reaching production.

## Pipeline architecture

```
push to main / PR opened
        │
        ▼
┌───────────────────┐
│   test (matrix)    │  PHP 8.3  ──┐
│                     │  PHP 8.4  ──┤  (parallel)
└───────────────────┘             │
        │ needs: test (all pass)   │
        ├──────────────┬───────────┘
        ▼              ▼
┌─────────────┐  ┌──────────────┐
│   docker     │  │   deploy      │  (only on push to main)
│ build+verify │  │  (simulated)  │
└─────────────┘  └──────────────┘
```

## Real issues debugged while building this

This project's commit history reflects genuine debugging, not just following a tutorial:

- **GitHub OAuth scope error** — initial push was rejected because the `gh` CLI token lacked the `workflow` scope required to create/update files under `.github/workflows/`.
- **HTTPS credential failure** — git fell back to legacy username/password auth after the scope fix; resolved with `gh auth setup-git` to wire `gh` in as git's credential helper.
- **YAML indentation bug** — the `deploy` job was initially nested one level too deep, silently becoming a property of the `test` job instead of a sibling job, due to a 2-space indentation error.
- **`phpunit.xml` override gotcha** — Laravel's default test config force-overrides the database connection to in-memory SQLite regardless of `.env`, unless real environment variables are set at the GitHub Actions job level (`env:` block) before PHPUnit boots.
- **PHP version/dependency mismatch** — a PHP 8.2 entry in the test matrix failed outright because Laravel 13 requires PHP ^8.3; matrix testing caught a real incompatibility, not a code bug.
- **Conflicting linters** — `phpcbf` (PHP_CodeSniffer's auto-fixer) and Pint disagreed on blank-line spacing after a trait import fix, requiring a manual resolution between two tools with different opinions.
- **Stale Docker build cache** — the Docker image failed to boot because `bootstrap/cache/packages.php`, generated locally with dev dependencies, was copied into the image and referenced a class (`Laravel\Pail\PailServiceProvider`) that didn't exist in the `--no-dev` production install.
- **Untracked files** — `phpcs` failed in CI ("No such file or directory") because `composer.lock`, `phpcs.xml`, `Dockerfile`, and `.dockerignore` had only ever been created locally and were never actually committed to git — a reminder that local success and CI access are not the same fact.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Running checks locally

```bash
./vendor/bin/phpstan analyse --memory-limit=1G   # static analysis
./vendor/bin/pint --test                          # code style (check only)
./vendor/bin/phpcs                                # naming/standards
php artisan test --coverage --min=25              # tests + coverage
composer audit                                    # security
```