# 📚 Documentation

## 📦 Sakoo\Framework\Core\Logger

### 🟢 FileLogger

---

##### Contract

```php
public function log( $level, Stringable|string $message, array $context): void
```

##### Usage

```php
$fileLogger->log($level, $message, $context)
```

<sub><sup>@param string $level</sup></sub>

<sub><sup>@throws \Exception|\Throwable</sup></sub>

### 🟢 LogFormatter

---

#### How to use the Class:

```php
$logFormatter = new LogFormatter(string $level, Stringable|string $message, string $mode, string $env)
```

## 📦 Sakoo\Framework\Core\VarDump\Cli

### 🟢 CliFormatter

---

#### How to use the Class:

```php
$cliFormatter = new CliFormatter(Output $output)
```

---

##### Contract

```php
public function format(mixed $value): void
```

##### Usage

```php
$cliFormatter->format($value)
```

---

##### Contract

```php
protected function formatType(mixed $value, int $depth): string
```

##### Usage

```php
$cliFormatter->formatType($value, $depth)
```

### 🟢 CliDumper

---

#### How to use the Class:

```php
$cliDumper = new CliDumper(Formatter $formatter)
```

---

##### Contract

```php
public function dump(mixed $value): void
```

##### Usage

```php
$cliDumper->dump($value)
```

## 📦 Sakoo\Framework\Core\VarDump

### 🟢 VarDump

---

##### Contract

```php
public static function dieDump(mixed $vars): never
```

##### Usage

```php
VarDump::dieDump($vars)
```

---

##### Contract

```php
public static function dump(mixed $vars): void
```

##### Usage

```php
VarDump::dump($vars)
```

## 📦 Sakoo\Framework\Core\VarDump\Http

### 🟢 HttpDumper

---

#### How to use the Class:

```php
$httpDumper = new HttpDumper(Formatter $formatter)
```

---

##### Contract

```php
public function dump(mixed $value): void
```

##### Usage

```php
$httpDumper->dump($value)
```

### 🟢 HttpFormatter

---

#### How to use the Class:

```php
$httpFormatter = new HttpFormatter()
```

---

##### Contract

```php
public function format(mixed $value): void
```

##### Usage

```php
$httpFormatter->format($value)
```

## 📦 Sakoo\Framework\Core\Watcher

### 🟢 Watcher

---

#### How to use the Class:

```php
$watcher = new Watcher(WatcherDriver $driver)
```

---

##### Contract

```php
public function watch(array $files, FileSystemAction $callback): self
```

##### Usage

```php
$watcher->watch($files, $callback)
```

<sub><sup>@param \SplFileObject[] $files</sup></sub>

---

##### Contract

```php
public function run(): void
```

##### Usage

```php
$watcher->run()
```

---

##### Contract

```php
public function check(): void
```

##### Usage

```php
$watcher->check()
```

### 🟢 EventTypes

---

##### Contract

```php
public static function cases(): array
```

##### Usage

```php
EventTypes::cases()
```

## 📦 Sakoo\Framework\Core\Watcher\Inotify

### 🟢 Event

---

#### How to use the Class:

```php
$event = new Event(File $file, array $event)
```

<sub><sup>@param int[] $event</sup></sub>

---

##### Contract

```php
public function getFile(): File
```

##### Usage

```php
$event->getFile()
```

---

##### Contract

```php
public function getHandlerId(): int
```

##### Usage

```php
$event->getHandlerId()
```

---

##### Contract

```php
public function getType(): EventTypes
```

##### Usage

```php
$event->getType()
```

---

##### Contract

```php
public function getGroupId(): int
```

##### Usage

