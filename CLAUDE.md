# Project Instructions

## Plan
- Master plan: `/root/.claude/plans/luminous-splashing-cosmos.md`
- Approach: PSMA (Plan, Study, Map, Act) - always study OC3 source and OC4 patterns before writing code
- OC3 source: `oc3-extension/upload/` - the original extension to port from
- OC4 reference: `opencart-4.1.0.3/upload/` - patterns to follow
- Extension output: `extension/lookersolution/` - where our code lives
- 100% feature parity required - port every feature, every edge case, every detail

## Style
- No emojis in responses or code
- No comments in code unless absolutely necessary
- No shortcuts, No workarounds, no hacks
- Keep text concise - no long documentation
- No mentioning of CLAUDE in code or comments or responses or in documentation or anywhere , neither in git history, git commit messages or anywhere else

## Development
- Always use official API and latest stable versions
- Always write clean, maintainable code
- No hardcoding values - use config files or environment variables
- Don't commit the buggy code - ensure everything works before pushing
- When coding on my code make sure you don't asume anything about the codebase - check existing code and patterns
- Proper error handling and logging
- Optimize for performance and scalability
- we use github issues for task management so make sure you create proper issues for any new tasks or bugs found.
- you can use gh cli for managing issues and pull requests from this machine and i think the repo do have access to on the server as well.
