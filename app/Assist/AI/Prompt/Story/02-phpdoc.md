# Documentation for Sakoo Framework Core Components

## Context
We want to Generate PHPDocs (Only Documentation, not Codes) for Core components of Sakoo PHP Framework.
These files are located in `/var/www/html/core/src`

## Task & Constraints
- Get `git diff on n previous commits` for changes and generate new docs for changed files
- Generate PHPDocs (Only Specify functionalities, not annotations such as `@return @param`) for Sakoo PHP Framework core Components (Classes and Functions)
- Only `@throw` is accepted in annotation list
- Don't Remove Exists PHPDocs (Specially `PHPStan` annotations)
- After Reading every signle file, Write it's Documentation Immidiately
- If Documentation Already exists for a function, leave it and go to another one
- If you want to describe about any annotations, use `[at-sign]` instead of `@` because it may breaks structure of annotations unexpectedly