```php
$event->getGroupId()
```

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$event->getName()
```

### 🟢 File

---

#### How to use the Class:

```php
$file = new File(int $id, string $path, FileSystemAction $callback, Locker $locker)
```

---

##### Contract

```php
public function getId(): int
```

##### Usage

```php
$file->getId()
```

---

##### Contract

```php
public function getCallback(): FileSystemAction
```

##### Usage

```php
$file->getCallback()
```

---

##### Contract

```php
public function getPath(): string
```

##### Usage

```php
$file->getPath()
```

---

##### Contract

```php
public function getLocker(): Locker
```

##### Usage

```php
$file->getLocker()
```

### 🟢 Inotify

---

#### How to use the Class:

```php
$inotify = new Inotify()
```

---

##### Contract

```php
public function watch(string $file, FileSystemAction $callback): void
```

##### Usage

```php
$inotify->watch($file, $callback)
```

---

##### Contract

```php
public function wait(): IterableInterface
```

##### Usage

```php
$inotify->wait()
```

---

##### Contract

```php
public function blind(int $id): bool
```

##### Usage

```php
$inotify->blind($id)
```

## 📦 Sakoo\Framework\Core\Path

### 🟢 Path

---

##### Contract

```php
public static function getRootDir(): string|false
```

##### Usage

```php
Path::getRootDir()
```

---

##### Contract

```php
public static function getCoreDir(): string|false
```

##### Usage

```php
Path::getCoreDir()
```

---

##### Contract

```php
public static function getVendorDir(): string
```

##### Usage

```php
Path::getVendorDir()
```

---

##### Contract

```php
public static function getStorageDir(): string
```

##### Usage

```php
Path::getStorageDir()
```

---

##### Contract

```php
public static function getLogsDir(): string
```

##### Usage

```php
Path::getLogsDir()
```

---

##### Contract

```php
public static function getTempTestDir(): string
```

##### Usage

```php
Path::getTempTestDir()
```

---

##### Contract

```php
public static function getProjectPHPFiles(): array
```

##### Usage

```php
Path::getProjectPHPFiles()
```

<sub><sup>@return SplFileObject[]</sup></sub>

---

##### Contract

```php
public static function getCorePHPFiles(): array
```

##### Usage

```php
Path::getCorePHPFiles()
```

<sub><sup>@return SplFileObject[]</sup></sub>

---

##### Contract

```php
public static function getPHPFilesOf(string $path): array
```

##### Usage

```php
Path::getPHPFilesOf($path)
```

<sub><sup>@return SplFileObject[]</sup></sub>

---

##### Contract

```php
public static function namespaceToPath(string $namespace): string
```

##### Usage

```php
Path::namespaceToPath($namespace)
```

---

##### Contract

```php
public static function pathToNamespace(string $path): string
```

##### Usage

```php
Path::pathToNamespace($path)
```

<sub><sup>@return class-string</sup></sub>

## 📦 Sakoo\Framework\Core\FileSystem\Storages\Local

### 🟢 Local

---

#### How to use the Class:

```php
$local = new Local(string $path)
```

---

##### Contract

```php
public function create(bool $asDirectory): bool
```

##### Usage

```php
$local->create($asDirectory)
```

---

##### Contract

```php
public function mkdir(bool $recursive): bool
```

##### Usage

```php
$local->mkdir($recursive)
```

---

##### Contract

```php
public function exists(): bool
```

##### Usage

```php
$local->exists()
```

---

##### Contract

```php
public function remove(): bool
```

##### Usage

```php
$local->remove()
```

---

##### Contract

```php
public function isDir(): bool
```

##### Usage

```php
$local->isDir()
```

---

##### Contract

```php
public function move(string $to): bool
```

##### Usage

```php
$local->move($to)
```

---

##### Contract

```php
public function copy(string $to): bool
```

##### Usage

```php
$local->copy($to)
```

---

##### Contract

```php
public function parentDir(): string
```

##### Usage

```php
$local->parentDir()
```

---

##### Contract

```php
public function rename(string $to): bool
```

##### Usage

```php
$local->rename($to)
```

---

##### Contract

```php
public function files(): array
```

##### Usage

```php
$local->files()
```

<sub><sup>@throws InvalidArgumentException</sup></sub>

---

##### Contract

```php
public function write(string $data): bool
```

##### Usage

```php
$local->write($data)
```

---

##### Contract

```php
public function append(string $data): bool
```

##### Usage

```php
$local->append($data)
```

---

##### Contract

```php
public function readLines(): array|false
```

##### Usage

```php
$local->readLines()
```

<sub><sup>@return false|string[]</sup></sub>

---

##### Contract

```php
public function setPermission(string|int $permission): bool
```

##### Usage

```php
$local->setPermission($permission)
```

---

##### Contract

```php
public function getPermission(): mixed
```

##### Usage

```php
$local->getPermission()
```

---

##### Contract

```php
public function getPath(): string
```

##### Usage

```php
$local->getPath()
```

## 📦 Sakoo\Framework\Core\FileSystem

### 🟢 File

---

##### Contract

```php
public static function open(Disk $storage, string $path): Storage
```

##### Usage

```php
File::open($storage, $path)
```

### 🟢 Disk

---

##### Contract

```php
public static function cases(): array
```

##### Usage

```php
Disk::cases()
```

---

#### How to use the Class:

```php
$disk = Disk::from(string|int $value)
```

---

#### How to use the Class:

```php
$disk = Disk::tryFrom(string|int $value)
```

### 🟢 Permission

---

##### Contract

```php
public static function allNothing(): string
```

##### Usage

```php
Permission::allNothing()
```

---

##### Contract

```php
public static function allExecute(): string
```

##### Usage

```php
Permission::allExecute()
```

---

##### Contract

```php
public static function allWrite(): string
```

##### Usage

```php
Permission::allWrite()
```

---

##### Contract

```php
public static function allExecuteWrite(): string
```

##### Usage

```php
Permission::allExecuteWrite()
```

---

##### Contract

```php
public static function allRead(): string
```

##### Usage

```php
Permission::allRead()
```

---

##### Contract

```php
public static function allExecuteRead(): string
```

##### Usage

```php
Permission::allExecuteRead()
```

---

##### Contract

```php
public static function allWriteRead(): string
```

##### Usage

```php
Permission::allWriteRead()
```

---

##### Contract

```php
public static function allExecuteWriteRead(): string
```

##### Usage

```php
Permission::allExecuteWriteRead()
```

---

##### Contract

```php
public static function getExecutables(): array
```

##### Usage

```php
Permission::getExecutables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function getNotExecutables(): array
```

##### Usage

```php
Permission::getNotExecutables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function getWritables(): array
```

##### Usage

```php
Permission::getWritables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function getNotWritables(): array
```

##### Usage

```php
Permission::getNotWritables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function getReadables(): array
```

##### Usage

```php
Permission::getReadables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function getNotReadables(): array
```

##### Usage

```php
Permission::getNotReadables()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public static function make(int $user, int $group, int $others): string
```

##### Usage

```php
Permission::make($user, $group, $others)
```

---

##### Contract

```php
public static function getFileDefault(): string
```

##### Usage

```php
Permission::getFileDefault()
```

---

##### Contract

```php
public static function getDirectoryDefault(): string
```

##### Usage

```php
Permission::getDirectoryDefault()
```

## 📦 Sakoo\Framework\Core\Assert

### 🟢 Assert

---

##### Contract

```php
public static function that(mixed $value): AssertionChain
```

##### Usage

```php
Assert::that($value)
```

---

##### Contract

```php
public static function lazy(): LazyAssertion
```

##### Usage

```php
Assert::lazy()
```

---

##### Contract

```php
protected static function throwIf(bool $condition, string $message): void
```

##### Usage

```php
Assert::throwIf($condition, $message)
```

<sub><sup>@throws InvalidArgumentException</sup></sub>

---

##### Contract

```php
protected static function throwUnless(bool $condition, string $message): void
```

##### Usage

```php
Assert::throwUnless($condition, $message)
```

<sub><sup>@throws InvalidArgumentException</sup></sub>

---

##### Contract

```php
public static function true(mixed $value, string $message): void
```

##### Usage

```php
Assert::true($value, $message)
```

---

##### Contract

```php
public static function false(mixed $value, string $message): void
```

##### Usage

```php
Assert::false($value, $message)
```

---

##### Contract

```php
public static function bool(mixed $value, string $message): void
```

##### Usage

```php
Assert::bool($value, $message)
```

---

##### Contract

```php
public static function notBool(mixed $value, string $message): void
```

##### Usage

```php
Assert::notBool($value, $message)
```

---

##### Contract

```php
public static function callable(mixed $value, string $message): void
```

##### Usage

```php
Assert::callable($value, $message)
```

---

##### Contract

```php
public static function notCallable(mixed $value, string $message): void
```

##### Usage

```php
Assert::notCallable($value, $message)
```

---

##### Contract

```php
public static function dir(string $value, string $message): void
```

##### Usage

```php
Assert::dir($value, $message)
```

---

##### Contract

```php
public static function notDir(string $value, string $message): void
```

##### Usage

```php
Assert::notDir($value, $message)
```

---

##### Contract

```php
public static function file(string $value, string $message): void
```

##### Usage

```php
Assert::file($value, $message)
```

---

##### Contract

```php
public static function notFile(string $value, string $message): void
```

##### Usage

```php
Assert::notFile($value, $message)
```

---

##### Contract

```php
public static function link(string $value, string $message): void
```

##### Usage

```php
Assert::link($value, $message)
```

---

##### Contract

```php
public static function notLink(string $value, string $message): void
```

##### Usage

```php
Assert::notLink($value, $message)
```

---

##### Contract

```php
public static function uploadedFile(string $value, string $message): void
```

##### Usage

```php
Assert::uploadedFile($value, $message)
```

---

##### Contract

```php
public static function notUploadedFile(string $value, string $message): void
```

##### Usage

```php
Assert::notUploadedFile($value, $message)
```

---

##### Contract

```php
public static function executableFile(string $value, string $message): void
```

##### Usage

```php
Assert::executableFile($value, $message)
```

---

##### Contract

```php
public static function notExecutableFile(string $value, string $message): void
```

##### Usage

```php
Assert::notExecutableFile($value, $message)
```

---

##### Contract

```php
public static function writableFile(string $value, string $message): void
```

##### Usage

```php
Assert::writableFile($value, $message)
```

---

##### Contract

```php
public static function notWritableFile(string $value, string $message): void
```

##### Usage

```php
Assert::notWritableFile($value, $message)
```

---

##### Contract

```php
public static function readableFile(string $value, string $message): void
```

##### Usage

```php
Assert::readableFile($value, $message)
```

---

##### Contract

```php
public static function notReadableFile(string $value, string $message): void
```

##### Usage

```php
Assert::notReadableFile($value, $message)
```

---

##### Contract

```php
public static function exists(string $value, string $message): void
```

##### Usage

```php
Assert::exists($value, $message)
```

---

##### Contract

```php
public static function notExists(string $value, string $message): void
```

##### Usage

```php
Assert::notExists($value, $message)
```

---

##### Contract

```php
public static function length(string $value, int $length, string $message): void
```

##### Usage

```php
Assert::length($value, $length, $message)
```

---

##### Contract

```php
public static function count(Countable|array $value, int $count, string $message): void
```

##### Usage

```php
Assert::count($value, $count, $message)
```

---

##### Contract

```php
public static function equals(mixed $value, mixed $expected, string $message): void
```

##### Usage

```php
Assert::equals($value, $expected, $message)
```

---

##### Contract

```php
public static function notEquals(mixed $value, mixed $expected, string $message): void
```

##### Usage

```php
Assert::notEquals($value, $expected, $message)
```

---

##### Contract

```php
public static function same(mixed $value, mixed $expected, string $message): void
```

##### Usage

```php
Assert::same($value, $expected, $message)
```

---

##### Contract

```php
public static function notSame(mixed $value, mixed $expected, string $message): void
```

##### Usage

```php
Assert::notSame($value, $expected, $message)
```

---

##### Contract

```php
public static function empty(mixed $value, string $message): void
```

##### Usage

```php
Assert::empty($value, $message)
```

---

##### Contract

```php
public static function notEmpty(mixed $value, string $message): void
```

##### Usage

```php
Assert::notEmpty($value, $message)
```

---

##### Contract

```php
public static function null(mixed $value, string $message): void
```

##### Usage

```php
Assert::null($value, $message)
```

---

##### Contract

```php
public static function notNull(mixed $value, string $message): void
```

##### Usage

```php
Assert::notNull($value, $message)
```

---

##### Contract

```php
public static function numeric(mixed $value, string $message): void
```

##### Usage

```php
Assert::numeric($value, $message)
```

---

##### Contract

```php
public static function notNumeric(mixed $value, string $message): void
```

##### Usage

```php
Assert::notNumeric($value, $message)
```

---

##### Contract

```php
public static function finite(float $value, string $message): void
```

##### Usage

```php
Assert::finite($value, $message)
```

---

##### Contract

```php
public static function infinite(float $value, string $message): void
```

##### Usage

```php
Assert::infinite($value, $message)
```

---

##### Contract

```php
public static function float(mixed $value, string $message): void
```

##### Usage

```php
Assert::float($value, $message)
```

---

##### Contract

```php
public static function notFloat(mixed $value, string $message): void
```

##### Usage

```php
Assert::notFloat($value, $message)
```

---

##### Contract

```php
public static function int(mixed $value, string $message): void
```

##### Usage

```php
Assert::int($value, $message)
```

---

##### Contract

```php
public static function notInt(mixed $value, string $message): void
```

##### Usage

```php
Assert::notInt($value, $message)
```

---

##### Contract

```php
public static function greater(int $value, int $expected, string $message): void
```

##### Usage

```php
Assert::greater($value, $expected, $message)
```

---

##### Contract

```php
public static function greaterOrEquals(int $value, int $expected, string $message): void
```

##### Usage

```php
Assert::greaterOrEquals($value, $expected, $message)
```

---

##### Contract

```php
public static function lower(int $value, int $expected, string $message): void
```

##### Usage

```php
Assert::lower($value, $expected, $message)
```

---

##### Contract

```php
public static function lowerOrEquals(int $value, int $expected, string $message): void
```

##### Usage

```php
Assert::lowerOrEquals($value, $expected, $message)
```

---

##### Contract

```php
public static function object(mixed $value, string $message): void
```

##### Usage

```php
Assert::object($value, $message)
```

---

##### Contract

```php
public static function notObject(mixed $value, string $message): void
```

##### Usage

```php
Assert::notObject($value, $message)
```

---

##### Contract

```php
public static function instanceOf(mixed $value, string $class, string $message): void
```

##### Usage

```php
Assert::instanceOf($value, $class, $message)
```

---

##### Contract

```php
public static function notInstanceOf(mixed $value, string $class, string $message): void
```

##### Usage

```php
Assert::notInstanceOf($value, $class, $message)
```

---

##### Contract

```php
public static function resource(mixed $value, string $message): void
```

##### Usage

```php
Assert::resource($value, $message)
```

---

##### Contract

```php
public static function notResource(mixed $value, string $message): void
```

##### Usage

```php
Assert::notResource($value, $message)
```

---

##### Contract

```php
public static function scalar(mixed $value, string $message): void
```

##### Usage

```php
Assert::scalar($value, $message)
```

---

##### Contract

```php
public static function notScalar(mixed $value, string $message): void
```

##### Usage

```php
Assert::notScalar($value, $message)
```

---

##### Contract

```php
public static function string(mixed $value, string $message): void
```

##### Usage

```php
Assert::string($value, $message)
```

---

##### Contract

```php
public static function notString(mixed $value, string $message): void
```

##### Usage

```php
Assert::notString($value, $message)
```

---

##### Contract

```php
public static function array(mixed $value, string $message): void
```

##### Usage

```php
Assert::array($value, $message)
```

---

##### Contract

```php
public static function notArray(mixed $value, string $message): void
```

##### Usage

```php
Assert::notArray($value, $message)
```

---

##### Contract

```php
public static function countable(mixed $value, string $message): void
```

##### Usage

```php
Assert::countable($value, $message)
```

---

##### Contract

```php
public static function notCountable(mixed $value, string $message): void
```

##### Usage

```php
Assert::notCountable($value, $message)
```

---

##### Contract

```php
public static function iterable(mixed $value, string $message): void
```

##### Usage

```php
Assert::iterable($value, $message)
```

---

##### Contract

```php
public static function notIterable(mixed $value, string $message): void
```

##### Usage

```php
Assert::notIterable($value, $message)
```

## 📦 Sakoo\Framework\Core\Assert\Exception

### 🟥 LazyAssertionException

---

##### Contract

```php
public static function init(array $exceptions): self
```

##### Usage

```php
LazyAssertionException::init($exceptions)
```

<sub><sup>@phpstan-param array&lt;string,array&lt;int,InvalidArgumentException&gt;&gt; $exceptions</sup></sub>

### 🟥 InvalidArgumentException

## 📦 Sakoo\Framework\Core\Regex

### 🟢 Regex

---

#### How to use the Class:

```php
$regex = new Regex(string $pattern)
```

---

##### Contract

```php
public function safeAdd(string $value): static
```

##### Usage

```php
$regex->safeAdd($value)
```

---

##### Contract

```php
public function add(string $value): static
```

##### Usage

```php
$regex->add($value)
```

---

##### Contract

```php
public function startOfLine(): static
```

##### Usage

```php
$regex->startOfLine()
```

---

##### Contract

```php
public function endOfLine(): static
```

##### Usage

```php
$regex->endOfLine()
```

---

##### Contract

```php
public function startsWith(callable|string $value): static
```

##### Usage

```php
$regex->startsWith($value)
```

---

##### Contract

```php
public function endsWith(callable|string $value): static
```

##### Usage

```php
$regex->endsWith($value)
```

---

##### Contract

```php
public function digit(int $length): static
```

##### Usage

```php
$regex->digit($length)
```

---

##### Contract

```php
public function oneOf(array $value): static
```

##### Usage

```php
$regex->oneOf($value)
```

<sub><sup>@param string[] $value</sup></sub>

---

##### Contract

```php
public function wrap(callable|string $value, bool $nonCapturing): static
```

##### Usage

```php
$regex->wrap($value, $nonCapturing)
```

---

##### Contract

```php
public function bracket(callable|string $value): static
```

##### Usage

```php
$regex->bracket($value)
```

---

##### Contract

```php
public function maybe(string $value): static
```

##### Usage

```php
$regex->maybe($value)
```

---

##### Contract

```php
public function anything(): static
```

##### Usage

```php
$regex->anything()
```

---

##### Contract

```php
public function something(): static
```

##### Usage

```php
$regex->something()
```

---

##### Contract

```php
public function unixLineBreak(): static
```

##### Usage

```php
$regex->unixLineBreak()
```

---

##### Contract

```php
public function windowsLineBreak(): static
```

##### Usage

```php
$regex->windowsLineBreak()
```

---

##### Contract

```php
public function tab(): static
```

##### Usage

```php
$regex->tab()
```

---

##### Contract

```php
public function space(): static
```

##### Usage

```php
$regex->space()
```

---

##### Contract

```php
public function word(): static
```

##### Usage

```php
$regex->word()
```

---

##### Contract

```php
public function chars(string $values): static
```

##### Usage

```php
$regex->chars($values)
```

---

##### Contract

```php
public function anythingWithout(callable|string $value): static
```

##### Usage

```php
$regex->anythingWithout($value)
```

---

##### Contract

```php
public function somethingWithout(callable|string $value): static
```

##### Usage

```php
$regex->somethingWithout($value)
```

---

##### Contract

```php
public function anythingWith(callable|string $value): static
```

##### Usage

```php
$regex->anythingWith($value)
```

---

##### Contract

```php
public function somethingWith(callable|string $value): static
```

##### Usage

```php
$regex->somethingWith($value)
```

---

##### Contract

```php
public function escapeChars(string $value): string
```

##### Usage

```php
$regex->escapeChars($value)
```

---

##### Contract

```php
public function lookahead(callable|string $value): static
```

##### Usage

```php
$regex->lookahead($value)
```

---

##### Contract

```php
public function lookbehind(callable|string $value): static
```

##### Usage

```php
$regex->lookbehind($value)
```

---

##### Contract

```php
public function negativeLookahead(callable|string $value): static
```

##### Usage

```php
$regex->negativeLookahead($value)
```

---

##### Contract

```php
public function negativeLookbehind(callable|string $value): static
```

##### Usage

```php
$regex->negativeLookbehind($value)
```

---

##### Contract

```php
public function match(string $value): array
```

##### Usage

```php
$regex->match($value)
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public function matchAll(string $value): array
```

##### Usage

```php
$regex->matchAll($value)
```

<sub><sup>@return string[][]</sup></sub>

---

##### Contract

```php
public function test(string $value): bool
```

##### Usage

```php
$regex->test($value)
```

---

##### Contract

```php
public function replace(Stringable|string $string, string $replace): array|string|null
```

##### Usage

```php
$regex->replace($string, $replace)
```

<sub><sup>@return null|string|string[]</sup></sub>

---

##### Contract

```php
public function split(Stringable|string $subject): array|false
```

##### Usage

```php
$regex->split($subject)
```

<sub><sup>@return false|string[]</sup></sub>

---

##### Contract

```php
public function get(): string
```

##### Usage

```php
$regex->get()
```

### 🟢 RegexHelper

---

##### Contract

```php
public static function findCamelCase(): Regex
```

##### Usage

```php
RegexHelper::findCamelCase()
```

---

##### Contract

```php
public static function getSpaceBetweenWords(): Regex
```

##### Usage

```php
RegexHelper::getSpaceBetweenWords()
```

---

##### Contract

```php
public static function getSpecialChars(): Regex
```

##### Usage

```php
RegexHelper::getSpecialChars()
```

## 📦 Sakoo\Framework\Core\Env

### 🟢 Env

---

##### Contract

```php
public static function get(string $key, mixed $default): mixed
```

##### Usage

```php
Env::get($key, $default)
```

---

##### Contract

```php
public static function load(Storage $file): void
```

##### Usage

```php
Env::load($file)
```

## 📦 Sakoo\Framework\Core\Testing

### 🟢 ExceptionAssertion

---

#### How to use the Class:

```php
$exceptionAssertion = new ExceptionAssertion(TestCase $phpunit,  $fn)
```

<sub><sup>@param callable $fn</sup></sub>

---

##### Contract

```php
public function withCode(int $code): static
```

##### Usage

```php
$exceptionAssertion->withCode($code)
```

---

##### Contract

```php
public function withType(string $type): static
```

##### Usage

```php
$exceptionAssertion->withType($type)
```

---

##### Contract

```php
public function withMessage(string $message): static
```

##### Usage

```php
$exceptionAssertion->withMessage($message)
```

---

##### Contract

```php
public function validate(): void
```

##### Usage

```php
$exceptionAssertion->validate()
```

## 📦 Sakoo\Framework\Core\Container

### 🟢 Container

---

#### How to use the Class:

```php
$container = new Container(string $cachePath)
```

---

##### Contract

```php
public function get(string $id): object
```

##### Usage

```php
$container->get($id)
```

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws ContainerNotFoundException</sup></sub>

---

##### Contract

```php
public function has(string $id): bool
```

##### Usage

```php
$container->has($id)
```

---

##### Contract

```php
public function bind(string $interface, callable|object|string $factory): void
```

##### Usage

```php
$container->bind($interface, $factory)
```

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws TypeMismatchException</sup></sub>

---

##### Contract

```php
public function singleton(string $interface, callable|object|string $factory): void
```

##### Usage

```php
$container->singleton($interface, $factory)
```

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws TypeMismatchException</sup></sub>

---

##### Contract

```php
public function resolve(string $interface): object
```

##### Usage

```php
$container->resolve($interface)
```

<sub><sup>@throws \ReflectionException</sup></sub>

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws ClassNotInstantiableException</sup></sub>

<sub><sup>@throws ClassNotFoundException</sup></sub>

---

##### Contract

```php
public function new(string $class, array $params): object
```

##### Usage

```php
$container->new($class, $params)
```

<sub><sup>@param array&lt;mixed&gt; $params</sup></sub>

<sub><sup>@throws \ReflectionException</sup></sub>

<sub><sup>@throws ClassNotFoundException</sup></sub>

<sub><sup>@throws ClassNotInstantiableException</sup></sub>

<sub><sup>@throws \Throwable</sup></sub>

---

##### Contract

```php
public function clear(): void
```

##### Usage

```php
$container->clear()
```

---

##### Contract

```php
public function loadCache(): void
```

##### Usage

```php
$container->loadCache()
```

<sub><sup>@throws \Throwable</sup></sub>

---

##### Contract

```php
public function flushCache(): bool
```

##### Usage

```php
$container->flushCache()
```

---

##### Contract

```php
public function cacheExists(): bool
```

##### Usage

```php
$container->cacheExists()
```

---

##### Contract

```php
public function dumpCache(): void
```

##### Usage

```php
$container->dumpCache()
```

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws \ReflectionException</sup></sub>

<sub><sup>@throws ClassNotInstantiableException</sup></sub>

<sub><sup>@throws ClassNotFoundException</sup></sub>

## 📦 Sakoo\Framework\Core\Container\Exceptions

### 🟥 ContainerCacheException

### 🟥 TypeMismatchException

### 🟥 ClassNotFoundException

### 🟥 ContainerNotFoundException

### 🟥 ClassNotInstantiableException

## 📦 Sakoo\Framework\Core\Container\Parameter

### 🟢 ParameterSet

---

#### How to use the Class:

```php
$parameterSet = new ParameterSet(Container $container)
```

---

##### Contract

```php
public function resolve(array $parameters): array
```

##### Usage

```php
$parameterSet->resolve($parameters)
```

<sub><sup>@param array&lt;\ReflectionParameter&gt; $parameters</sup></sub>

<sub><sup>@return list&lt;mixed&gt;</sup></sub>

<sub><sup>@throws \ReflectionException</sup></sub>

<sub><sup>@throws ClassNotFoundException</sup></sub>

<sub><sup>@throws ClassNotInstantiableException</sup></sub>

<sub><sup>@throws \Throwable</sup></sub>

### 🟢 Parameter

---

#### How to use the Class:

```php
$parameter = new Parameter(Container $container)
```

---

##### Contract

```php
public function resolve(ReflectionParameter $parameter): mixed
```

##### Usage

```php
$parameter->resolve($parameter)
```

<sub><sup>@throws \Throwable</sup></sub>

<sub><sup>@throws \ReflectionException</sup></sub>

<sub><sup>@throws ClassNotInstantiableException</sup></sub>

<sub><sup>@throws ClassNotFoundException</sup></sub>

## 📦 Sakoo\Framework\Core\Markup

### 🟢 Markdown

---

#### How to use the Class:

```php
$markdown = new Markdown()
```

---

##### Contract

```php
public function write(string $value): void
```

##### Usage

```php
$markdown->write($value)
```

---

##### Contract

```php
public function writeLine(string $value): void
```

##### Usage

```php
$markdown->writeLine($value)
```

---

##### Contract

```php
public function br(): void
```

##### Usage

```php
$markdown->br()
```

---

##### Contract

```php
public function fbr(): void
```

##### Usage

```php
$markdown->fbr()
```

---

##### Contract

```php
public function callout(string $value): void
```

##### Usage

```php
$markdown->callout($value)
```

---

##### Contract

```php
public function h1(string $value): void
```

##### Usage

```php
$markdown->h1($value)
```

---

##### Contract

```php
public function h2(string $value): void
```

##### Usage

```php
$markdown->h2($value)
```

---

##### Contract

```php
public function h3(string $value): void
```

##### Usage

```php
$markdown->h3($value)
```

---

##### Contract

```php
public function h4(string $value): void
```

##### Usage

```php
$markdown->h4($value)
```

---

##### Contract

```php
public function h5(string $value): void
```

##### Usage

```php
$markdown->h5($value)
```

---

##### Contract

```php
public function h6(string $value): void
```

##### Usage

```php
$markdown->h6($value)
```

---

##### Contract

```php
public function ul(string $value): void
```

##### Usage

```php
$markdown->ul($value)
```

---

##### Contract

```php
public function link(string $url, string $text): void
```

##### Usage

```php
$markdown->link($url, $text)
```

---

##### Contract

```php
public function image(string $path, string $alt): void
```

##### Usage

```php
$markdown->image($path, $alt)
```

---

##### Contract

```php
public function checklist(string $value, bool $checked): void
```

##### Usage

```php
$markdown->checklist($value, $checked)
```

---

##### Contract

```php
public function hr(): void
```

##### Usage

```php
$markdown->hr()
```

---

##### Contract

```php
public function code(string $value, string $language): void
```

##### Usage

```php
$markdown->code($value, $language)
```

---

##### Contract

```php
public function inlineCode(string $value): void
```

##### Usage

```php
$markdown->inlineCode($value)
```

---

##### Contract

```php
public function tiny(string $value): void
```

##### Usage

```php
$markdown->tiny($value)
```

---

##### Contract

```php
public function get(): string
```

##### Usage

```php
$markdown->get()
```

## 📦 Sakoo\Framework\Core\Finder

### 🟢 FileFinder

---

#### How to use the Class:

```php
$fileFinder = new FileFinder(string $path)
```

---

##### Contract

```php
public function pattern(string $pattern): FileFinder
```

##### Usage

```php
$fileFinder->pattern($pattern)
```

---

##### Contract

```php
public function ignoreVCS(bool $value): FileFinder
```

##### Usage

```php
$fileFinder->ignoreVCS($value)
```

---

##### Contract

```php
public function ignoreVCSIgnored(bool $value): FileFinder
```

##### Usage

```php
$fileFinder->ignoreVCSIgnored($value)
```

---

##### Contract

```php
public function ignoreDotFiles(bool $value): FileFinder
```

##### Usage

```php
$fileFinder->ignoreDotFiles($value)
```

---

##### Contract

```php
public function getFiles(): array
```

##### Usage

```php
$fileFinder->getFiles()
```

<sub><sup>@return SplFileObject[]</sup></sub>

---

##### Contract

```php
public function find(): array
```

##### Usage

```php
$fileFinder->find()
```

<sub><sup>@return string[]</sup></sub>

### 🟢 SplFileObject

---

##### Contract

```php
public function isClassFile(): bool
```

##### Usage

```php
$splFileObject->isClassFile()
```

---

##### Contract

```php
public function getNamespace(): string
```

##### Usage

```php
$splFileObject->getNamespace()
```

<sub><sup>@return class-string</sup></sub>

### 🟢 GitIgnore

---

#### How to use the Class:

```php
$gitIgnore = new GitIgnore(string $path)
```

---

##### Contract

```php
public function isIgnored(string $file): bool
```

##### Usage

```php
$gitIgnore->isIgnored($file)
```

## 📦 Sakoo\Framework\Core\Locker

### 🟢 Locker

---

##### Contract

```php
public function lock(): void
```

##### Usage

```php
$locker->lock()
```

---

##### Contract

```php
public function unlock(): void
```

##### Usage

```php
$locker->unlock()
```

---

##### Contract

```php
public function isLocked(): bool
```

##### Usage

```php
$locker->isLocked()
```

## 📦 Sakoo\Framework\Core\Doc

### 🟢 Doc

---

#### How to use the Class:

```php
$doc = new Doc(array $files, Formatter $formatter, Storage $docFile)
```

---

##### Contract

```php
public function generate(): void
```

##### Usage

```php
$doc->generate()
```

## 📦 Sakoo\Framework\Core\Doc\Formatters

### 🟢 DocFormatter

---

##### Contract

```php
public function format(array $namespaces): string
```

##### Usage

```php
$docFormatter->format($namespaces)
```

---

#### How to use the Class:

```php
$docFormatter = new DocFormatter(Markup $markup)
```

### 🟢 TocFormatter

---

##### Contract

```php
public function format(array $namespaces): string
```

##### Usage

```php
$tocFormatter->format($namespaces)
```

---

#### How to use the Class:

```php
$tocFormatter = new TocFormatter(Markup $markup)
```

## 📦 Sakoo\Framework\Core\Doc\Attributes

### 🟢 DontDocument

---

#### How to use the Class:

```php
$dontDocument = new DontDocument()
```

## 📦 Sakoo\Framework\Core\Doc\Object

### 🟢 ParameterObject

---

#### How to use the Class:

```php
$parameterObject = new ParameterObject(ReflectionParameter $parameter)
```

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$parameterObject->getName()
```

