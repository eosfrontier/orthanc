Only look at PLAN.md. We use this file to work out what we want to build.

feature/v3-scaffold is the feature branch for orthanc we use.

When working on a task:
- Create a new branch per issue (e.g. `feature/v3-01-env-auth-config`), branched from the previous task's branch or `master`

Code style:
- Always add PHPDoc blocks to classes, enums, and public methods

When completing a task:
1. Check off the checkbox in PLAN.md (`- [ ]` → `- [x]`)
2. Add a comment on the linked GitHub issue with a short summary (do NOT close it — issues are closed when the PR is merged)
3. Commit and push the branch
4. Create a PR linking to the issue (use `Closes #N` or `Resolves #N` in the PR body)
