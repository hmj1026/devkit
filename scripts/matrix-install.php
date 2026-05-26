#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * scripts/matrix-install.php
 *
 * Reproduce a CI matrix cell locally. Reads .github/workflows/tests.yml
 * matrix.include block, finds the row matching the given PHP × Laravel
 * combination, then runs the same composer require / update sequence the
 * CI runs for that cell.
 *
 * Usage:
 *   composer matrix:install -- <php> <laravel> [--lowest] [--dry-run]
 *   composer matrix:list
 *
 * Examples:
 *   composer matrix:install -- 8.2 11
 *   composer matrix:install -- 7.4 6 --lowest
 *
 * No external YAML parser — the matrix rows are written in single-line
 * inline flow style that is regex-parseable.
 *
 * Cleanup after a matrix run:
 *   git checkout -- composer.json composer.lock && composer install
 */

const MATRIX_INSTALL_WORKFLOW = __DIR__ . '/../.github/workflows/tests.yml';

function matrix_fail(string $msg, int $code = 1): void
{
    fwrite(STDERR, "matrix-install: {$msg}\n");
    exit($code);
}

function matrix_usage($stream = null): void
{
    if ($stream === null) {
        $stream = STDOUT;
    }
    fwrite($stream, <<<'TXT'
Usage: composer matrix:install -- <php> <laravel> [--lowest] [--dry-run]
       composer matrix:list

Reproduce a CI matrix cell locally. Reads .github/workflows/tests.yml
matrix.include and runs the same composer sequence CI runs.

Arguments
  <php>       PHP version, e.g. 7.3, 8.2
  <laravel>   Laravel major, accepts "6", "6.*", or "6.x"

Options
  --lowest    composer update --prefer-lowest (CI uses --prefer-dist)
  --dry-run   Print commands without executing
  --list      Print every (php, laravel) pair declared in the workflow
  --help, -h  Show this help

Cleanup
  git checkout -- composer.json composer.lock && composer install


TXT
    );
}

function matrix_parse(string $path): array
{
    if (!is_file($path)) {
        matrix_fail("workflow not found: {$path}");
    }
    $text = @file_get_contents($path);
    if ($text === false) {
        matrix_fail("cannot read: {$path}");
    }

    $rows = [];
    if (!preg_match_all('/^\s*-\s*\{([^}]+)\}\s*$/m', $text, $matches)) {
        matrix_fail('no matrix rows found — expected inline flow style ("- { key: value, ... }")');
    }

    foreach ($matches[1] as $inner) {
        $kv = [];
        if (preg_match_all("/(\w+)\s*:\s*'([^']*)'/", $inner, $kvMatches, PREG_SET_ORDER)) {
            foreach ($kvMatches as $m) {
                $kv[$m[1]] = $m[2];
            }
        }
        if (!empty($kv['php']) && !empty($kv['laravel'])) {
            $rows[] = $kv;
        }
    }

    if (empty($rows)) {
        matrix_fail('failed to parse any matrix rows');
    }
    return $rows;
}

function matrix_normalise_major(string $laravel): string
{
    // Accept "6", "6.*", "6.x", "^6.0" etc — extract leading major.
    if (preg_match('/(\d+)/', $laravel, $m)) {
        return $m[1];
    }
    return $laravel;
}

function matrix_find(array $rows, string $php, string $laravel): ?array
{
    $target = matrix_normalise_major($laravel);
    foreach ($rows as $row) {
        if ($row['php'] !== $php) {
            continue;
        }
        if (matrix_normalise_major($row['laravel']) === $target) {
            return $row;
        }
    }
    return null;
}

function matrix_print_list(array $rows): void
{
    fwrite(STDOUT, "Available cells (PHP × Laravel × Monolog × meta-tags):\n");
    foreach ($rows as $row) {
        fprintf(
            STDOUT,
            "  %-4s × %-5s × %-7s × %s\n",
            $row['php'],
            $row['laravel'],
            $row['monolog'] ?? '?',
            $row['meta_tags'] ?? '?'
        );
    }
}