---

##### Contract

```php
public function getType(): TypeObject
```

##### Usage

```php
$parameterObject->getType()
```

### 🟢 MethodObject

---

#### How to use the Class:

```php
$methodObject = new MethodObject(ClassObject $classObject, ReflectionMethod $method)
```

---

##### Contract

```php
public function getClass(): ClassObject
```

##### Usage

```php
$methodObject->getClass()
```

---

##### Contract

```php
public function getMethodParameters(): array
```

##### Usage

```php
$methodObject->getMethodParameters()
```

<sub><sup>@return ParameterObject[]</sup></sub>

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$methodObject->getName()
```

---

##### Contract

```php
public function isPrivate(): bool
```

##### Usage

```php
$methodObject->isPrivate()
```

---

##### Contract

```php
public function isProtected(): bool
```

##### Usage

```php
$methodObject->isProtected()
```

---

##### Contract

```php
public function isPublic(): bool
```

##### Usage

```php
$methodObject->isPublic()
```

---

##### Contract

```php
public function isStatic(): bool
```

##### Usage

```php
$methodObject->isStatic()
```

---

##### Contract

```php
public function isConstructor(): bool
```

##### Usage

```php
$methodObject->isConstructor()
```

---

##### Contract

```php
public function isMagicMethod(): bool
```

##### Usage

```php
$methodObject->isMagicMethod()
```

---

##### Contract

```php
public function getMethodReturnTypes(): string
```

##### Usage

```php
$methodObject->getMethodReturnTypes()
```

---

##### Contract

```php
public function getPhpDocs(): array
```

##### Usage

```php
$methodObject->getPhpDocs()
```

---

##### Contract

```php
public function getModifiers(): array
```

##### Usage

```php
$methodObject->getModifiers()
```

---

##### Contract

```php
public function isFrameworkFunction(): bool
```

##### Usage

```php
$methodObject->isFrameworkFunction()
```

---

##### Contract

```php
public function getDefaultValues(): string
```

##### Usage

```php
$methodObject->getDefaultValues()
```

---

##### Contract

```php
public function getDefaultValueTypes(): string
```

##### Usage

```php
$methodObject->getDefaultValueTypes()
```

---

##### Contract

```php
public function shouldNotDocument(): bool
```

##### Usage

```php
$methodObject->shouldNotDocument()
```

---

##### Contract

```php
public function isStaticInstantiator(): bool
```

##### Usage

```php
$methodObject->isStaticInstantiator()
```

### 🟢 ClassObject

---

#### How to use the Class:

```php
$classObject = new ClassObject(ReflectionClass $class)
```

<sub><sup>@phpstan-ignore missingType.generics</sup></sub>

---

##### Contract

```php
public function getMethods(): array
```

##### Usage

```php
$classObject->getMethods()
```

<sub><sup>@return MethodObject[]</sup></sub>

---

##### Contract

```php
public function getNamespace(): string
```

##### Usage

```php
$classObject->getNamespace()
```

---

##### Contract

```php
public function isIllegal(): bool
```

##### Usage

```php
$classObject->isIllegal()
```

---

##### Contract

```php
public function isInstantiable(): bool
```

##### Usage

```php
$classObject->isInstantiable()
```

---

##### Contract

```php
public function isException(): bool
```

##### Usage

```php
$classObject->isException()
```

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$classObject->getName()
```

