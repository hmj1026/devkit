# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes — security fixes |
| < 1.0   | No — pre-release |

Security fixes are released as `1.x` patch versions. Public contracts remain stable within
the `1.x` line (see [CONTRIBUTING.md](./CONTRIBUTING.md) and
[`docs/v2-roadmap.md`](./docs/v2-roadmap.md)).

## Reporting a vulnerability

Please report suspected vulnerabilities **privately** — do not open a public issue.

- Use GitHub's [private vulnerability reporting](https://github.com/hmj1026/devkit/security/advisories/new)
  ("Report a vulnerability" under the repository's **Security** tab).

Include where possible:

- The affected module / class and version (PHP × Laravel × dependency majors).
- A minimal reproduction or proof of concept.
- The impact you foresee.

You can expect an initial acknowledgement within a few business days. Once a fix is ready it
will be released as a patch version and the advisory published. Please give us a reasonable
window to release before any public disclosure.

## Scope notes

- devkit ships **no concrete SMS provider drivers** and **no Elasticsearch query builder** by
  design; consumer-supplied driver code is out of scope for this policy.
- The file uploader hardens against executable-extension and MIME spoofing, but consumers
  remain responsible for the security configuration of their own Flysystem disks and
  Laravel filesystem settings.
