# Public Repository Governance Sync: Complete Setup Guide

## Architecture Overview

This guide details the setup for synchronizing repository labels and triage documentation (`docs/issue-triage.md`) from **`8fold/repo-skeleton` (Public)** to downstream repositories (such as `8fold/efqs-mono`) using GitHub Actions Reusable Workflows and the built-in `GITHUB_TOKEN`.

```
8fold/repo-skeleton (Public main)
- Guaranteed label set
- docs/issue-triage.md
- .github/workflows/syn-triage.yml

Invoked via reuseable workflow in
downstream repositories: .github/workflows/sync.yml
```

---

## Step 0: Prune Unnecessary Secrets & Credentials

Because the source repository (`8fold/repo-skeleton`) is public, reading its documentation and querying its labels requires no special authentication.

1. Navigate to your downstream repositories (or Organization Settings) under **Settings → Secrets and variables → Actions**.
2. **Remove obsolete secrets**: You can safely delete `APP_CLIENT_ID` and `APP_PRIVATE_KEY` if they were previously created for this workflow.
3. **Verify Workflow Permissions**: In repository **Settings → Actions → General → Workflow permissions**, ensure the default permission is set to **Read and write permissions** (or rely on explicit job-level `permissions:` blocks inside the workflow file).

---

## Step 1: Configure `8fold/repo-skeleton` (Public Master Repository)

1. Ensure the repository visibility for `8fold/repo-skeleton` is set to **Public**.
2. Create the file **`.github/workflows/sync-triage-reusable-public.yml`** in `8fold/repo-skeleton` with the following content:

```yaml
name: Reusable Label & Triage Sync

on:
  workflow_call:
    inputs:
      source_repo:
        required: false
        type: string
        default: '8fold/repo-skeleton'

permissions:
  contents: write
  issues: write

jobs:
  sync-assets:
    name: Sync Assets from Master Template
    runs-on: ubuntu-latest
    if: ${{ github.repository != inputs.source_repo }}

    steps:
      - name: Checkout Target Repo
        uses: actions/checkout@v6.0.2
        with:
          persist-credentials: true

      - name: Checkout Public Master Source Repo
        uses: actions/checkout@v6.0.2
        with:
          repository: ${{ inputs.source_repo }}
          path: temp-source-repo

      - name: Sync Triage Markdown File
        run: |
          mkdir -p docs
          if [ -f temp-source-repo/docs/issue-triage.md ]; then
            cp temp-source-repo/docs/issue-triage.md docs/issue-triage.md
          fi
          rm -rf temp-source-repo

      - name: Commit and Push File Updates
        env:
          GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "41898282+github-actions[bot]@users.noreply.github.com"

          git add docs/issue-triage.md
          if ! git diff --cached --quiet; then
            git commit -m "sync: update docs/issue-triage.md from repo-skeleton"
            git push
          fi

      - name: Sync and Prune Labels via GitHub API
        env:
          GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          SOURCE_REPO: ${{ inputs.source_repo }}
          TARGET_REPO: ${{ github.repository }}
        run: |
          gh api "repos/$SOURCE_REPO/labels" --paginate > source_labels.json
          gh api "repos/$TARGET_REPO/labels" --paginate > target_labels.json

          # 1. Upsert labels from master source to target
          jq -c '.[]' source_labels.json | while read -r label; do
            NAME=$(echo "$label" | jq -r '.name')
            COLOR=$(echo "$label" | jq -r '.color')
            DESC=$(echo "$label" | jq -r '.description // ""')

            ENCODED_NAME=$(python3 -c "import urllib.parse, sys; print(urllib.parse.quote(sys.argv[1]))" "$NAME")

            gh api "repos/$TARGET_REPO/labels/$ENCODED_NAME" \
              -X PATCH -f color="$COLOR" -f description="$DESC" 2>/dev/null || \
            gh api "repos/$TARGET_REPO/labels" \
              -X POST -f name="$NAME" -f color="$COLOR" -f description="$DESC"
          done

          # 2. Prune obsolete target labels absent from master source
          jq -r '.[].name' source_labels.json > source_names.txt

          jq -c '.[]' target_labels.json | while read -r label; do
            TARGET_NAME=$(echo "$label" | jq -r '.name')

            if ! grep -Fxc "$TARGET_NAME" source_names.txt >/dev/null; then
              echo "Pruning obsolete label: $TARGET_NAME"
              ENCODED_TARGET_NAME=$(python3 -c "import urllib.parse, sys; print(urllib.parse.quote(sys.argv[1]))" "$TARGET_NAME")
              gh api "repos/$TARGET_REPO/labels/$ENCODED_TARGET_NAME" -X DELETE
            fi
          done
```

---

## Step 2: Deploy Caller in Downstream Repositories

In `8fold/efqs-mono` and any other repository created from `repo-skeleton`, add the following 5-line caller file at **`.github/workflows/sync.yml`**:

```yaml
name: Sync Governance Assets

on:
  schedule:
    - cron: '0 0 1 */3 *' # Quarterly execution
  workflow_dispatch:     # Manual trigger

jobs:
  call-sync:
    uses: 8fold/repo-skeleton/.github/workflows/sync-triage-reusable-public.yml@main
```

---

## Verification & Testing

1. In any downstream repository (`efqs-mono`), navigate to **Actions → Sync Governance Assets**.
2. Click **Run workflow** (triggering `workflow_dispatch`).
3. Verify that:
   * `docs/issue-triage.md` is updated and committed automatically by `github-actions[bot]`.
   * Repository labels match `8fold/repo-skeleton` exactly, with obsolete labels pruned cleanly.