---

##### Contract

```php
public function getPhpDocs(): array
```

##### Usage

```php
$classObject->getPhpDocs()
```

<sub><sup>@return string[]</sup></sub>

---

##### Contract

```php
public function getVirtualMethods(): array
```

##### Usage

```php
$classObject->getVirtualMethods()
```

<sub><sup>@return VirtualMethodObject[]</sup></sub>

### 🟢 TypeObject

---

#### How to use the Class:

```php
$typeObject = new TypeObject(ReflectionType $type)
```

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$typeObject->getName()
```

---

##### Contract

```php
public function getReflectionUnionTypeName(ReflectionUnionType $type): string
```

##### Usage

```php
$typeObject->getReflectionUnionTypeName($type)
```

### 🟢 NamespaceObject

---

#### How to use the Class:

```php
$namespaceObject = new NamespaceObject(string $namespace, array $classes)
```

<sub><sup>@param ClassObject[] $classes</sup></sub>

---

##### Contract

```php
public function getClasses(): array
```

##### Usage

```php
$namespaceObject->getClasses()
```

<sub><sup>@return ClassObject[]</sup></sub>

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$namespaceObject->getName()
```

### 🟥 InvalidVirtualMethodDefinitionException

### 🟢 VirtualMethodObject

---

#### How to use the Class:

