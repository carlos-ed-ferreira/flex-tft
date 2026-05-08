# Copilot Instructions

This repository keeps source code free of comments.

Do not add code comments unless the user explicitly asks for them. This includes line comments, block comments, docblocks, PHPDoc, JSDoc, HTML comments, Vue template comments, Blade comments, CSS comments, and commented-out code.

Prefer clear names, smaller functions, typed signatures, and direct structure over explanatory comments. When code would need a comment to be understood, refactor the code first.

## Endpoints and tests

When creating a new endpoint, always create corresponding feature tests covering status code, response structure, and relevant business rules.

When modifying an existing endpoint, review its existing feature tests and update them if the behavior, structure, or contract has changed.