function matrix_run(array $row, bool $lowest, bool $dryRun): void
{
    $pins = [
        ['monolog/monolog', $row['monolog']],
        ['butschster/meta-tags', $row['meta_tags']],
        ['psr/simple-cache', '^1.0 || ^2.0'],
    ];
    $devPins = [
        ['laravel/framework', $row['laravel']],
        ['orchestra/testbench', $row['testbench']],
        ['phpunit/phpunit', $row['phpunit']],
    ];

    // Mirror CI: disable Composer's security audit so legacy cells
    // (Laravel 6/7/8, Monolog 2.x) don't get blocked by Composer 2.4+
    // audit enforcement on dependency CVEs.
    $auditOff = ['composer', 'config', 'audit.block-insecure', 'false'];

    $require = array_merge(['composer', 'require', '--no-update'], matrix_pin_args($pins));
    $requireDev = array_merge(['composer', 'require', '--no-update', '--dev'], matrix_pin_args($devPins));

    // --prefer-dist and --prefer-lowest are orthogonal axes (download
    // method vs version selection). Always prefer-dist; conditionally
    // add prefer-lowest.
    $update = ['composer', 'update', '--prefer-dist', '--no-interaction', '--no-progress'];
    if ($lowest) {
        $update[] = '--prefer-lowest';
    }

    fprintf(
        STDOUT,
        "matrix-install: PHP %s × Laravel %s × PHPUnit %s × Monolog %s × Testbench %s\n",
        $row['php'],
        $row['laravel'],
        $row['phpunit'],
        $row['monolog'],
        $row['testbench']
    );

    foreach ([$auditOff, $require, $requireDev, $update] as $cmd) {
        $printable = implode(' ', array_map('escapeshellarg', $cmd));
        fwrite(STDOUT, "  > {$printable}\n");
        if ($dryRun) {
            continue;
        }
        $exit = 0;
        passthru($printable, $exit);
        if ($exit !== 0) {
            matrix_fail("command failed (exit {$exit}): {$printable}", $exit);
        }
    }
}

function matrix_pin_args(array $pins): array
{
    $args = [];
    foreach ($pins as $pin) {
        [$pkg, $ver] = $pin;
        $args[] = "{$pkg}:{$ver}";
    }
    return $args;
}

function matrix_main(array $argv): int
{
    array_shift($argv);
    $lowest = false;
    $dryRun = false;
    $list = false;
    $positional = [];
    foreach ($argv as $arg) {
        switch ($arg) {
            case '--lowest':
                $lowest = true;
                break;
            case '--dry-run':
                $dryRun = true;
                break;
            case '--list':
                $list = true;
                break;
            case '--help':
            case '-h':
                matrix_usage();
                return 0;
            default:
                if ($arg !== '' && $arg[0] === '-') {
                    matrix_fail("unknown option: {$arg}", 2);
                }
                $positional[] = $arg;
        }
    }

    $rows = matrix_parse(MATRIX_INSTALL_WORKFLOW);

    if ($list) {
        matrix_print_list($rows);
        return 0;
    }

    if (count($positional) < 2) {
        matrix_usage(STDERR);
        matrix_fail('expected: <php> <laravel> [--lowest] [--dry-run]', 2);
    }

    [$php, $laravel] = $positional;
    $row = matrix_find($rows, $php, $laravel);
    if ($row === null) {
        $available = array_map(static function (array $r): string {
            return "{$r['php']} × {$r['laravel']}";
        }, $rows);
        matrix_fail(
            "no matrix row for PHP {$php} + Laravel {$laravel}\nAvailable cells:\n  " . implode("\n  ", $available),
            3
        );
    }

    matrix_run($row, $lowest, $dryRun);
    return 0;
}

exit(matrix_main($argv));