```php
$virtualMethodObject = new VirtualMethodObject(ClassObject $classObject, string $line)
```

<sub><sup>@throws InvalidVirtualMethodDefinitionException</sup></sub>

---

##### Contract

```php
public function getClass(): ClassObject
```

##### Usage

```php
$virtualMethodObject->getClass()
```

---

##### Contract

```php
public function getName(): string
```

##### Usage

```php
$virtualMethodObject->getName()
```

---

##### Contract

```php
public function isPrivate(): bool
```

##### Usage

```php
$virtualMethodObject->isPrivate()
```

---

##### Contract

```php
public function isProtected(): bool
```

##### Usage

```php
$virtualMethodObject->isProtected()
```

---

##### Contract

```php
public function isPublic(): bool
```

##### Usage

```php
$virtualMethodObject->isPublic()
```

---

##### Contract

```php
public function isStatic(): bool
```

##### Usage

```php
$virtualMethodObject->isStatic()
```

---

##### Contract

```php
public function isConstructor(): bool
```

##### Usage

```php
$virtualMethodObject->isConstructor()
```

---

##### Contract

```php
public function isMagicMethod(): bool
```

##### Usage

```php
$virtualMethodObject->isMagicMethod()
```

---

##### Contract

```php
public function getMethodReturnTypes(): string
```

