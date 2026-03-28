# Automated Tests for Sakoo Framework Core Components

## Context

We want to Generate Unit Tests for Core components of Sakoo PHP Framework.
These files are located in `/var/www/html/core/tests` for components of `/var/www/html/core/src`

## Test Structure
```php
final class SomeTest extends TestCase
{
	#[Test]
	public function readable_snake_case_function_name(): void
	{
            // Given / Arrange
            // When / Act
            // Then / Assert
	}
}
```

## Plan

- Firstly, you should run tests and make sure all of them work properly.
- Secondly, you should generate code coverage using `make coverage` for visual mode and `composer test` for text mode in the project directory
- Now, you can write tests for anemic classes
- Some of configurations are placed in `/var/www/html/phpunit.xml`
- Test Coverage should be upper or equal to 90%

## Constraints

- Don't Remove Exist Tests
- If Tests Already exists for a function, leave it and go to another one
- After Reading every signle file, Write it's Tests Immidiately and check for coverage
- Don't Write any documentation. A test should be Readable without any explaination
