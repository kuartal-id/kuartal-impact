#!/usr/bin/env python3
"""
Kuartal multi-agent guardrails (shared by every Kuartal repo).

Run locally:  python3 .ai/check.py
CI runs it on every push and pull request (.github/workflows/ai-guardrails.yml).

Fails when:
  1. A secret / private key / .env file is committed.
  2. A protected path (.ai/protected.txt) changed without the "owner-approved" PR label.
  3. An existing database migration was edited or deleted (add a NEW migration instead) —
     production databases have already run the old ones.
  4. A changed PHP file has a syntax error (when `php` is available).

Env: BASE_REF (git ref/sha to diff against), PR_LABELS (comma-separated).
"""
import fnmatch
import os
import re
import shutil
import subprocess
import sys

errors, warnings = [], []


def sh(*args):
    return subprocess.run(args, capture_output=True, text=True, check=True).stdout.strip()


def read(path):
    try:
        with open(path, encoding="utf-8", errors="ignore") as f:
            return f.read()
    except OSError:
        return ""


# ── 1. Secrets ─────────────────────────────────────────────────────
try:
    tracked = [f for f in sh("git", "ls-files").split("\n") if f]
except Exception:
    tracked = []
    warnings.append("Not a git checkout — skipped secret scan.")

BAD_NAMES = [
    r"(^|/)\.env$", r"(^|/)\.env\.(?!example$)[^/]+$", r"\.pem$", r"\.p12$", r"\.pfx$",
    r"(^|/)id_(rsa|ed25519)$", r"oauth-(private|public)\.key$", r"\.keystore$", r"(^|/)auth\.json$",
]
BAD_CONTENT = [
    (r"-----BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----", "private key"),
    (r"AKIA[0-9A-Z]{16}", "AWS access key"),
    (r"\bsk-(ant-|proj-)?[A-Za-z0-9_-]{32,}", "API secret key"),
    (r"\bgh[pousr]_[A-Za-z0-9]{36,}", "GitHub token"),
    (r"\bAIza[0-9A-Za-z_-]{35}\b", "Google API key"),
    (r"xox[baprs]-[A-Za-z0-9-]{10,}", "Slack token"),
    (r"^APP_KEY=base64:[A-Za-z0-9+/=]{40,}", "Laravel APP_KEY"),
]
SKIP = re.compile(r"\.(png|jpe?g|webp|gif|ico|icns|svg|mp4|webm|mp3|woff2?|ttf|otf|pdf|zip|lock)$|package-lock\.json$|(^|/)vendor/|(^|/)node_modules/|\.ai/check\.py$")
for f in tracked:
    if any(re.search(p, f) for p in BAD_NAMES):
        errors.append(f"Secret-looking file is committed: {f} (remove it and rotate the secret)")
    if SKIP.search(f):
        continue
    text = read(f)
    if len(text) > 2_000_000:
        continue
    for pat, what in BAD_CONTENT:
        if re.search(pat, text, re.M):
            errors.append(f"Possible {what} in {f}")

# ── Changed files vs base ──────────────────────────────────────────
base_env = os.environ.get("BASE_REF", "")
candidates = [base_env if base_env and not re.fullmatch(r"0+", base_env) else "", "origin/main", "main"]
try:
    current = sh("git", "rev-parse", "--abbrev-ref", "HEAD")
except Exception:
    current = ""
changes = None  # list of (status, path)
for cand in [c for c in candidates if c]:
    if cand == "main" and current == "main":
        continue
    try:
        sh("git", "rev-parse", "--verify", "--quiet", f"{cand}^{{commit}}")
        out = sh("git", "diff", "--name-status", f"{cand}...HEAD")
        changes = []
        for line in out.split("\n"):
            if not line:
                continue
            parts = line.split("\t")
            status = parts[0][0]
            for p in parts[1:]:
                changes.append((status, p))
        break
    except Exception:
        continue
if changes is None:
    warnings.append("No base branch to compare with — skipped change checks (run `git fetch origin` to enable).")
    changes = []

labels = [l.strip() for l in os.environ.get("PR_LABELS", "").split(",")]
approved = "owner-approved" in labels

# ── 2. Protected paths ─────────────────────────────────────────────
globs = [l.strip() for l in read(".ai/protected.txt").splitlines() if l.strip() and not l.strip().startswith("#")]


def is_protected(path):
    for g in globs:
        if fnmatch.fnmatch(path, g) or (g.endswith("/**") and path.startswith(g[:-3] + "/")):
            return True
    return False


hits = sorted({p for _, p in changes if is_protected(p)})
if hits and not approved:
    errors.append("Protected files changed without owner approval:\n    - " + "\n    - ".join(hits)
                  + "\n  Ask Diemas to review, then add the \"owner-approved\" label to the PR.")
elif hits:
    warnings.append("Protected files changed (owner-approved): " + ", ".join(hits))

# ── 3. Existing migrations are immutable ───────────────────────────
mig = sorted({p for s, p in changes if p.startswith("database/migrations/") and s in ("M", "D", "R")})
if mig and not approved:
    errors.append("Existing migrations were edited/deleted/renamed (production already ran them — add a NEW migration instead):\n    - "
                  + "\n    - ".join(mig))

# ── 4. PHP syntax ──────────────────────────────────────────────────
php = shutil.which("php")
changed_php = [p for s, p in changes if s != "D" and p.endswith(".php") and os.path.exists(p)]
if php and changed_php:
    for p in changed_php:
        r = subprocess.run([php, "-l", p], capture_output=True, text=True)
        if r.returncode != 0:
            errors.append(f"PHP syntax error in {p}: {(r.stdout + r.stderr).strip().splitlines()[0]}")
elif changed_php:
    warnings.append("php not installed here — skipped PHP syntax check (CI runs it).")

if changes and not any(p == "docs/HANDOFF.md" for _, p in changes):
    warnings.append("docs/HANDOFF.md was not updated — please log what you changed for the next agent.")

# ── Report ─────────────────────────────────────────────────────────
for w in warnings:
    print(f"⚠  {w}")
if errors:
    print(f"\n✖ Guardrails failed ({len(errors)}):\n", file=sys.stderr)
    for e in errors:
        print(f"  • {e}\n", file=sys.stderr)
    print("Read AGENTS.md before changing protected areas.", file=sys.stderr)
    sys.exit(1)
print(f"✔ Guardrails passed ({len(tracked)} files scanned, {len(globs)} protected globs, {len(changes)} changed files checked).")