##### Usage

```php
$virtualMethodObject->getMethodReturnTypes()
```

---

##### Contract

```php
public function getPhpDocs(): array
```

##### Usage

```php
$virtualMethodObject->getPhpDocs()
```

<sub><sup>@return array&lt;string, mixed&gt;</sup></sub>

---

##### Contract

```php
public function getModifiers(): array
```

##### Usage

```php
$virtualMethodObject->getModifiers()
```

---

##### Contract

```php
public function isFrameworkFunction(): bool
```

##### Usage

```php
$virtualMethodObject->isFrameworkFunction()
```

---

##### Contract

```php
public function getDefaultValues(): string
```

##### Usage

```php
$virtualMethodObject->getDefaultValues()
```

---

##### Contract

```php
public function getDefaultValueTypes(): string
```

##### Usage

```php
$virtualMethodObject->getDefaultValueTypes()
```

---

##### Contract

```php
public function shouldNotDocument(): bool
```

##### Usage

```php
$virtualMethodObject->shouldNotDocument()
```

---

##### Contract

```php
public function isStaticInstantiator(): bool
```

##### Usage

```php
$virtualMethodObject->isStaticInstantiator()
```

## 📦 Sakoo\Framework\Core\Commands

### 🟢 ContainerCacheCommand

---

#### How to use the Class:

```php
$containerCacheCommand = new ContainerCacheCommand(ContainerInterface $container)
```

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
ContainerCacheCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
ContainerCacheCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$containerCacheCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$containerCacheCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$containerCacheCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$containerCacheCommand->getApplication()
```

### 🟢 ZenCommand

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
ZenCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
ZenCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$zenCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$zenCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$zenCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$zenCommand->getApplication()
```

### 🟢 DevCommand

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
DevCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
DevCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$devCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$devCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$devCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$devCommand->getApplication()
```

### 🟢 DocGenCommand

---

#### How to use the Class:

```php
$docGenCommand = new DocGenCommand(string $docPath, string $sidebarPath, string $footerPath)
```

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
DocGenCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
DocGenCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$docGenCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$docGenCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$docGenCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$docGenCommand->getApplication()
```

## 📦 Sakoo\Framework\Core\Commands\Watcher

### 🟢 PhpBundler

---

#### How to use the Class:

```php
$phpBundler = new PhpBundler(Input $input, Output $output)
```

---

##### Contract

```php
public function fileModified(Event $event): void
```

##### Usage

```php
$phpBundler->fileModified($event)
```

---

##### Contract

```php
public function fileMoved(Event $event): void
```

##### Usage

```php
$phpBundler->fileMoved($event)
```

---

##### Contract

```php
public function fileDeleted(Event $event): void
```

##### Usage

```php
$phpBundler->fileDeleted($event)
```

### 🟢 WatchCommand

---

#### How to use the Class:

```php
$watchCommand = new WatchCommand(Watcher $watcher)
```

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
WatchCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
WatchCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$watchCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$watchCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$watchCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$watchCommand->getApplication()
```

## 📦 Sakoo\Framework\Core\Profiler

### 🟢 Profiler

---

#### How to use the Class:

```php
$profiler = new Profiler(ClockInterface $clock)
```

---

##### Contract

```php
public function start(string $key): void
```

##### Usage

```php
$profiler->start($key)
```

---

##### Contract

```php
public function elapsedTime(string $key): int
```

##### Usage

```php
$profiler->elapsedTime($key)
```

## 📦 Sakoo\Framework\Core\Set\Exceptions

### 🟥 GenericMismatchException

## 📦 Sakoo\Framework\Core\Set

### 🟢 Set

---

#### How to use the Class:

```php
$set = new Set(array $items)
```

<sub><sup>@param array&lt;int|string,T&gt; $items</sup></sub>

<sub><sup>@implements \IteratorAggregate&lt;int|string, T&gt;</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function exists(string|int $name): bool
```

##### Usage

```php
$set->exists($name)
```

---

##### Contract

```php
public function count(): int
```

##### Usage

```php
$set->count()
```

---

##### Contract

```php
public function each(callable $callback): void
```

##### Usage

```php
$set->each($callback)
```

---

##### Contract

```php
public function map(callable $callback): self
```

##### Usage

```php
$set->map($callback)
```

<sub><sup>@template U</sup></sub>

<sub><sup>@param callable(T): U $callback</sup></sub>

<sub><sup>@return Set&lt;U&gt;</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function pluck(string $key): self
```

##### Usage

```php
$set->pluck($key)
```

<sub><sup>@return Set&lt;T&gt;</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function add(mixed $key, mixed $value): self
```

##### Usage

```php
$set->add($key, $value)
```

<sub><sup>@return Set&lt;T&gt;</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function remove(string|int $key): self
```

##### Usage

```php
$set->remove($key)
```

<sub><sup>@return Set&lt;T&gt;</sup></sub>

---

##### Contract

```php
public function get(string|int $key, mixed $default): mixed
```

##### Usage

```php
$set->get($key, $default)
```

<sub><sup>@return null|T</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function toArray(): array
```

##### Usage

```php
$set->toArray()
```

<sub><sup>@return array&lt;T&gt;</sup></sub>

---

##### Contract

```php
public function getIterator(): ArrayIterator
```

##### Usage

```php
$set->getIterator()
```

<sub><sup>@return \ArrayIterator&lt;int|string, T&gt;</sup></sub>

---

##### Contract

```php
public function sort(Sorter $sorter): self
```

##### Usage

```php
$set->sort($sorter)
```

<sub><sup>@param Sorter&lt;T&gt; $sorter</sup></sub>

<sub><sup>@return Set&lt;T&gt;</sup></sub>

---

##### Contract

```php
public function search(mixed $needle, Searcher $searcher): self
```

##### Usage

```php
$set->search($needle, $searcher)
```

<sub><sup>@param Searcher&lt;T&gt; $searcher</sup></sub>

<sub><sup>@return Set&lt;T&gt;</sup></sub>

---

##### Contract

```php
public function filter(callable $callback): self
```

##### Usage

```php
$set->filter($callback)
```

<sub><sup>@return Set&lt;T&gt;</sup></sub>

<sub><sup>@throws GenericMismatchException|\Throwable</sup></sub>

---

##### Contract

```php
public function first(): mixed
```

##### Usage

```php
$set->first()
```

---

##### Contract

```php
public function second(): mixed
```

##### Usage

```php
$set->second()
```

---

##### Contract

```php
public function third(): mixed
```

##### Usage

```php
$set->third()
```

---

##### Contract

```php
public function fourth(): mixed
```

##### Usage

```php
$set->fourth()
```

---

##### Contract

```php
public function fifth(): mixed
```

##### Usage

```php
$set->fifth()
```

---

##### Contract

```php
public function sixth(): mixed
```

##### Usage

```php
$set->sixth()
```

---

##### Contract

```php
public function seventh(): mixed
```

##### Usage

```php
$set->seventh()
```

---

##### Contract

```php
public function eighth(): mixed
```

##### Usage

```php
$set->eighth()
```

---

##### Contract

```php
public function ninth(): mixed
```

##### Usage

```php
$set->ninth()
```

---

##### Contract

```php
public function tenth(): mixed
```

##### Usage

```php
$set->tenth()
```

## 📦 Sakoo\Framework\Core\Exception

### 🟢 Exception

## 📦 Sakoo\Framework\Core\Str

### 🟢 Str

---

#### How to use the Class:

```php
$str = new Str(string $value)
```

---

##### Contract

```php
public function length(): int
```

##### Usage

```php
$str->length()
```

---

##### Contract

```php
public function uppercaseWords(): static
```

##### Usage

```php
$str->uppercaseWords()
```

---

##### Contract

```php
public function uppercase(): static
```

##### Usage

```php
$str->uppercase()
```

---

##### Contract

```php
public function lowercase(): static
```

##### Usage

```php
$str->lowercase()
```

---

##### Contract

```php
public function upperFirst(): static
```

##### Usage

```php
$str->upperFirst()
```

---

##### Contract

```php
public function lowerFirst(): static
```

##### Usage

```php
$str->lowerFirst()
```

---

##### Contract

```php
public function reverse(): static
```

##### Usage

```php
$str->reverse()
```

---

##### Contract

```php
public function contains(string $substring): bool
```

##### Usage

```php
$str->contains($substring)
```

---

##### Contract

```php
public function replace(string $search, string $replace): static
```

##### Usage

```php
$str->replace($search, $replace)
```

---

##### Contract

```php
public function trim(): static
```

##### Usage

```php
$str->trim()
```

---

##### Contract

```php
public function slug(): static
```

##### Usage

```php
$str->slug()
```

---

##### Contract

```php
public function camelCase(): static
```

##### Usage

```php
$str->camelCase()
```

---

##### Contract

```php
public function snakeCase(): static
```

##### Usage

```php
$str->snakeCase()
```

---

##### Contract

```php
public function kebabCase(): static
```

##### Usage

```php
$str->kebabCase()
```

---

##### Contract

```php
public function get(): string
```

##### Usage

```php
$str->get()
```

---

##### Contract

```php
public static function fromType(mixed $value): self
```

##### Usage

```php
Str::fromType($value)
```

## 📦 Sakoo\Framework\Core\Kernel

### 🟢 Mode

---

##### Contract

```php
public static function cases(): array
```

##### Usage

```php
Mode::cases()
```

---

#### How to use the Class:

```php
$mode = Mode::from(string|int $value)
```

---

#### How to use the Class:

```php
$mode = Mode::tryFrom(string|int $value)
```

### 🟢 Environment

---

##### Contract

```php
public static function cases(): array
```

##### Usage

```php
Environment::cases()
```

---

#### How to use the Class:

```php
$environment = Environment::from(string|int $value)
```

---

#### How to use the Class:

```php
$environment = Environment::tryFrom(string|int $value)
```

### 🟢 Kernel

---

#### How to use the Class:

```php
$kernel = Kernel::prepare(Mode $mode, Environment $environment)
```

<sub><sup>@throws KernelTwiceCallException</sup></sub>

---

#### How to use the Class:

```php
$kernel = Kernel::getInstance()
```

<sub><sup>@throws KernelIsNotStartedException</sup></sub>

---

##### Contract

```php
public function run(): void
```

##### Usage

```php
$kernel->run()
```

---

##### Contract

```php
public function getMode(): Mode
```

##### Usage

```php
$kernel->getMode()
```

---

##### Contract

```php
public function getEnvironment(): Environment
```

##### Usage

```php
$kernel->getEnvironment()
```

---

##### Contract

```php
public function getProfiler(): ProfilerInterface
```

##### Usage

```php
$kernel->getProfiler()
```

---

##### Contract

```php
public function getContainer(): ContainerInterface
```

##### Usage

```php
$kernel->getContainer()
```

---

##### Contract

```php
public function getReplicaId(): string
```

##### Usage

```php
$kernel->getReplicaId()
```

---

##### Contract

```php
public function setExceptionHandler(callable $handler): static
```

##### Usage

```php
$kernel->setExceptionHandler($handler)
```

---

##### Contract

```php
public function setErrorHandler(callable $handler): static
```

##### Usage

```php
$kernel->setErrorHandler($handler)
```

---

##### Contract

```php
public function setServerTimezone(string $timezone): static
```

##### Usage

```php
$kernel->setServerTimezone($timezone)
```

---

##### Contract

```php
public function setServiceLoaders(array $serviceLoaders): static
```

##### Usage

```php
$kernel->setServiceLoaders($serviceLoaders)
```

<sub><sup>@param array&lt;ServiceLoader&gt; $serviceLoaders</sup></sub>

---

##### Contract

```php
public function setReplicaId(string $replicaId): static
```

##### Usage

```php
$kernel->setReplicaId($replicaId)
```

---

##### Contract

```php
public function isInTestMode(): bool
```

##### Usage

```php
$kernel->isInTestMode()
```

---

##### Contract

```php
public function isInHttpMode(): bool
```

##### Usage

```php
$kernel->isInHttpMode()
```

---

##### Contract

```php
public function isInConsoleMode(): bool
```

##### Usage

```php
$kernel->isInConsoleMode()
```

---

##### Contract

```php
public function isInDebugEnv(): bool
```

##### Usage

```php
$kernel->isInDebugEnv()
```

---

##### Contract

```php
public function isInProductionEnv(): bool
```

##### Usage

```php
$kernel->isInProductionEnv()
```

## 📦 Sakoo\Framework\Core\Kernel\Exceptions

### 🟥 KernelTwiceCallException

### 🟥 KernelIsNotStartedException

## 📦 Sakoo\Framework\Core\Kernel\Handlers

### 🟢 ExceptionHandler

### 🟢 ErrorHandler

## 📦 Sakoo\Framework\Core\Clock\Exceptions

### 🟥 ClockTestModeException

## 📦 Sakoo\Framework\Core\Clock

### 🟢 Clock

---

##### Contract

```php
public static function setTestNow(string $datetime): void
```

##### Usage

```php
Clock::setTestNow($datetime)
```

<sub><sup>@throws ClockTestModeException|\Throwable</sup></sub>

---

##### Contract

```php
public function now(): DateTimeImmutable
```

##### Usage

```php
$clock->now()
```

<sub><sup>@throws \Exception</sup></sub>

## 📦 Sakoo\Framework\Core\ServiceLoader

### 🟢 VarDumpLoader

---

##### Contract

```php
public function load(Container $container): void
```

##### Usage

```php
$varDumpLoader->load($container)
```

### 🟢 MainLoader

---

##### Contract

```php
public function load(Container $container): void
```

##### Usage

```php
$mainLoader->load($container)
```

### 🟢 WatcherLoader

---

##### Contract

```php
public function load(Container $container): void
```

##### Usage

```php
$watcherLoader->load($container)
```

## 📦 Sakoo\Framework\Core\Console

### 🟢 Application

---

#### How to use the Class:

```php
$application = new Application(Input $input, Output $output)
```

---

##### Contract

```php
public function run(): int
```

##### Usage

```php
$application->run()
```

---

##### Contract

```php
public function addCommands(array $commands): void
```

##### Usage

```php
$application->addCommands($commands)
```

<sub><sup>@param Command[] $commands</sup></sub>

---

##### Contract

```php
public function addCommand(Command $command): void
```

##### Usage

```php
$application->addCommand($command)
```

---

##### Contract

```php
public function setDefaultCommand(string $command): void
```

##### Usage

```php
$application->setDefaultCommand($command)
```

<sub><sup>@param class-string&lt;Command&gt; $command</sup></sub>

<sub><sup>@throws \Throwable</sup></sub>

---

##### Contract

```php
public function getCommands(): array
```

##### Usage

```php
$application->getCommands()
```

<sub><sup>@return Command[]</sup></sub>

### 🟢 Output

---

#### How to use the Class:

```php
$output = new Output(bool $forceColors)
```

---

##### Contract

```php
public function newLine(): void
```

##### Usage

```php
$output->newLine()
```

---

##### Contract

```php
public function write(string $message): void
```

##### Usage

```php
$output->write($message)
```

---

##### Contract

```php
public function text(array|string $message, int $foreground, int $background, int $style): void
```

##### Usage

```php
$output->text($message, $foreground, $background, $style)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function block(array|string $message, int $foreground, int $background, int $style): void
```

##### Usage

```php
$output->block($message, $foreground, $background, $style)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function success(array|string $message): void
```

##### Usage

```php
$output->success($message)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function info(array|string $message): void
```

##### Usage

```php
$output->info($message)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function warning(array|string $message): void
```

##### Usage

```php
$output->warning($message)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function error(array|string $message): void
```

##### Usage

```php
$output->error($message)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

---

##### Contract

```php
public function setSilentMode(bool $isSilentMode): void
```

##### Usage

```php
$output->setSilentMode($isSilentMode)
```

---

##### Contract

```php
public function supportsColors(): bool
```

##### Usage

```php
$output->supportsColors()
```

---

##### Contract

```php
public function getBuffer(): array
```

##### Usage

```php
$output->getBuffer()
```

<sub><sup>@return list&lt;string&gt;</sup></sub>

---

##### Contract

```php
public function getDisplay(): string
```

##### Usage

```php
$output->getDisplay()
```

---

##### Contract

```php
public function formatText(array|string $message, int $foreground, int $background, int $style): string
```

##### Usage

```php
$output->formatText($message, $foreground, $background, $style)
```

<sub><sup>@param list&lt;string&gt;|string $message</sup></sub>

### 🟢 Input

---

#### How to use the Class:

```php
$input = new Input(array $args)
```

<sub><sup>@param null|array&lt;string&gt; $args</sup></sub>

---

##### Contract

```php
public function getArguments(): array
```

##### Usage

```php
$input->getArguments()
```

<sub><sup>@return array&lt;string&gt;</sup></sub>

---

##### Contract

```php
public function getArgument(int $position): string
```

##### Usage

```php
$input->getArgument($position)
```

---

##### Contract

```php
public function getOptions(): array
```

##### Usage

```php
$input->getOptions()
```

<sub><sup>@return array&lt;string&gt;</sup></sub>

---

##### Contract

```php
public function hasOption(string $name): bool
```

##### Usage

```php
$input->hasOption($name)
```

---

##### Contract

```php
public function getOption(string $name): string
```

##### Usage

```php
$input->getOption($name)
```

---

##### Contract

```php
public function getUserInput(): string
```

##### Usage

```php
$input->getUserInput()
```

---

##### Contract

```php
public function radio(array $options, string $title): string
```

##### Usage

```php
$input->radio($options, $title)
```

<sub><sup>@param string[] $options</sup></sub>

## 📦 Sakoo\Framework\Core\Console\Exceptions

### 🟥 CommandNotFoundException

## 📦 Sakoo\Framework\Core\Console\Components

### 🟢 RadioButton

---

#### How to use the Class:

```php
$radioButton = new RadioButton(string $prompt, array $options)
```

<sub><sup>@param string[] $options</sup></sub>

---

##### Contract

```php
public function show(): string
```

##### Usage

```php
$radioButton->show()
```

## 📦 Sakoo\Framework\Core\Console\Commands

### 🟢 HelpCommand

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
HelpCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
HelpCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$helpCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$helpCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$helpCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$helpCommand->getApplication()
```

### 🟢 VersionCommand

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
VersionCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
VersionCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$versionCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$versionCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$versionCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$versionCommand->getApplication()
```

### 🟢 NotFoundCommand

---

##### Contract

```php
public static function getName(): string
```

##### Usage

```php
NotFoundCommand::getName()
```

---

##### Contract

```php
public static function getDescription(): string
```

##### Usage

```php
NotFoundCommand::getDescription()
```

---

##### Contract

```php
public function run(Input $input, Output $output): int
```

##### Usage

```php
$notFoundCommand->run($input, $output)
```

---

##### Contract

```php
public function help(Input $input, Output $output): int
```

##### Usage

```php
$notFoundCommand->help($input, $output)
```

---

##### Contract

```php
public function setRunningApplication(Application $app): void
```

##### Usage

```php
$notFoundCommand->setRunningApplication($app)
```

---

##### Contract

```php
public function getApplication(): Application
```

##### Usage

```php
$notFoundCommand->getApplication()
```

