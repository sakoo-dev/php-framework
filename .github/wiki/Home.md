# 📚 Documentation

## 📦 Sakoo\Framework\Core\Logger

### 🟢 FileLogger

<sub><sup>PSR-3 logger implementation that persists log entries to daily rotating files. </sup></sub>



<sub><sup>Extends Psr\Log\AbstractLogger so all eight convenience methods (debug, info, warning, etc.) are available out of the box — only log() needs to be implemented. </sup></sub>



<sub><sup>Each log entry is formatted by LogFormatter and appended to a file whose path follows the pattern {logs_dir}/{Y/m/d}.log, rotating automatically at midnight. The current environment (Debug/Production) and mode (Test/Console/HTTP) are embedded in every entry for contextual filtering. </sup></sub>



<sub><sup>If the filesystem write fails, an Exception is thrown rather than silently swallowing the failure, ensuring observability issues surface immediately. </sup></sub>



### - `log` Function

<sub><sup>Formats the log entry and appends it to today&#039;s rotating log file. </sup></sub>



<sub><sup>@param string $level PSR-3 log level string (e.g. &#039;debug&#039;, &#039;error&#039;) </sup></sub>



> @throws \Exception|\Throwable when the log file cannot be written

```php
// --- Contract
public function log( $level, Stringable|string $message, array $context): void
// --- Usage
$fileLogger->log($level, $message, $context);
```

### 🟢 LogFormatter

<sub><sup>Formats a single PSR-3 log entry into a structured, human-readable string. </sup></sub>



<sub><sup>The rendered format is: [{ISO-8601 datetime}] [{LEVEL}] [{Mode} {Environment}] - {message} </sup></sub>



<sub><sup>For example: [2024-06-01T12:00:00+00:00] [ERROR] [HTTP Production] - Payment gateway timeout </sup></sub>



<sub><sup>Implemented as an immutable readonly class: all state is captured at construction time and the formatted string is produced lazily via __toString(). This makes instances safe to pass around and cast to string at any point without side effects. </sup></sub>



<sub><sup>The current instant is obtained from Clock so the formatter remains compatible with time-pinning in test mode. </sup></sub>



#### How to use the Class:

```php
$logFormatter = new LogFormatter(string $level, Stringable|string $message, string $mode, string $env);
```

## 📦 Sakoo\Framework\Core\VarDump\Cli

### 🟢 CliFormatter

<sub><sup>ANSI-coloured CLI formatter for debug-dumping PHP values. </sup></sub>



<sub><sup>Produces a recursive, indented, type-annotated representation of any PHP value and writes it to the console Output as a block. Type colouring follows these conventions: </sup></sub>



<sub><sup>- Strings   → yellow, wrapped in double quotes - Integers / floats → red - Booleans  → green (&quot;true&quot; or &quot;false&quot;) - Null      → red (&quot;null&quot;) - Arrays    → cyan label with element count, each key in green, values recursive - Objects   → magenta class name, each property with +/- visibility prefix, values recursive </sup></sub>



<sub><sup>Recursion depth is tracked via $depth so nested arrays and objects receive proportionally increased indentation. </sup></sub>



#### How to use the Class:

```php
$cliFormatter = new CliFormatter(Output $output);
```

### - `format` Function

<sub><sup>Formats $value and writes the result as a console block. </sup></sub>



```php
// --- Contract
public function format(mixed $value): void
// --- Usage
$cliFormatter->format($value);
```

### - `formatType` Function

<sub><sup>Dispatches $value to the appropriate type-specific formatter and returns the coloured string representation. $depth controls the current indentation level for recursive calls on arrays and objects. </sup></sub>



```php
// --- Contract
protected function formatType(mixed $value, int $depth): string
// --- Usage
$cliFormatter->formatType($value, $depth);
```

### 🟢 CliDumper

<sub><sup>CLI implementation of the Dumper contract. </sup></sub>



<sub><sup>Delegates all rendering to the injected Formatter (typically CliFormatter), keeping the dumper itself free of any output or formatting logic. Registered in the container for console/test mode so that dump() and dd() produce ANSI-coloured output on the terminal instead of HTML markup. </sup></sub>



#### How to use the Class:

```php
$cliDumper = new CliDumper(Formatter $formatter);
```

### - `dump` Function

<sub><sup>Passes $value to the formatter, which renders it to the terminal output channel. </sup></sub>



```php
// --- Contract
public function dump(mixed $value): void
// --- Usage
$cliDumper->dump($value);
```

## 📦 Sakoo\Framework\Core\VarDump

### 🟢 VarDump

<sub><sup>Global entry point for debug-dumping values at runtime. </sup></sub>



<sub><sup>Resolves the active Dumper implementation from the container and delegates to it, keeping dump logic decoupled from output channel specifics. This means the rendering strategy (HTML, CLI, log) can be swapped by rebinding Dumper in the container without touching any call site. </sup></sub>



<sub><sup>Two static methods are provided: - dump()     — renders one or more values and returns normally. - dieDump()  — renders one or more values then terminates the process immediately. </sup></sub>



<sub><sup>These are exposed as global helper functions dump() and dd() in helpers.php for ergonomic use throughout the codebase. </sup></sub>



### - `dieDump` Function

<sub><sup>Renders each value in $vars through the active Dumper and then terminates the process. Equivalent to calling dump() followed by exit. </sup></sub>



```php
// --- Contract
public static function dieDump(mixed $vars): never
// --- Usage
VarDump::dieDump($vars);
```

### - `dump` Function

<sub><sup>Resolves the active Dumper from the container and passes each value in $vars to its dump() method in order. </sup></sub>



```php
// --- Contract
public static function dump(mixed $vars): void
// --- Usage
VarDump::dump($vars);
```

## 📦 Sakoo\Framework\Core\VarDump\Http

### 🟢 HttpDumper

<sub><sup>HTTP implementation of the Dumper contract. </sup></sub>



<sub><sup>Delegates all rendering to the injected Formatter (typically HttpFormatter), keeping the dumper itself free of any output or formatting logic. Registered in the container for HTTP mode so that dump() and dd() write debug output directly into the HTTP response body as HTML. </sup></sub>



#### How to use the Class:

```php
$httpDumper = new HttpDumper(Formatter $formatter);
```

### - `dump` Function

<sub><sup>Passes $value to the formatter, which renders it into the HTTP response output channel. </sup></sub>



```php
// --- Contract
public function dump(mixed $value): void
// --- Usage
$httpDumper->dump($value);
```

### 🟢 HttpFormatter

<sub><sup>HTTP formatter for debug-dumping PHP values into an HTML response. </sup></sub>



<sub><sup>Implements the Formatter contract for the HTTP mode. Rendering is intentionally deferred to a future implementation — the current body is a stub that accepts any value and produces no output, acting as a safe no-op until an HTML rendering strategy is wired in. </sup></sub>



#### How to use the Class:

```php
$httpFormatter = new HttpFormatter();
```

### - `format` Function

<sub><sup>Renders $value into the HTTP response output channel. Currently a no-op stub pending a concrete HTML rendering implementation. </sup></sub>



```php
// --- Contract
public function format(mixed $value): void
// --- Usage
$httpFormatter->format($value);
```

## 📦 Sakoo\Framework\Core\Watcher

### 🟢 Watcher

<sub><sup>High-level filesystem watcher that coordinates a driver with a callback action. </sup></sub>



<sub><sup>Accepts a set of files to monitor and a FileSystemAction callback, registers each file with the underlying WatcherDriver, then enters an event loop that waits for kernel-level filesystem notifications and dispatches them to the appropriate callback method based on the event type. </sup></sub>



<sub><sup>Three event types are handled: - MODIFY — file content was changed; calls FileSystemAction::fileModified(). - MOVE   — file was renamed or moved; calls FileSystemAction::fileMoved(). - DELETE — file was removed; calls FileSystemAction::fileDeleted() and removes the watch descriptor from the driver via blind(). </sup></sub>



<sub><sup>run() blocks indefinitely in a while(true) loop. Use check() directly in tests or custom loop implementations to process a single batch of events at a time. </sup></sub>



#### How to use the Class:

```php
$watcher = new Watcher(WatcherDriver $driver);
```

### - `watch` Function

<sub><sup>Registers each file in $files with the driver, associating it with $callback so the correct action is invoked when the file changes. Returns the same Watcher instance for fluent chaining. </sup></sub>



<sub><sup>@param \SplFileObject[] $files </sup></sub>



```php
// --- Contract
public function watch(array $files, FileSystemAction $callback): self
// --- Usage
$watcher->watch($files, $callback);
```

### - `run` Function

<sub><sup>Enters an infinite event loop, repeatedly calling check() to wait for and dispatch filesystem events. This method never returns under normal operation. </sup></sub>



```php
// --- Contract
public function run(): void
// --- Usage
$watcher->run();
```

### - `check` Function

<sub><sup>Waits for the next batch of filesystem events from the driver and dispatches each one to the appropriate FileSystemAction method via eventCall(). </sup></sub>



```php
// --- Contract
public function check(): void
// --- Usage
$watcher->check();
```

### 🟢 EventTypes

<sub><sup>Enumerates the filesystem event types the Watcher subsystem can handle. </sup></sub>



<sub><sup>- MODIFY — the file&#039;s content was written or truncated. - MOVE   — the file was renamed or moved to a different path. - DELETE — the file was unlinked from the filesystem. </sup></sub>



### - `cases` Function

```php
// --- Contract
public static function cases(): array
// --- Usage
EventTypes::cases();
```

## 📦 Sakoo\Framework\Core\Watcher\Inotify

### 🟢 Event

<sub><sup>Inotify-backed value object representing a single filesystem event. </sup></sub>



<sub><sup>Wraps the raw associative array returned by inotify_read() and enriches it with the File value object that was registered for the triggering watch descriptor. The inotify event mask is decoded into an EventTypes case by testing against the IN_MODIFY, IN_MOVE_SELF, and IN_DELETE_SELF bitmask constants. When none of the expected bits are set, MODIFY is returned as a safe default. </sup></sub>



<sub><sup>The cookie field exposed by getGroupId() is the inotify rename cookie that links paired IN_MOVED_FROM / IN_MOVED_TO events for atomic renames; it is zero for all other event types. </sup></sub>



#### How to use the Class:

```php
$event = new Event(File $file, array $event);
```

### - `getFile` Function

<sub><sup>Returns the File value object for the watched path that triggered this event. </sup></sub>



```php
// --- Contract
public function getFile(): File
// --- Usage
$event->getFile();
```

### - `getHandlerId` Function

<sub><sup>Returns the inotify watch descriptor integer identifying the registered watch. Passed to WatcherDriver::blind() when a DELETE event is processed. </sup></sub>



```php
// --- Contract
public function getHandlerId(): int
// --- Usage
$event->getHandlerId();
```

### - `getType` Function

<sub><sup>Decodes the inotify bitmask into an EventTypes case. </sup></sub>



<sub><sup>Tests IN_MODIFY first, then IN_MOVE_SELF, then IN_DELETE_SELF. Falls back to EventTypes::MODIFY when no recognised bit is set. </sup></sub>



```php
// --- Contract
public function getType(): EventTypes
// --- Usage
$event->getType();
```

### - `getGroupId` Function

<sub><sup>Returns the inotify rename cookie that correlates paired MOVED_FROM MOVED_TO events for atomic renames. Zero for all other event types. </sup></sub>



```php
// --- Contract
public function getGroupId(): int
// --- Usage
$event->getGroupId();
```

### - `getName` Function

<sub><sup>Returns the absolute path of the file that triggered this event. </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$event->getName();
```

### 🟢 File

<sub><sup>Inotify-backed value object representing a file registered for watching. </sup></sub>



<sub><sup>Holds the inotify watch descriptor ID assigned by inotify_add_watch(), the absolute path being monitored, the FileSystemAction callback to invoke on events, and a per-file Locker used by WatcherActions to debounce rapid consecutive MODIFY events so the action is not triggered multiple times for a single logical file save. </sup></sub>



<sub><sup>Instances are created by Inotify::watch() and stored in the handler registry keyed by watch descriptor so they can be retrieved when events arrive. </sup></sub>



#### How to use the Class:

```php
$file = new File(int $id, string $path, FileSystemAction $callback, Locker $locker);
```

### - `getId` Function

<sub><sup>Returns the inotify watch descriptor integer assigned when this file was registered with inotify_add_watch(). </sup></sub>



```php
// --- Contract
public function getId(): int
// --- Usage
$file->getId();
```

### - `getCallback` Function

<sub><sup>Returns the FileSystemAction callback to invoke when a filesystem event is received for this file. </sup></sub>



```php
// --- Contract
public function getCallback(): FileSystemAction
// --- Usage
$file->getCallback();
```

### - `getPath` Function

<sub><sup>Returns the absolute filesystem path this instance was registered to watch. </sup></sub>



```php
// --- Contract
public function getPath(): string
// --- Usage
$file->getPath();
```

### - `getLocker` Function

<sub><sup>Returns the Locker instance used to prevent re-entrant handling of consecutive MODIFY events on this specific file. </sup></sub>



```php
// --- Contract
public function getLocker(): Locker
// --- Usage
$file->getLocker();
```

### 🟢 Inotify

<sub><sup>Linux inotify-backed implementation of WatcherDriver. </sup></sub>



<sub><sup>Uses the inotify PHP extension to subscribe to kernel-level filesystem notifications. Three inotify event masks are combined for each watched path: IN_MODIFY (content change), IN_MOVE_SELF (the file itself was renamed/moved), and IN_DELETE_SELF (the file itself was deleted). </sup></sub>



<sub><sup>Registered watch descriptors and their associated File value objects are kept in an internal Set keyed by the string representation of the watch descriptor integer, so Event objects returned by wait() can be enriched with the correct File reference. </sup></sub>



<sub><sup>Each file is given its own Locker instance (resolved from the container) so WatcherActions can debounce rapid MODIFY events per file independently. </sup></sub>



#### How to use the Class:

```php
$inotify = new Inotify();
```

### - `watch` Function

<sub><sup>Adds a watch for $file with IN_MODIFY | IN_MOVE_SELF | IN_DELETE_SELF masks, creates a File value object with the returned watch descriptor and a fresh Locker, and stores both in the handler registry. </sup></sub>



```php
// --- Contract
public function watch(string $file, FileSystemAction $callback): void
// --- Usage
$inotify->watch($file, $callback);
```

### - `wait` Function

<sub><sup>Blocks on inotify_read() until at least one event is delivered, then constructs an Event value object for each raw inotify event and returns them as a Set. Returns an empty Set when inotify_read() yields no events. </sup></sub>



<sub><sup>@return IterableInterface&lt;EventInterface&gt; </sup></sub>



```php
// --- Contract
public function wait(): IterableInterface
// --- Usage
$inotify->wait();
```

### - `blind` Function

<sub><sup>Removes the watch descriptor $id from inotify so no further events are delivered for the associated path. Returns true on success. </sup></sub>



```php
// --- Contract
public function blind(int $id): bool
// --- Usage
$inotify->blind($id);
```

## 📦 Sakoo\Framework\Core\Path

### 🟢 Path

<sub><sup>Centralised registry of well-known filesystem paths within a Sakoo project. </sup></sub>



<sub><sup>All path resolution is static so the class acts as a pure namespace rather than a service, avoiding the need to inject it throughout the codebase. Paths are derived at runtime from the current working directory (getRootDir) or from the location of this file itself (getCoreDir), ensuring they are correct regardless of where the process was started from. </sup></sub>



<sub><sup>Utility methods convert between PSR-4 namespace strings and filesystem paths so the framework can locate, load, and introspect its own source files without maintaining a separate manifest. </sup></sub>



### - `getRootDir` Function

<sub><sup>Returns the project root directory, which is the current working directory of the running PHP process. Returns false when getcwd() fails. </sup></sub>



```php
// --- Contract
public static function getRootDir(): string|false
// --- Usage
Path::getRootDir();
```

### - `getCoreDir` Function

<sub><sup>Returns the absolute path to the framework core source directory (one level above this file). Returns false when realpath() cannot resolve the path. </sup></sub>



```php
// --- Contract
public static function getCoreDir(): string|false
// --- Usage
Path::getCoreDir();
```

### - `getVendorDir` Function

<sub><sup>Returns the absolute path to the project&#039;s vendor directory. </sup></sub>



```php
// --- Contract
public static function getVendorDir(): string
// --- Usage
Path::getVendorDir();
```

### - `getStorageDir` Function

<sub><sup>Returns the absolute path to the project&#039;s storage directory. </sup></sub>



```php
// --- Contract
public static function getStorageDir(): string
// --- Usage
Path::getStorageDir();
```

### - `getLogsDir` Function

<sub><sup>Returns the directory where log files are written. </sup></sub>



<sub><sup>In test mode the temporary test directory is used to avoid polluting the real storage tree. In all other modes, logs are written under storage/logs. </sup></sub>



```php
// --- Contract
public static function getLogsDir(): string
// --- Usage
Path::getLogsDir();
```

### - `getTempTestDir` Function

<sub><sup>Returns the temporary directory used exclusively during test runs. Isolating test artefacts here prevents leftover files from affecting production storage between runs. </sup></sub>



```php
// --- Contract
public static function getTempTestDir(): string
// --- Usage
Path::getTempTestDir();
```

### - `getProjectPHPFiles` Function

<sub><sup>Returns all PHP files found recursively under the project root directory, excluding VCS directories, VCS-ignored paths, and dot-files. </sup></sub>



<sub><sup>@return SplFileObject[] </sup></sub>



```php
// --- Contract
public static function getProjectPHPFiles(): array
// --- Usage
Path::getProjectPHPFiles();
```

### - `getCorePHPFiles` Function

<sub><sup>Returns all PHP files found recursively under the framework core directory, excluding VCS directories, VCS-ignored paths, and dot-files. </sup></sub>



<sub><sup>@return SplFileObject[] </sup></sub>



```php
// --- Contract
public static function getCorePHPFiles(): array
// --- Usage
Path::getCorePHPFiles();
```

### - `getPHPFilesOf` Function

<sub><sup>Returns all PHP files found recursively under $path using FileFinder, with VCS directories, VCS-ignored paths, and dot-files excluded. </sup></sub>



<sub><sup>@return SplFileObject[] </sup></sub>



```php
// --- Contract
public static function getPHPFilesOf(string $path): array
// --- Usage
Path::getPHPFilesOf($path);
```

### - `namespaceToPath` Function

<sub><sup>Converts a fully-qualified framework namespace string to a relative file path. </sup></sub>



<sub><sup>Replaces the &#039;Sakoo\Framework\Core&#039; prefix with &#039;src&#039; and converts namespace separators to directory separators, appending &#039;.php&#039;. Used for locating source files from reflection data without hitting the filesystem. </sup></sub>



```php
// --- Contract
public static function namespaceToPath(string $namespace): string
// --- Usage
Path::namespaceToPath($namespace);
```

### - `pathToNamespace` Function

<sub><sup>Converts a relative file path back to a fully-qualified framework class name. </sup></sub>



<sub><sup>Strips the &#039;.php&#039; extension, replaces the &#039;src&#039; prefix with &#039;Sakoo\Framework\Core&#039;, and converts directory separators to namespace separators. </sup></sub>



<sub><sup>@return class-string </sup></sub>



```php
// --- Contract
public static function pathToNamespace(string $path): string
// --- Usage
Path::pathToNamespace($path);
```

## 📦 Sakoo\Framework\Core\FileSystem\Storages\Local

### 🟢 Local

<sub><sup>Local-disk implementation of the Storage interface. </sup></sub>



<sub><sup>Fulfils the full Storage contract using standard PHP filesystem functions (file_exists, mkdir, touch, rename, chmod, fopen, etc.) against the host operating system&#039;s local filesystem. All operations target the single path provided to the constructor. </sup></sub>



<sub><sup>Recursive directory handling is delegated to the CanBeDirectory trait (deep remove and copy), and exclusive-lock writes are provided by the CanBeWritable trait. </sup></sub>



<sub><sup>Assertion preconditions (via Assert) guard write() and append() against being called on directory paths, and files() against being called on plain files. These assertions throw InvalidArgumentException on violation. </sup></sub>



#### How to use the Class:

```php
$local = new Local(string $path);
```

### - `create` Function

<sub><sup>Creates the file (or directory when $asDirectory is true) at the configured path. </sup></sub>



<sub><sup>Ensures the parent directory exists first. Returns false when the node already exists, true on success. </sup></sub>



```php
// --- Contract
public function create(bool $asDirectory): bool
// --- Usage
$local->create($asDirectory);
```

### - `mkdir` Function

<sub><sup>Creates the parent directory of the configured path. </sup></sub>



<sub><sup>When $recursive is true (the default), all missing intermediate directories are created. Returns true immediately if the parent directory already exists. </sup></sub>



```php
// --- Contract
public function mkdir(bool $recursive): bool
// --- Usage
$local->mkdir($recursive);
```

### - `exists` Function

<sub><sup>Returns true when a file or directory exists at the configured path. </sup></sub>



```php
// --- Contract
public function exists(): bool
// --- Usage
$local->exists();
```

### - `remove` Function

<sub><sup>Deletes the node at the configured path. For directories, all children are removed recursively before the directory itself is deleted. </sup></sub>



```php
// --- Contract
public function remove(): bool
// --- Usage
$local->remove();
```

### - `isDir` Function

<sub><sup>Returns true when the configured path points to a directory. </sup></sub>



```php
// --- Contract
public function isDir(): bool
// --- Usage
$local->isDir();
```

### - `move` Function

<sub><sup>Moves the node to $to, creating intermediate parent directories as needed. </sup></sub>



```php
// --- Contract
public function move(string $to): bool
// --- Usage
$local->move($to);
```

### - `copy` Function

<sub><sup>Copies the node to $to. For directories, the entire subtree is copied recursively. Throws when the source does not exist. </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
public function copy(string $to): bool
// --- Usage
$local->copy($to);
```

### - `parentDir` Function

<sub><sup>Returns the absolute path to the parent directory of the configured path. </sup></sub>



```php
// --- Contract
public function parentDir(): string
// --- Usage
$local->parentDir();
```

### - `rename` Function

<sub><sup>Renames (moves) the node to $to using PHP&#039;s rename(), which works atomically on most local filesystems when source and destination are on the same device. </sup></sub>



```php
// --- Contract
public function rename(string $to): bool
// --- Usage
$local->rename($to);
```

### - `files` Function

<sub><sup>Returns the real paths of all files found recursively under the configured directory path. Throws when the path is not a directory. </sup></sub>



<sub><sup>@return string[] </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
public function files(): array
// --- Usage
$local->files();
```

### - `write` Function

<sub><sup>Overwrites the file with $data using an exclusive lock. Throws when the path is a directory. </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
public function write(string $data): bool
// --- Usage
$local->write($data);
```

### - `append` Function

<sub><sup>Appends $data to the file using an exclusive lock. Throws when the path is a directory. </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
public function append(string $data): bool
// --- Usage
$local->append($data);
```

### - `readLines` Function

<sub><sup>Reads the file into an ordered array of lines. Throws when the path does not exist or is a directory. </sup></sub>



<sub><sup>@return false|string[] </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
public function readLines(): array|false
// --- Usage
$local->readLines();
```

### - `setPermission` Function

<sub><sup>Sets the permission bits on the node. String permissions are converted from octal notation to an integer before calling chmod(). </sup></sub>



```php
// --- Contract
public function setPermission(string|int $permission): bool
// --- Usage
$local->setPermission($permission);
```

### - `getPermission` Function

<sub><sup>Returns the last four characters of the octal permission string for the node (e.g. &quot;0644&quot;), as reported by fileperms(). </sup></sub>



```php
// --- Contract
public function getPermission(): mixed
// --- Usage
$local->getPermission();
```

### - `getPath` Function

<sub><sup>Returns the absolute path this instance was opened with. </sup></sub>



```php
// --- Contract
public function getPath(): string
// --- Usage
$local->getPath();
```

## 📦 Sakoo\Framework\Core\FileSystem

### 🟢 File

<sub><sup>Static factory for opening Storage instances. </sup></sub>



<sub><sup>Decouples call sites from concrete Storage implementations by accepting a Disk enum case that carries the fully-qualified class name of the desired driver. open() instantiates the appropriate driver with the given path and returns it as a Storage contract, so consumers never depend on a concrete class directly. </sup></sub>



<sub><sup>The private constructor prevents instantiation — this class is intentionally a pure static factory with no instance state. </sup></sub>



### - `open` Function

<sub><sup>Opens a file at $path using the storage driver identified by $storage. </sup></sub>



<sub><sup>The Disk enum value is used as the class name to instantiate, allowing new storage backends to be added by defining a new Disk case without modifying this factory. </sup></sub>



```php
// --- Contract
public static function open(Disk $storage, string $path): Storage
// --- Usage
File::open($storage, $path);
```

### 🟢 Disk

<sub><sup>Enumerates the available filesystem storage backends. </sup></sub>



<sub><sup>Each case carries the fully-qualified class name of the corresponding Storage implementation as its string value. File::open() uses this value directly to instantiate the driver, so adding a new backend requires only a new enum case — no changes to the factory are necessary. </sup></sub>



<sub><sup>Currently available drivers: - Local — reads and writes files on the local host filesystem via standard PHP file functions. </sup></sub>



### - `cases` Function

```php
// --- Contract
public static function cases(): array
// --- Usage
Disk::cases();
```

#### How to use the Class:

```php
$disk = Disk::from(string|int $value);
```

#### How to use the Class:

```php
$disk = Disk::tryFrom(string|int $value);
```

### 🟢 Permission

<sub><sup>Named constants and factory methods for Unix-style octal file permission strings. </sup></sub>



<sub><sup>The eight integer constants map directly to the standard Unix permission bits: read (4), write (2), execute (1), and their combinations. The static factory methods compose these bits into a four-character octal string of the form &quot;0UGO&quot; (leading zero, then user/group/other digits) accepted by chmod(). </sup></sub>



<sub><sup>The &quot;all*&quot; helpers set the same permission for user, group, and others in one call. The &quot;get*&quot; group methods return arrays of all octal strings that satisfy a specific predicate (e.g. all readable combinations) — useful for asserting permissions in tests or validating filesystem state. </sup></sub>



<sub><sup>getFileDefault() and getDirectoryDefault() return the conventional permission strings used when creating new files and directories respectively. </sup></sub>



### - `allNothing` Function

<sub><sup>Returns the octal string &quot;0000&quot; — no permissions for user, group, or others. </sup></sub>



```php
// --- Contract
public static function allNothing(): string
// --- Usage
Permission::allNothing();
```

### - `allExecute` Function

<sub><sup>Returns the octal string &quot;0111&quot; — execute-only for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allExecute(): string
// --- Usage
Permission::allExecute();
```

### - `allWrite` Function

<sub><sup>Returns the octal string &quot;0222&quot; — write-only for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allWrite(): string
// --- Usage
Permission::allWrite();
```

### - `allExecuteWrite` Function

<sub><sup>Returns the octal string &quot;0333&quot; — write and execute for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allExecuteWrite(): string
// --- Usage
Permission::allExecuteWrite();
```

### - `allRead` Function

<sub><sup>Returns the octal string &quot;0444&quot; — read-only for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allRead(): string
// --- Usage
Permission::allRead();
```

### - `allExecuteRead` Function

<sub><sup>Returns the octal string &quot;0555&quot; — read and execute for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allExecuteRead(): string
// --- Usage
Permission::allExecuteRead();
```

### - `allWriteRead` Function

<sub><sup>Returns the octal string &quot;0666&quot; — read and write for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allWriteRead(): string
// --- Usage
Permission::allWriteRead();
```

### - `allExecuteWriteRead` Function

<sub><sup>Returns the octal string &quot;0777&quot; — full permissions for user, group, and others. </sup></sub>



```php
// --- Contract
public static function allExecuteWriteRead(): string
// --- Usage
Permission::allExecuteWriteRead();
```

### - `getExecutables` Function

<sub><sup>Returns the four permission strings that include the execute bit for all principals. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getExecutables(): array
// --- Usage
Permission::getExecutables();
```

### - `getNotExecutables` Function

<sub><sup>Returns the four permission strings that do not include the execute bit. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getNotExecutables(): array
// --- Usage
Permission::getNotExecutables();
```

### - `getWritables` Function

<sub><sup>Returns the four permission strings that include the write bit for all principals. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getWritables(): array
// --- Usage
Permission::getWritables();
```

### - `getNotWritables` Function

<sub><sup>Returns the four permission strings that do not include the write bit. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getNotWritables(): array
// --- Usage
Permission::getNotWritables();
```

### - `getReadables` Function

<sub><sup>Returns the four permission strings that include the read bit for all principals. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getReadables(): array
// --- Usage
Permission::getReadables();
```

### - `getNotReadables` Function

<sub><sup>Returns the four permission strings that do not include the read bit. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public static function getNotReadables(): array
// --- Usage
Permission::getNotReadables();
```

### - `make` Function

<sub><sup>Composes $user, $group, and $others permission bits into a four-character octal string prefixed with a leading zero (e.g. &quot;0755&quot;), ready for use with chmod(). </sup></sub>



```php
// --- Contract
public static function make(int $user, int $group, int $others): string
// --- Usage
Permission::make($user, $group, $others);
```

### - `getFileDefault` Function

<sub><sup>Returns the default permission string for newly created files: &quot;0644&quot; (owner read/write, group and others read-only). </sup></sub>



```php
// --- Contract
public static function getFileDefault(): string
// --- Usage
Permission::getFileDefault();
```

### - `getDirectoryDefault` Function

<sub><sup>Returns the default permission string for newly created directories: &quot;0755&quot; (owner full access, group and others read/execute). </sup></sub>



```php
// --- Contract
public static function getDirectoryDefault(): string
// --- Usage
Permission::getDirectoryDefault();
```

## 📦 Sakoo\Framework\Core\Assert

### 🟢 Assert

<sub><sup>Static assertion library for validating values at runtime. </sup></sub>



<sub><sup>Aggregates all type-specific assertion traits (boolean, callable, file, general, null, number, object, resource, scalar, string, traversable) into a single entry point. Every static method validates one condition and throws InvalidArgumentException immediately when the assertion fails — making Assert suitable for guard clauses at the top of methods and constructors. </sup></sub>



<sub><sup>For scenarios where multiple assertions must all be evaluated before any failure is reported, use Assert::lazy() to obtain a LazyAssertion instance that collects all failures and throws a single LazyAssertionException at the end. </sup></sub>



<sub><sup>For fluent, chainable single-value assertions, use Assert::that($value) to obtain an AssertionChain that proxies every Assert method with the bound value already supplied as the first argument. </sup></sub>



<sub><sup>The protected throwIf() and throwUnless() helpers are the sole throw sites used by all traits, keeping error-throwing logic in one place. </sup></sub>



### - `that` Function

<sub><sup>Returns an AssertionChain bound to $value, enabling fluent chained assertions without repeating the value on every call. </sup></sub>



```php
// --- Contract
public static function that(mixed $value): AssertionChain
// --- Usage
Assert::that($value);
```

### - `lazy` Function

<sub><sup>Returns a LazyAssertion instance that accumulates all assertion failures and reports them together via LazyAssertionException when validate() is called. </sup></sub>



```php
// --- Contract
public static function lazy(): LazyAssertion
// --- Usage
Assert::lazy();
```

### - `throwIf` Function

<sub><sup>Throws InvalidArgumentException when $condition is true. Used internally by all assertion traits as the single throw site for &quot;must not be&quot; style assertions. </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
protected static function throwIf(bool $condition, string $message): void
// --- Usage
Assert::throwIf($condition, $message);
```

### - `throwUnless` Function

<sub><sup>Throws InvalidArgumentException when $condition is false. Used internally by all assertion traits as the single throw site for &quot;must be&quot; style assertions. </sup></sub>



> @throws InvalidArgumentException

```php
// --- Contract
protected static function throwUnless(bool $condition, string $message): void
// --- Usage
Assert::throwUnless($condition, $message);
```

### - `true` Function

<sub><sup>Asserts that $value is strictly identical to true. </sup></sub>



```php
// --- Contract
public static function true(mixed $value, string $message): void
// --- Usage
Assert::true($value, $message);
```

### - `false` Function

<sub><sup>Asserts that $value is strictly identical to false. </sup></sub>



```php
// --- Contract
public static function false(mixed $value, string $message): void
// --- Usage
Assert::false($value, $message);
```

### - `bool` Function

<sub><sup>Asserts that $value is of type bool (true or false). </sup></sub>



```php
// --- Contract
public static function bool(mixed $value, string $message): void
// --- Usage
Assert::bool($value, $message);
```

### - `notBool` Function

<sub><sup>Asserts that $value is NOT of type bool. </sup></sub>



```php
// --- Contract
public static function notBool(mixed $value, string $message): void
// --- Usage
Assert::notBool($value, $message);
```

### - `callable` Function

<sub><sup>Asserts that $value is callable (a closure, invokable object, or valid callback). </sup></sub>



```php
// --- Contract
public static function callable(mixed $value, string $message): void
// --- Usage
Assert::callable($value, $message);
```

### - `notCallable` Function

<sub><sup>Asserts that $value is NOT callable. </sup></sub>



```php
// --- Contract
public static function notCallable(mixed $value, string $message): void
// --- Usage
Assert::notCallable($value, $message);
```

### - `dir` Function

<sub><sup>Asserts that $value is an existing directory path. </sup></sub>



```php
// --- Contract
public static function dir(string $value, string $message): void
// --- Usage
Assert::dir($value, $message);
```

### - `notDir` Function

<sub><sup>Asserts that $value is NOT a directory path. </sup></sub>



```php
// --- Contract
public static function notDir(string $value, string $message): void
// --- Usage
Assert::notDir($value, $message);
```

### - `file` Function

<sub><sup>Asserts that $value is an existing regular file. </sup></sub>



```php
// --- Contract
public static function file(string $value, string $message): void
// --- Usage
Assert::file($value, $message);
```

### - `notFile` Function

<sub><sup>Asserts that $value is NOT a regular file. </sup></sub>



```php
// --- Contract
public static function notFile(string $value, string $message): void
// --- Usage
Assert::notFile($value, $message);
```

### - `link` Function

<sub><sup>Asserts that $value is a symbolic link. </sup></sub>



```php
// --- Contract
public static function link(string $value, string $message): void
// --- Usage
Assert::link($value, $message);
```

### - `notLink` Function

<sub><sup>Asserts that $value is NOT a symbolic link. </sup></sub>



```php
// --- Contract
public static function notLink(string $value, string $message): void
// --- Usage
Assert::notLink($value, $message);
```

### - `uploadedFile` Function

<sub><sup>Asserts that $value was uploaded via HTTP POST (is_uploaded_file). </sup></sub>



```php
// --- Contract
public static function uploadedFile(string $value, string $message): void
// --- Usage
Assert::uploadedFile($value, $message);
```

### - `notUploadedFile` Function

<sub><sup>Asserts that $value was NOT uploaded via HTTP POST. </sup></sub>



```php
// --- Contract
public static function notUploadedFile(string $value, string $message): void
// --- Usage
Assert::notUploadedFile($value, $message);
```

### - `executableFile` Function

<sub><sup>Asserts that $value is an executable file path. </sup></sub>



```php
// --- Contract
public static function executableFile(string $value, string $message): void
// --- Usage
Assert::executableFile($value, $message);
```

### - `notExecutableFile` Function

<sub><sup>Asserts that $value is NOT an executable file path. </sup></sub>



```php
// --- Contract
public static function notExecutableFile(string $value, string $message): void
// --- Usage
Assert::notExecutableFile($value, $message);
```

### - `writableFile` Function

<sub><sup>Asserts that $value is a writable file or directory path. </sup></sub>



```php
// --- Contract
public static function writableFile(string $value, string $message): void
// --- Usage
Assert::writableFile($value, $message);
```

### - `notWritableFile` Function

<sub><sup>Asserts that $value is NOT a writable path. </sup></sub>



```php
// --- Contract
public static function notWritableFile(string $value, string $message): void
// --- Usage
Assert::notWritableFile($value, $message);
```

### - `readableFile` Function

<sub><sup>Asserts that $value is a readable file or directory path. </sup></sub>



```php
// --- Contract
public static function readableFile(string $value, string $message): void
// --- Usage
Assert::readableFile($value, $message);
```

### - `notReadableFile` Function

<sub><sup>Asserts that $value is NOT a readable path. </sup></sub>



```php
// --- Contract
public static function notReadableFile(string $value, string $message): void
// --- Usage
Assert::notReadableFile($value, $message);
```

### - `exists` Function

<sub><sup>Asserts that $value points to an existing file or directory (file_exists). </sup></sub>



```php
// --- Contract
public static function exists(string $value, string $message): void
// --- Usage
Assert::exists($value, $message);
```

### - `notExists` Function

<sub><sup>Asserts that $value does NOT point to any existing filesystem entry. </sup></sub>



```php
// --- Contract
public static function notExists(string $value, string $message): void
// --- Usage
Assert::notExists($value, $message);
```

### - `length` Function

<sub><sup>Asserts that the multibyte character length of $value equals $length. </sup></sub>



```php
// --- Contract
public static function length(string $value, int $length, string $message): void
// --- Usage
Assert::length($value, $length, $message);
```

### - `count` Function

<sub><sup>Asserts that the count of $value (array or Countable) equals $count. </sup></sub>



<sub><sup>@phpstan-ignore missingType.iterableValue </sup></sub>



```php
// --- Contract
public static function count(Countable|array $value, int $count, string $message): void
// --- Usage
Assert::count($value, $count, $message);
```

### - `equals` Function

<sub><sup>Asserts that $value is loosely equal (==) to $expected. </sup></sub>



```php
// --- Contract
public static function equals(mixed $value, mixed $expected, string $message): void
// --- Usage
Assert::equals($value, $expected, $message);
```

### - `notEquals` Function

<sub><sup>Asserts that $value is NOT loosely equal (!=) to $expected. </sup></sub>



```php
// --- Contract
public static function notEquals(mixed $value, mixed $expected, string $message): void
// --- Usage
Assert::notEquals($value, $expected, $message);
```

### - `same` Function

<sub><sup>Asserts that $value is strictly identical (===) to $expected. </sup></sub>



```php
// --- Contract
public static function same(mixed $value, mixed $expected, string $message): void
// --- Usage
Assert::same($value, $expected, $message);
```

### - `notSame` Function

<sub><sup>Asserts that $value is NOT strictly identical (!==) to $expected. </sup></sub>



```php
// --- Contract
public static function notSame(mixed $value, mixed $expected, string $message): void
// --- Usage
Assert::notSame($value, $expected, $message);
```

### - `empty` Function

<sub><sup>Asserts that $value is empty as evaluated by PHP&#039;s empty() construct. </sup></sub>



```php
// --- Contract
public static function empty(mixed $value, string $message): void
// --- Usage
Assert::empty($value, $message);
```

### - `notEmpty` Function

<sub><sup>Asserts that $value is NOT empty. </sup></sub>



```php
// --- Contract
public static function notEmpty(mixed $value, string $message): void
// --- Usage
Assert::notEmpty($value, $message);
```

### - `null` Function

<sub><sup>Asserts that $value is strictly null. </sup></sub>



```php
// --- Contract
public static function null(mixed $value, string $message): void
// --- Usage
Assert::null($value, $message);
```

### - `notNull` Function

<sub><sup>Asserts that $value is NOT null. </sup></sub>



```php
// --- Contract
public static function notNull(mixed $value, string $message): void
// --- Usage
Assert::notNull($value, $message);
```

### - `numeric` Function

<sub><sup>Asserts that $value is numeric (an integer, float, or numeric string). </sup></sub>



```php
// --- Contract
public static function numeric(mixed $value, string $message): void
// --- Usage
Assert::numeric($value, $message);
```

### - `notNumeric` Function

<sub><sup>Asserts that $value is NOT numeric. </sup></sub>



```php
// --- Contract
public static function notNumeric(mixed $value, string $message): void
// --- Usage
Assert::notNumeric($value, $message);
```

### - `finite` Function

<sub><sup>Asserts that $value is a finite float (not INF, -INF, or NAN). </sup></sub>



```php
// --- Contract
public static function finite(float $value, string $message): void
// --- Usage
Assert::finite($value, $message);
```

### - `infinite` Function

<sub><sup>Asserts that $value is an infinite float (INF or -INF). </sup></sub>



```php
// --- Contract
public static function infinite(float $value, string $message): void
// --- Usage
Assert::infinite($value, $message);
```

### - `float` Function

<sub><sup>Asserts that $value is of type float. </sup></sub>



```php
// --- Contract
public static function float(mixed $value, string $message): void
// --- Usage
Assert::float($value, $message);
```

### - `notFloat` Function

<sub><sup>Asserts that $value is NOT of type float. </sup></sub>



```php
// --- Contract
public static function notFloat(mixed $value, string $message): void
// --- Usage
Assert::notFloat($value, $message);
```

### - `int` Function

<sub><sup>Asserts that $value is of type int. </sup></sub>



```php
// --- Contract
public static function int(mixed $value, string $message): void
// --- Usage
Assert::int($value, $message);
```

### - `notInt` Function

<sub><sup>Asserts that $value is NOT of type int. </sup></sub>



```php
// --- Contract
public static function notInt(mixed $value, string $message): void
// --- Usage
Assert::notInt($value, $message);
```

### - `greater` Function

<sub><sup>Asserts that $value is strictly greater than $expected. </sup></sub>



```php
// --- Contract
public static function greater(int $value, int $expected, string $message): void
// --- Usage
Assert::greater($value, $expected, $message);
```

### - `greaterOrEquals` Function

<sub><sup>Asserts that $value is greater than or equal to $expected. </sup></sub>



```php
// --- Contract
public static function greaterOrEquals(int $value, int $expected, string $message): void
// --- Usage
Assert::greaterOrEquals($value, $expected, $message);
```

### - `lower` Function

<sub><sup>Asserts that $value is strictly less than $expected. </sup></sub>



```php
// --- Contract
public static function lower(int $value, int $expected, string $message): void
// --- Usage
Assert::lower($value, $expected, $message);
```

### - `lowerOrEquals` Function

<sub><sup>Asserts that $value is less than or equal to $expected. </sup></sub>



```php
// --- Contract
public static function lowerOrEquals(int $value, int $expected, string $message): void
// --- Usage
Assert::lowerOrEquals($value, $expected, $message);
```

### - `object` Function

<sub><sup>Asserts that $value is an object. </sup></sub>



```php
// --- Contract
public static function object(mixed $value, string $message): void
// --- Usage
Assert::object($value, $message);
```

### - `notObject` Function

<sub><sup>Asserts that $value is NOT an object. </sup></sub>



```php
// --- Contract
public static function notObject(mixed $value, string $message): void
// --- Usage
Assert::notObject($value, $message);
```

### - `instanceOf` Function

<sub><sup>Asserts that $value is an instance of or a subclass of $class. Accepts both objects and class-name strings. </sup></sub>



```php
// --- Contract
public static function instanceOf(mixed $value, string $class, string $message): void
// --- Usage
Assert::instanceOf($value, $class, $message);
```

### - `notInstanceOf` Function

<sub><sup>Asserts that $value is NOT an instance of or subclass of $class. </sup></sub>



```php
// --- Contract
public static function notInstanceOf(mixed $value, string $class, string $message): void
// --- Usage
Assert::notInstanceOf($value, $class, $message);
```

### - `resource` Function

<sub><sup>Asserts that $value is a PHP resource (e.g. a file handle or stream). </sup></sub>



```php
// --- Contract
public static function resource(mixed $value, string $message): void
// --- Usage
Assert::resource($value, $message);
```

### - `notResource` Function

<sub><sup>Asserts that $value is NOT a PHP resource. </sup></sub>



```php
// --- Contract
public static function notResource(mixed $value, string $message): void
// --- Usage
Assert::notResource($value, $message);
```

### - `scalar` Function

<sub><sup>Asserts that $value is a scalar (int, float, string, or bool). </sup></sub>



```php
// --- Contract
public static function scalar(mixed $value, string $message): void
// --- Usage
Assert::scalar($value, $message);
```

### - `notScalar` Function

<sub><sup>Asserts that $value is NOT scalar. </sup></sub>



```php
// --- Contract
public static function notScalar(mixed $value, string $message): void
// --- Usage
Assert::notScalar($value, $message);
```

### - `string` Function

<sub><sup>Asserts that $value is of type string. </sup></sub>



```php
// --- Contract
public static function string(mixed $value, string $message): void
// --- Usage
Assert::string($value, $message);
```

### - `notString` Function

<sub><sup>Asserts that $value is NOT of type string. </sup></sub>



```php
// --- Contract
public static function notString(mixed $value, string $message): void
// --- Usage
Assert::notString($value, $message);
```

### - `array` Function

<sub><sup>Asserts that $value is a PHP array. </sup></sub>



```php
// --- Contract
public static function array(mixed $value, string $message): void
// --- Usage
Assert::array($value, $message);
```

### - `notArray` Function

<sub><sup>Asserts that $value is NOT a PHP array. </sup></sub>



```php
// --- Contract
public static function notArray(mixed $value, string $message): void
// --- Usage
Assert::notArray($value, $message);
```

### - `countable` Function

<sub><sup>Asserts that $value is countable (an array or an object implementing Countable). </sup></sub>



```php
// --- Contract
public static function countable(mixed $value, string $message): void
// --- Usage
Assert::countable($value, $message);
```

### - `notCountable` Function

<sub><sup>Asserts that $value is NOT countable. </sup></sub>



```php
// --- Contract
public static function notCountable(mixed $value, string $message): void
// --- Usage
Assert::notCountable($value, $message);
```

### - `iterable` Function

<sub><sup>Asserts that $value is iterable (an array or an object implementing Traversable). </sup></sub>



```php
// --- Contract
public static function iterable(mixed $value, string $message): void
// --- Usage
Assert::iterable($value, $message);
```

### - `notIterable` Function

<sub><sup>Asserts that $value is NOT iterable. </sup></sub>



```php
// --- Contract
public static function notIterable(mixed $value, string $message): void
// --- Usage
Assert::notIterable($value, $message);
```

## 📦 Sakoo\Framework\Core\Assert\Exception

### 🟥 LazyAssertionException

<sub><sup>Aggregates all assertion failures collected during a LazyAssertion run. </sup></sub>



<sub><sup>Instead of stopping at the first failure, LazyAssertion accumulates every InvalidArgumentException keyed by chain name, then calls LazyAssertionException::init() to bundle them into a single, numbered message. This gives callers a complete picture of all validation errors in one throw. </sup></sub>



### - `init` Function

<sub><sup>Constructs a LazyAssertionException from the full map of accumulated failures. Each failure is formatted as a numbered line prefixed by its chain name. </sup></sub>



<sub><sup>@phpstan-param array&lt;string,array&lt;int,InvalidArgumentException&gt;&gt; $exceptions </sup></sub>



```php
// --- Contract
public static function init(array $exceptions): self
// --- Usage
LazyAssertionException::init($exceptions);
```

### 🟥 InvalidArgumentException

<sub><sup>Thrown by Assert when a single assertion fails immediately. </sup></sub>



<sub><sup>Carries the human-readable message describing which assertion was violated and what value triggered the failure. Extends the framework base Exception so it can be caught at application boundaries alongside other framework exceptions. </sup></sub>



## 📦 Sakoo\Framework\Core\Regex

### 🟢 Regex

<sub><sup>Fluent regex builder and executor. </sup></sub>



<sub><sup>Provides a composable, readable API for constructing PCRE regular expressions programmatically, avoiding raw pattern strings scattered throughout the codebase. Every builder method appends to an internal pattern string and returns the same instance for chaining. The finished pattern is delimited with forward-slashes when passed to PCRE functions. </sup></sub>



<sub><sup>Anchors, quantifiers, character classes, lookarounds, and grouping constructs are all available as named methods. Raw pattern fragments can be appended via add() (unescaped) or safeAdd() (auto-escaped with preg_quote). Callable arguments passed to methods that accept callable|string receive the current Regex instance so nested sub-expressions can be built inline. </sup></sub>



<sub><sup>Constants ALPHABET_UPPER, ALPHABET_LOWER, DIGITS, UNDERLINE, and DOT are provided as pre-defined character-class ranges for use inside bracket expressions. </sup></sub>



#### How to use the Class:

```php
$regex = new Regex(string $pattern);
```

### - `safeAdd` Function

<sub><sup>Appends $value to the pattern after escaping all PCRE metacharacters via preg_quote. </sup></sub>



```php
// --- Contract
public function safeAdd(string $value): static
// --- Usage
$regex->safeAdd($value);
```

### - `add` Function

<sub><sup>Appends a raw, unescaped fragment directly to the pattern. </sup></sub>



```php
// --- Contract
public function add(string $value): static
// --- Usage
$regex->add($value);
```

### - `startOfLine` Function

<sub><sup>Appends the start-of-line anchor (^) to the pattern. </sup></sub>



```php
// --- Contract
public function startOfLine(): static
// --- Usage
$regex->startOfLine();
```

### - `endOfLine` Function

<sub><sup>Appends the end-of-line anchor ($) to the pattern. </sup></sub>



```php
// --- Contract
public function endOfLine(): static
// --- Usage
$regex->endOfLine();
```

### - `startsWith` Function

<sub><sup>Appends a start-of-line anchor followed by $value, effectively asserting the pattern must match from the beginning of the subject. </sup></sub>



```php
// --- Contract
public function startsWith(callable|string $value): static
// --- Usage
$regex->startsWith($value);
```

### - `endsWith` Function

<sub><sup>Appends $value followed by an end-of-line anchor, asserting the pattern must match at the end of the subject. </sup></sub>



```php
// --- Contract
public function endsWith(callable|string $value): static
// --- Usage
$regex->endsWith($value);
```

### - `digit` Function

<sub><sup>Appends a \d digit token. When $length is greater than zero, a {n} quantifier is appended to match exactly that many digits. </sup></sub>



```php
// --- Contract
public function digit(int $length): static
// --- Usage
$regex->digit($length);
```

### - `oneOf` Function

<sub><sup>Appends a non-capturing alternation group matching any one of the given strings. </sup></sub>



<sub><sup>@param string[] $value </sup></sub>



```php
// --- Contract
public function oneOf(array $value): static
// --- Usage
$regex->oneOf($value);
```

### - `wrap` Function

<sub><sup>Wraps $value in a capturing group, or a non-capturing group when $nonCapturing is true. Callable $value receives the current Regex instance. </sup></sub>



```php
// --- Contract
public function wrap(callable|string $value, bool $nonCapturing): static
// --- Usage
$regex->wrap($value, $nonCapturing);
```

### - `bracket` Function

<sub><sup>Wraps $value in a bracket expression [...]. Useful for building character classes. </sup></sub>



```php
// --- Contract
public function bracket(callable|string $value): static
// --- Usage
$regex->bracket($value);
```

### - `maybe` Function

<sub><sup>Appends $value (escaped) followed by ? making the preceding token optional. </sup></sub>



```php
// --- Contract
public function maybe(string $value): static
// --- Usage
$regex->maybe($value);
```

### - `anything` Function

<sub><sup>Appends .* matching any character zero or more times (greedy). </sup></sub>



```php
// --- Contract
public function anything(): static
// --- Usage
$regex->anything();
```

### - `something` Function

<sub><sup>Appends .+ matching any character one or more times (greedy). </sup></sub>



```php
// --- Contract
public function something(): static
// --- Usage
$regex->something();
```

### - `unixLineBreak` Function

<sub><sup>Appends \n matching a Unix line break. </sup></sub>



```php
// --- Contract
public function unixLineBreak(): static
// --- Usage
$regex->unixLineBreak();
```

### - `windowsLineBreak` Function

<sub><sup>Appends \r\n matching a Windows line break. </sup></sub>



```php
// --- Contract
public function windowsLineBreak(): static
// --- Usage
$regex->windowsLineBreak();
```

### - `tab` Function

<sub><sup>Appends \t matching a horizontal tab character. </sup></sub>



```php
// --- Contract
public function tab(): static
// --- Usage
$regex->tab();
```

### - `space` Function

<sub><sup>Appends \s matching any whitespace character. </sup></sub>



```php
// --- Contract
public function space(): static
// --- Usage
$regex->space();
```

### - `word` Function

<sub><sup>Appends \w matching any word character (letter, digit, or underscore). </sup></sub>



```php
// --- Contract
public function word(): static
// --- Usage
$regex->word();
```

### - `chars` Function

<sub><sup>Appends each of the given raw character fragments to the pattern in order. </sup></sub>



```php
// --- Contract
public function chars(string $values): static
// --- Usage
$regex->chars($values);
```

### - `anythingWithout` Function

<sub><sup>Appends a negated character class [^...]*  matching any character NOT in the set defined by $value, zero or more times. </sup></sub>



```php
// --- Contract
public function anythingWithout(callable|string $value): static
// --- Usage
$regex->anythingWithout($value);
```

### - `somethingWithout` Function

<sub><sup>Appends a negated character class [^...]+ matching any character NOT in the set defined by $value, one or more times. </sup></sub>



```php
// --- Contract
public function somethingWithout(callable|string $value): static
// --- Usage
$regex->somethingWithout($value);
```

### - `anythingWith` Function

<sub><sup>Appends a character class [...]* matching any character IN the set defined by $value, zero or more times. </sup></sub>



```php
// --- Contract
public function anythingWith(callable|string $value): static
// --- Usage
$regex->anythingWith($value);
```

### - `somethingWith` Function

<sub><sup>Appends a character class [...]+ matching any character IN the set defined by $value, one or more times. </sup></sub>



```php
// --- Contract
public function somethingWith(callable|string $value): static
// --- Usage
$regex->somethingWith($value);
```

### - `escapeChars` Function

<sub><sup>Escapes all PCRE metacharacters in $value using preg_quote with a / delimiter. Returns an empty string when $value is empty. </sup></sub>



```php
// --- Contract
public function escapeChars(string $value): string
// --- Usage
$regex->escapeChars($value);
```

### - `lookahead` Function

<sub><sup>Appends a positive lookahead (?=...) asserting that $value matches immediately ahead of the current position without consuming characters. </sup></sub>



```php
// --- Contract
public function lookahead(callable|string $value): static
// --- Usage
$regex->lookahead($value);
```

### - `lookbehind` Function

<sub><sup>Appends a positive lookbehind (?&lt;=...) asserting that $value matches immediately behind the current position without consuming characters. </sup></sub>



```php
// --- Contract
public function lookbehind(callable|string $value): static
// --- Usage
$regex->lookbehind($value);
```

### - `negativeLookahead` Function

<sub><sup>Appends a negative lookahead (?!...) asserting that $value does NOT match immediately ahead of the current position. </sup></sub>



```php
// --- Contract
public function negativeLookahead(callable|string $value): static
// --- Usage
$regex->negativeLookahead($value);
```

### - `negativeLookbehind` Function

<sub><sup>Appends a negative lookbehind (?&lt;!...) asserting that $value does NOT match immediately behind the current position. </sup></sub>



```php
// --- Contract
public function negativeLookbehind(callable|string $value): static
// --- Usage
$regex->negativeLookbehind($value);
```

### - `match` Function

<sub><sup>Executes preg_match against $value and returns the full matches array. Returns an empty array when there is no match. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public function match(string $value): array
// --- Usage
$regex->match($value);
```

### - `matchAll` Function

<sub><sup>Executes preg_match_all against $value and returns all match groups. </sup></sub>



<sub><sup>@return string[][] </sup></sub>



```php
// --- Contract
public function matchAll(string $value): array
// --- Usage
$regex->matchAll($value);
```

### - `test` Function

<sub><sup>Returns true when the pattern matches anywhere in $value, false otherwise. </sup></sub>



```php
// --- Contract
public function test(string $value): bool
// --- Usage
$regex->test($value);
```

### - `replace` Function

<sub><sup>Performs a preg_replace on $string, substituting every match with $replace. Accepts both plain strings and Stringable objects as the subject. </sup></sub>



<sub><sup>@return null|string|string[] </sup></sub>



```php
// --- Contract
public function replace(Stringable|string $string, string $replace): array|string|null
// --- Usage
$regex->replace($string, $replace);
```

### - `split` Function

<sub><sup>Splits $subject on every occurrence of the pattern using preg_split. Returns false on failure. </sup></sub>



<sub><sup>@return false|string[] </sup></sub>



```php
// --- Contract
public function split(Stringable|string $subject): array|false
// --- Usage
$regex->split($subject);
```

### - `get` Function

<sub><sup>Returns the raw pattern string accumulated so far. </sup></sub>



```php
// --- Contract
public function get(): string
// --- Usage
$regex->get();
```

### 🟢 RegexHelper

<sub><sup>Factory for the pre-built Regex instances used internally by the Str class. </sup></sub>



<sub><sup>Centralises the recurring regular expression patterns required for case conversion and string normalisation so they are defined once and reused consistently across the codebase. </sup></sub>



### - `findCamelCase` Function

<sub><sup>Returns a Regex that matches the zero-width boundary between a lowercase letter and an uppercase letter — the split point used to separate camelCase words. </sup></sub>



```php
// --- Contract
public static function findCamelCase(): Regex
// --- Usage
RegexHelper::findCamelCase();
```

### - `getSpaceBetweenWords` Function

<sub><sup>Returns a Regex that matches a single whitespace character preceded and followed by a word character — i.e. the space between two words rather than leading or trailing whitespace. </sup></sub>



```php
// --- Contract
public static function getSpaceBetweenWords(): Regex
// --- Usage
RegexHelper::getSpaceBetweenWords();
```

### - `getSpecialChars` Function

<sub><sup>Returns a Regex that matches one or more characters that are NOT letters, digits, or underscores — effectively any &quot;special&quot; or punctuation character. </sup></sub>



```php
// --- Contract
public static function getSpecialChars(): Regex
// --- Usage
RegexHelper::getSpecialChars();
```

## 📦 Sakoo\Framework\Core\Env

### 🟢 Env

<sub><sup>Reads and exposes environment variables, with optional loading from a .env file. </sup></sub>



<sub><sup>get() provides a typed, null-safe wrapper around getenv() with a configurable fallback so callers never need to guard against false returns. </sup></sub>



<sub><sup>load() parses a simple KEY=VALUE .env file (one assignment per line, no sections, no quoted values, no inline comments) and populates both the process environment via putenv() and the $_ENV superglobal so the values are accessible through standard PHP mechanisms as well as through get(). Lines that do not match the expected pattern are silently skipped, allowing blank lines and comments to be present in the file without causing parse errors. </sup></sub>



### - `get` Function

<sub><sup>Returns the value of the environment variable identified by $key, or $default when the variable is not set or is an empty string. </sup></sub>



```php
// --- Contract
public static function get(string $key, mixed $default): mixed
// --- Usage
Env::get($key, $default);
```

### - `load` Function

<sub><sup>Parses a KEY=VALUE .env file and registers each valid assignment into both the process environment (putenv) and the $_ENV superglobal. </sup></sub>



<sub><sup>When the file does not exist, the method returns without side effects. Each line is trimmed before being tested against the KEY=VALUE pattern; lines that do not match (blank lines, comment lines, malformed entries) are ignored. </sup></sub>



```php
// --- Contract
public static function load(Storage $file): void
// --- Usage
Env::load($file);
```

## 📦 Sakoo\Framework\Core\Testing

### 🟢 ExceptionAssertion

<sub><sup>Fluent builder for asserting that a callable raises an exception. </sup></sub>



<sub><sup>Returned by HelperAssertions::throwsException(), this class provides an ergonomic alternative to PHPUnit&#039;s expectException() family of methods, allowing the expected type, message, and code to be specified in a readable chain before the assertion is executed: </sup></sub>



<sub><sup>$this-&gt;throwsException(fn() =&gt; $obj-&gt;doSomething()) -&gt;withType(DomainException::class) -&gt;withMessage(&#039;Invalid state&#039;) -&gt;withCode(42) -&gt;validate(); </sup></sub>



<sub><sup>validate() invokes the callable, catches any \Exception, and asserts each of the configured properties against the caught exception. If no exception is thrown, the test fails with &quot;Error does not raised!&quot;. The type, message, and code constraints are all optional — omit any of them to skip that assertion. </sup></sub>



#### How to use the Class:

```php
$exceptionAssertion = new ExceptionAssertion(TestCase $phpunit,  $fn);
```

### - `withCode` Function

<sub><sup>Constrains the assertion to verify that the exception&#039;s code equals $code. </sup></sub>



```php
// --- Contract
public function withCode(int $code): static
// --- Usage
$exceptionAssertion->withCode($code);
```

### - `withType` Function

<sub><sup>Constrains the assertion to verify that the exception is an instance of $type. </sup></sub>



```php
// --- Contract
public function withType(string $type): static
// --- Usage
$exceptionAssertion->withType($type);
```

### - `withMessage` Function

<sub><sup>Constrains the assertion to verify that the exception message equals $message. </sup></sub>



```php
// --- Contract
public function withMessage(string $message): static
// --- Usage
$exceptionAssertion->withMessage($message);
```

### - `validate` Function

<sub><sup>Executes the callable and asserts that it throws an exception satisfying all configured constraints. Fails the test immediately when no exception is raised. </sup></sub>



```php
// --- Contract
public function validate(): void
// --- Usage
$exceptionAssertion->validate();
```

## 📦 Sakoo\Framework\Core\Container

### 🟢 Container

<sub><sup>PSR-11-compliant inversion-of-control container with autowiring and cache support. </sup></sub>



<sub><sup>The Container manages three distinct resolution strategies: </sup></sub>



<sub><sup>- Transient bindings (bind()): a fresh object is produced on every call to resolve(). - Singleton bindings (singleton()): the object is created once on first resolution and the same instance is returned on every subsequent call. - Autowiring (new() / resolve() fallback): when no binding is registered, the container inspects the class constructor via reflection, recursively resolves all typed dependencies from itself, and synthesises zero-values for unresolvable primitive parameters. </sup></sub>



<sub><sup>Factories may be provided as a class-name string, a pre-built object, or a callable that returns an object. When registering a concrete class or object against an interface, the container verifies that the implementation actually satisfies the interface and throws TypeMismatchException otherwise. </sup></sub>



<sub><sup>The Cacheable trait adds PHP-file-based persistence of the binding maps, allowing production boots to skip all reflection overhead entirely. </sup></sub>



<sub><sup>An optional $cachePath constructor argument enables caching; when omitted the container operates purely in-memory. </sup></sub>



#### How to use the Class:

```php
$container = new Container(string $cachePath);
```

### - `get` Function

<sub><sup>Returns the object bound to $id, resolving it through the appropriate strategy. Throws ContainerNotFoundException when $id has no registered binding. </sup></sub>



> @throws \Throwable

> @throws ContainerNotFoundException

```php
// --- Contract
public function get(string $id): object
// --- Usage
$container->get($id);
```

### - `has` Function

<sub><sup>Returns true when $id has a singleton or transient binding registered, false otherwise. Does not account for autowirable classes. </sup></sub>



```php
// --- Contract
public function has(string $id): bool
// --- Usage
$container->has($id);
```

### - `bind` Function

<sub><sup>Registers $factory as a transient binding for $interface. A new object is produced on every resolution. Throws TypeMismatchException when a non-callable factory does not implement the given interface. </sup></sub>



> @throws \Throwable

> @throws TypeMismatchException

```php
// --- Contract
public function bind(string $interface, callable|object|string $factory): void
// --- Usage
$container->bind($interface, $factory);
```

### - `singleton` Function

<sub><sup>Registers $factory as a singleton binding for $interface. The object is instantiated once and cached for all subsequent resolutions. Throws TypeMismatchException when a non-callable factory does not implement $interface. </sup></sub>



> @throws \Throwable

> @throws TypeMismatchException

```php
// --- Contract
public function singleton(string $interface, callable|object|string $factory): void
// --- Usage
$container->singleton($interface, $factory);
```

### - `resolve` Function

<sub><sup>Resolves $interface to a concrete object. </sup></sub>



<sub><sup>Resolution order: singleton registry → transient binding registry → direct autowired instantiation via new(). This method is the primary entry point for all dependency resolution inside the framework. </sup></sub>



> @throws \ReflectionException

> @throws \Throwable

> @throws ClassNotInstantiableException

> @throws ClassNotFoundException

```php
// --- Contract
public function resolve(string $interface): object
// --- Usage
$container->resolve($interface);
```

### - `new` Function

<sub><sup>Directly instantiates $class using reflection, bypassing the binding registry. </sup></sub>



<sub><sup>When $params is empty and the constructor has typed parameters, the container autowires each dependency automatically. Explicit $params suppress autowiring entirely and are passed to the constructor as-is. </sup></sub>



<sub><sup>@param array&lt;mixed&gt; $params </sup></sub>



> @throws \ReflectionException

> @throws ClassNotFoundException

> @throws ClassNotInstantiableException

> @throws \Throwable

```php
// --- Contract
public function new(string $class, array $params): object
// --- Usage
$container->new($class, $params);
```

### - `clear` Function

<sub><sup>Resets all bindings, singleton registrations, cached instances, and the on-disk cache, returning the container to a pristine state. </sup></sub>



```php
// --- Contract
public function clear(): void
// --- Usage
$container->clear();
```

### - `loadCache` Function

<sub><sup>Loads the persisted binding and singleton maps from the cache file into the container&#039;s internal state, skipping all reflection and autowiring on boot. </sup></sub>



> @throws \Throwable when no cache file exists at the configured path

```php
// --- Contract
public function loadCache(): void
// --- Usage
$container->loadCache();
```

### - `flushCache` Function

<sub><sup>Deletes the cache file from disk. Returns true on success, false when no cache file was present. </sup></sub>



```php
// --- Contract
public function flushCache(): bool
// --- Usage
$container->flushCache();
```

### - `cacheExists` Function

<sub><sup>Returns true when a cache file exists at the configured cache path. </sup></sub>



```php
// --- Contract
public function cacheExists(): bool
// --- Usage
$container->cacheExists();
```

### - `dumpCache` Function

<sub><sup>Serialises all current bindings and singletons to a PHP cache file, flushing any stale cache beforehand. Throws when no cache path was configured on the container. </sup></sub>



> @throws \Throwable

> @throws \ReflectionException

> @throws ClassNotInstantiableException

> @throws ClassNotFoundException

```php
// --- Contract
public function dumpCache(): void
// --- Usage
$container->dumpCache();
```

## 📦 Sakoo\Framework\Core\Container\Exceptions

### 🟥 ContainerCacheException

<sub><sup>Thrown during container cache operations when a precondition is not satisfied — for example when loadCache() is called but no cache file exists, or when dumpCache() is called but no cache path was configured. Implements PSR-11 ContainerExceptionInterface. </sup></sub>



### 🟥 TypeMismatchException

<sub><sup>Thrown when a concrete class or object is registered against an interface it does not implement. Prevents silent runtime errors that would only surface later during actual usage of the resolved object. Implements PSR-11 ContainerExceptionInterface. </sup></sub>



### 🟥 ClassNotFoundException

<sub><sup>Thrown when the container cannot locate the requested class during resolution or direct instantiation, typically because the class does not exist or is not autoloadable. Implements PSR-11 ContainerExceptionInterface. </sup></sub>



### 🟥 ContainerNotFoundException

<sub><sup>Thrown when the container is asked to resolve an identifier that has no registered binding or singleton. Implements PSR-11 NotFoundExceptionInterface so callers can distinguish a missing registration from other container errors. </sup></sub>



### 🟥 ClassNotInstantiableException

<sub><sup>Thrown when the container attempts to instantiate a class that cannot be constructed directly — for example an abstract class, an interface, or a class with a private constructor. Implements PSR-11 ContainerExceptionInterface. </sup></sub>



## 📦 Sakoo\Framework\Core\Container\Parameter

### 🟢 ParameterSet

<sub><sup>Resolves an ordered list of ReflectionParameters to a flat array of concrete values. </sup></sub>



<sub><sup>Acts as a thin orchestrator over Parameter, iterating constructor parameters in declaration order and delegating each one to a single Parameter instance. The resulting array is ready to be spread into ReflectionClass::newInstanceArgs(). </sup></sub>



#### How to use the Class:

```php
$parameterSet = new ParameterSet(Container $container);
```

### - `resolve` Function

<sub><sup>Resolves every parameter in $parameters to a concrete value and returns them as an ordered list suitable for constructor injection. </sup></sub>



<sub><sup>@param array&lt;\ReflectionParameter&gt; $parameters </sup></sub>



<sub><sup>@return list&lt;mixed&gt; </sup></sub>



> @throws \ReflectionException

> @throws ClassNotFoundException

> @throws ClassNotInstantiableException

> @throws \Throwable

```php
// --- Contract
public function resolve(array $parameters): array
// --- Usage
$parameterSet->resolve($parameters);
```

### 🟢 Parameter

<sub><sup>Resolves a single constructor parameter to a concrete value. </sup></sub>



<sub><sup>When the parameter carries a non-built-in type hint, the container is asked to resolve that type. When a default value is declared on the parameter, that default is returned as-is. When neither condition holds, a safe zero-value is synthesised from the parameter&#039;s type (empty string, 0, false, empty array, etc.), preventing reflection errors on optional infrastructure parameters that have no registered binding. </sup></sub>



#### How to use the Class:

```php
$parameter = new Parameter(Container $container);
```

### - `resolve` Function

<sub><sup>Resolves $parameter to a usable value. </sup></sub>



<sub><sup>Resolution priority: 1. Non-built-in typed parameters are resolved through the container. 2. Parameters with a declared default value return that default. 3. All other parameters receive a synthesised zero-value based on their type. </sup></sub>



> @throws \Throwable

> @throws \ReflectionException

> @throws ClassNotInstantiableException

> @throws ClassNotFoundException

```php
// --- Contract
public function resolve(ReflectionParameter $parameter): mixed
// --- Usage
$parameter->resolve($parameter);
```

## 📦 Sakoo\Framework\Core\Markup

### 🟢 Markdown

<sub><sup>GitHub-Flavored Markdown implementation of the Markup contract. </sup></sub>



<sub><sup>Accumulates a Markdown string in a private buffer and exposes the full Markup API as thin wrappers around write() and writeLine(). The rendered document uses standard GFM syntax: fenced code blocks with language identifiers, &gt; blockquotes for callouts, &lt;sub&gt;&lt;sup&gt; for tiny text, and &lt;br&gt; for forced line breaks within paragraphs. </sup></sub>



<sub><sup>The internal buffer is append-only; there is no mechanism to rewind or edit previously written content. Instances should be constructed fresh for each document generation run. </sup></sub>



<sub><sup>Implements Stringable (via the Markup interface) so the finished document can be retrieved by casting the instance to string without calling get() explicitly. </sup></sub>



#### How to use the Class:

```php
$markdown = new Markdown();
```

### - `write` Function

<sub><sup>Appends $value to the buffer without any trailing newline. </sup></sub>



```php
// --- Contract
public function write(string $value): void
// --- Usage
$markdown->write($value);
```

### - `writeLine` Function

<sub><sup>Appends $value to the buffer followed by a blank line (double PHP_EOL), producing a paragraph break in the rendered output. </sup></sub>



```php
// --- Contract
public function writeLine(string $value): void
// --- Usage
$markdown->writeLine($value);
```

### - `br` Function

<sub><sup>Appends a blank line (double PHP_EOL) to produce a paragraph break. </sup></sub>



```php
// --- Contract
public function br(): void
// --- Usage
$markdown->br();
```

### - `fbr` Function

<sub><sup>Appends an HTML &lt;br&gt; tag for a forced inline line break that does not create a full paragraph gap. </sup></sub>



```php
// --- Contract
public function fbr(): void
// --- Usage
$markdown->fbr();
```

### - `callout` Function

<sub><sup>Appends $value as a GFM blockquote (&gt; prefix), used for @throws callouts in documentation output. </sup></sub>



```php
// --- Contract
public function callout(string $value): void
// --- Usage
$markdown->callout($value);
```

### - `h1` Function

<sub><sup>Appends $value as a level-1 heading (# prefix).</sup></sub>

```php
// --- Contract
public function h1(string $value): void
// --- Usage
$markdown->h1($value);
```

### - `h2` Function

<sub><sup>Appends $value as a level-2 heading (## prefix).</sup></sub>

```php
// --- Contract
public function h2(string $value): void
// --- Usage
$markdown->h2($value);
```

### - `h3` Function

<sub><sup>Appends $value as a level-3 heading (### prefix).</sup></sub>

```php
// --- Contract
public function h3(string $value): void
// --- Usage
$markdown->h3($value);
```

### - `h4` Function

<sub><sup>Appends $value as a level-4 heading (#### prefix).</sup></sub>

```php
// --- Contract
public function h4(string $value): void
// --- Usage
$markdown->h4($value);
```

### - `h5` Function

<sub><sup>Appends $value as a level-5 heading (##### prefix).</sup></sub>

```php
// --- Contract
public function h5(string $value): void
// --- Usage
$markdown->h5($value);
```

### - `h6` Function

<sub><sup>Appends $value as a level-6 heading (###### prefix).</sup></sub>

```php
// --- Contract
public function h6(string $value): void
// --- Usage
$markdown->h6($value);
```

### - `ul` Function

<sub><sup>Appends $value as an unordered list item (- prefix). </sup></sub>



```php
// --- Contract
public function ul(string $value): void
// --- Usage
$markdown->ul($value);
```

### - `link` Function

<sub><sup>Appends an inline hyperlink [text](url) without a trailing newline. </sup></sub>



```php
// --- Contract
public function link(string $url, string $text): void
// --- Usage
$markdown->link($url, $text);
```

### - `image` Function

<sub><sup>Appends an inline image ![alt](path) by prepending &#039;!&#039; to a link(). </sup></sub>



```php
// --- Contract
public function image(string $path, string $alt): void
// --- Usage
$markdown->image($path, $alt);
```

### - `checklist` Function

<sub><sup>Appends $value as a GFM task-list item. The checkbox marker is &#039;X&#039; when $checked is true, empty otherwise. </sup></sub>



```php
// --- Contract
public function checklist(string $value, bool $checked): void
// --- Usage
$markdown->checklist($value, $checked);
```

### - `hr` Function

<sub><sup>Appends a horizontal rule (---) as a section divider. </sup></sub>



```php
// --- Contract
public function hr(): void
// --- Usage
$markdown->hr();
```

### - `code` Function

<sub><sup>Appends $value as a fenced code block with the given $language identifier for syntax highlighting (e.g. &#039;php&#039;, &#039;bash&#039;). </sup></sub>



```php
// --- Contract
public function code(string $value, string $language): void
// --- Usage
$markdown->code($value, $language);
```

### - `inlineCode` Function

<sub><sup>Appends $value wrapped in single backticks for inline monospace formatting. </sup></sub>



```php
// --- Contract
public function inlineCode(string $value): void
// --- Usage
$markdown->inlineCode($value);
```

### - `tiny` Function

<sub><sup>Appends $value wrapped in &lt;sub&gt;&lt;sup&gt; tags to produce small subscript text, used for PHPDoc description paragraphs in generated documentation. </sup></sub>



```php
// --- Contract
public function tiny(string $value): void
// --- Usage
$markdown->tiny($value);
```

### - `get` Function

<sub><sup>Returns the complete accumulated Markdown document. </sup></sub>



```php
// --- Contract
public function get(): string
// --- Usage
$markdown->get();
```

## 📦 Sakoo\Framework\Core\Finder

### 🟢 FileFinder

<sub><sup>Recursive PHP-file finder with configurable filtering options. </sup></sub>



<sub><sup>FileFinder walks a directory tree and collects files whose names match a glob pattern. Filtering options can be combined freely via a fluent builder API: </sup></sub>



<sub><sup>- pattern()         — restricts results to filenames matching a glob (default: &#039;*&#039;). - ignoreVCS()       — skips directories used by common VCS systems (.git, .svn, .hg, .bzr). - ignoreVCSIgnored()— skips files that would be excluded by the nearest .gitignore. - ignoreDotFiles()  — skips any file or directory whose name begins with &#039;.&#039;. </sup></sub>



<sub><sup>getFiles() returns an array of SplFileObject instances ready for further inspection, while find() returns raw pathname strings for callers that need the paths only. </sup></sub>



<sub><sup>The class is declared final to prevent extension; filtering behaviour should be modified by composing FileFinder instances rather than subclassing. </sup></sub>



#### How to use the Class:

```php
$fileFinder = new FileFinder(string $path);
```

### - `pattern` Function

<sub><sup>Restricts results to files whose names match the given glob $pattern. Defaults to &#039;*&#039; (all files) when not called. </sup></sub>



```php
// --- Contract
public function pattern(string $pattern): FileFinder
// --- Usage
$fileFinder->pattern($pattern);
```

### - `ignoreVCS` Function

<sub><sup>When $value is true (the default), directories used by VCS systems (.git, .svn, .hg, .bzr) are excluded from traversal entirely. </sup></sub>



```php
// --- Contract
public function ignoreVCS(bool $value): FileFinder
// --- Usage
$fileFinder->ignoreVCS($value);
```

### - `ignoreVCSIgnored` Function

<sub><sup>When $value is true (the default), files matched by the nearest .gitignore are excluded from results. Requires a readable .gitignore in the working directory. </sup></sub>



```php
// --- Contract
public function ignoreVCSIgnored(bool $value): FileFinder
// --- Usage
$fileFinder->ignoreVCSIgnored($value);
```

### - `ignoreDotFiles` Function

<sub><sup>When $value is true (the default), any file or directory whose name starts with &#039;.&#039; is excluded from traversal and results. </sup></sub>



```php
// --- Contract
public function ignoreDotFiles(bool $value): FileFinder
// --- Usage
$fileFinder->ignoreDotFiles($value);
```

### - `getFiles` Function

<sub><sup>Executes the search and returns all matching files as SplFileObject instances opened in read-write (&#039;r+&#039;) mode. </sup></sub>



<sub><sup>@return SplFileObject[] </sup></sub>



```php
// --- Contract
public function getFiles(): array
// --- Usage
$fileFinder->getFiles();
```

### - `find` Function

<sub><sup>Executes the search and returns the absolute pathnames of all matching files as plain strings, applying all configured filters during traversal. </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public function find(): array
// --- Usage
$fileFinder->find();
```

### 🟢 SplFileObject

<sub><sup>Extends PHP&#039;s SplFileObject with framework-aware namespace resolution. </sup></sub>



<sub><sup>Provides two additional capabilities on top of the standard SplFileObject API: </sup></sub>



<sub><sup>- getNamespace() derives the fully-qualified PSR-4 class name for this file by stripping the core directory prefix and converting the remaining relative path to a namespace string via Path::pathToNamespace(). </sup></sub>



<sub><sup>- isClassFile() checks whether the resolved namespace actually corresponds to a loaded class, allowing callers to distinguish PHP files that define a class from plain scripts, configuration files, or helpers. </sup></sub>



### - `isClassFile` Function

<sub><sup>Returns true when the namespace derived from this file&#039;s path corresponds to an existing, autoloaded class. </sup></sub>



```php
// --- Contract
public function isClassFile(): bool
// --- Usage
$splFileObject->isClassFile();
```

### - `getNamespace` Function

<sub><sup>Derives the fully-qualified framework class name for this file. </sup></sub>



<sub><sup>Computes the path relative to the core source root by stripping the core directory prefix, prepends &#039;src&#039;, and delegates to Path::pathToNamespace() for the final conversion. </sup></sub>



<sub><sup>@return class-string </sup></sub>



```php
// --- Contract
public function getNamespace(): string
// --- Usage
$splFileObject->getNamespace();
```

### 🟢 GitIgnore

<sub><sup>Parses a .gitignore file and tests whether a given filesystem path is ignored. </sup></sub>



<sub><sup>Each non-comment, non-empty line in the .gitignore is converted to a PCRE pattern according to simplified gitignore semantics: </sup></sub>



<sub><sup>- Lines starting with &#039;!&#039; negate a previous match (un-ignore). - Lines starting with &#039;/&#039; are rooted to the repository root. - Trailing &#039;/&#039; denotes a directory pattern. - Wildcards &#039;*&#039; and &#039;?&#039; are translated to their PCRE equivalents (.*  and .). - All other metacharacters are quoted via preg_quote. </sup></sub>



<sub><sup>Rules are evaluated in order; the last matching rule wins, mirroring real git behaviour. The class is readonly because the rule set is derived entirely from the file at construction time and must not change afterwards. </sup></sub>



#### How to use the Class:

```php
$gitIgnore = new GitIgnore(string $path);
```

### - `isIgnored` Function

<sub><sup>Returns true when $file would be excluded by the parsed .gitignore rules. </sup></sub>



<sub><sup>The absolute real path of $file is resolved, converted to a path relative to the repository root, and matched against all rules in order. Returns false when the path cannot be resolved or no rule matches. </sup></sub>



```php
// --- Contract
public function isIgnored(string $file): bool
// --- Usage
$gitIgnore->isIgnored($file);
```

## 📦 Sakoo\Framework\Core\Locker

### 🟢 Locker

<sub><sup>Simple in-memory boolean lock for protecting critical sections. </sup></sub>



<sub><sup>Provides a lightweight mutex-like flag that a single process can set and query to guard a critical section from re-entrant or concurrent access within the same request lifecycle. Because the state is held in an instance property it is not shared across processes or requests — this is not a distributed lock. </sup></sub>



<sub><sup>Typical usage is to lock before entering a critical operation and unlock in a finally block to ensure the flag is always cleared even if an exception is thrown. </sup></sub>



### - `lock` Function

<sub><sup>Acquires the lock, marking the critical section as entered. </sup></sub>



```php
// --- Contract
public function lock(): void
// --- Usage
$locker->lock();
```

### - `unlock` Function

<sub><sup>Releases the lock, allowing the critical section to be entered again. </sup></sub>



```php
// --- Contract
public function unlock(): void
// --- Usage
$locker->unlock();
```

### - `isLocked` Function

<sub><sup>Returns true when the lock is currently held, false otherwise. </sup></sub>



```php
// --- Contract
public function isLocked(): bool
// --- Usage
$locker->isLocked();
```

## 📦 Sakoo\Framework\Core\Doc

### 🟢 Doc

<sub><sup>Documentation generator that introspects PHP source files and writes formatted output. </sup></sub>



<sub><sup>Accepts a list of SplFileObject instances, a Formatter strategy, and a Storage target file. On generate(), it: </sup></sub>



<sub><sup>1. Iterates the source files, skipping non-class files and classes marked with the DontDocument attribute, traits, abstracts, and interfaces. 2. Groups the surviving ClassObject instances by namespace into NamespaceObject bags. 3. Passes the ordered namespace list to the Formatter to produce a Markdown string. 4. Removes any existing target file, recreates it, and writes the rendered string. </sup></sub>



<sub><sup>The Formatter strategy determines the output style — DocFormatter produces a full API reference while TocFormatter produces a navigation sidebar. Both are injected at the call site so Doc itself remains format-agnostic. </sup></sub>



#### How to use the Class:

```php
$doc = new Doc(array $files, Formatter $formatter, Storage $docFile);
```

### - `generate` Function

<sub><sup>Introspects the source files, formats the resulting namespace graph, and writes the output to the configured Storage target, replacing any prior content. </sup></sub>



```php
// --- Contract
public function generate(): void
// --- Usage
$doc->generate();
```

## 📦 Sakoo\Framework\Core\Doc\Formatters

### 🟢 DocFormatter

<sub><sup>Full API reference formatter for the documentation generator. </sup></sub>



<sub><sup>Produces a Markdown document structured as: </sup></sub>



<sub><sup># 📚 Documentation ## 📦 Namespace\Name ### 🟢/🟥 ClassName #### How to use the Class:   (constructor or static instantiator) ##### Contract / Usage       (regular methods) </sup></sub>



<sub><sup>For each method the formatter emits: - A &quot;How to use the Class&quot; block for constructors and static named constructors, showing the instantiation expression. - A Contract block (full signature with modifiers and return type) and a Usage block (call-site expression with `$instance-&gt;method($args)` or `Class::method()`) for all other public/protected methods. - Inline PHPDoc lines rendered as small text, with @throws lines rendered as callout blocks. </sup></sub>



<sub><sup>Classes marked as exceptions are given a 🟥 icon; all others use 🟢. Methods carrying the DontDocument attribute, private methods, and non-constructor magic methods are silently skipped. </sup></sub>



<sub><sup>TODO: add @throws label rendering for methods, Attribute support, helper function support. </sup></sub>



### - `format` Function

<sub><sup>Iterates all namespaces and their classes, renders each class&#039;s methods, and returns the complete Markdown API reference as a string. </sup></sub>



<sub><sup>@param NamespaceObject[] $namespaces </sup></sub>



```php
// --- Contract
public function format(array $namespaces): string
// --- Usage
$docFormatter->format($namespaces);
```

#### How to use the Class:

```php
$docFormatter = new DocFormatter(Markup $markup);
```

### 🟢 TocFormatter

<sub><sup>Table-of-contents formatter that produces a Markdown navigation sidebar. </sup></sub>



<sub><sup>Renders one bullet-point entry per namespace in the form: </sup></sub>



<sub><sup>[ShortName](#-anchor) </sup></sub>



<sub><sup>where ShortName strips the leading &#039;Sakoo\Framework\Core\&#039; prefix for readability and the anchor is derived by lowercasing the full namespace and replacing spaces and backslashes with hyphens, matching GitHub wiki anchor conventions. </sup></sub>



<sub><sup>Intended for use as a _Sidebar.md file in GitHub wikis generated by DocGenCommand. </sup></sub>



### - `format` Function

<sub><sup>Emits one Markdown list item per namespace and returns the accumulated sidebar string. </sup></sub>



<sub><sup>@param NamespaceObject[] $namespaces </sup></sub>



```php
// --- Contract
public function format(array $namespaces): string
// --- Usage
$tocFormatter->format($namespaces);
```

#### How to use the Class:

```php
$tocFormatter = new TocFormatter(Markup $markup);
```

## 📦 Sakoo\Framework\Core\Doc\Attributes

### 🟢 DontDocument

<sub><sup>Marker attribute that excludes the annotated class, method, property, or constant from the framework&#039;s automatic documentation generator. </sup></sub>



<sub><sup>Apply this attribute to internal implementation details — such as magic methods, framework-internal helpers, and infrastructure glue — that should not appear in the public API documentation produced by the doc:gen console command. </sup></sub>



<sub><sup>The attribute targets all declaration types (TARGET_ALL) so it can be placed on classes, interfaces, traits, enums, methods, properties, constants, and function parameters without restriction. </sup></sub>



#### How to use the Class:

```php
$dontDocument = new DontDocument();
```

## 📦 Sakoo\Framework\Core\Doc\Object\PhpDoc

### 🟢 PhpDocLineObject

#### How to use the Class:

```php
$phpDocLineObject = new PhpDocLineObject(string $line);
```

### - `isThrows` Function

```php
// --- Contract
public function isThrows(): bool
// --- Usage
$phpDocLineObject->isThrows();
```

### - `isMethod` Function

```php
// --- Contract
public function isMethod(): bool
// --- Usage
$phpDocLineObject->isMethod();
```

### - `isEmpty` Function

```php
// --- Contract
public function isEmpty(): bool
// --- Usage
$phpDocLineObject->isEmpty();
```

### 🟢 PhpDocObject

#### How to use the Class:

```php
$phpDocObject = new PhpDocObject(ClassInterface|MethodInterface $component);
```

### - `getLines` Function

<sub><sup>@return PhpDocLineObject[] </sup></sub>



```php
// --- Contract
public function getLines(): array
// --- Usage
$phpDocObject->getLines();
```

## 📦 Sakoo\Framework\Core\Doc\Object\Class

### 🟢 ClassObject

<sub><sup>Reflection-backed value object representing a single PHP class for documentation. </sup></sub>



<sub><sup>Wraps a ReflectionClass and exposes the information the documentation generator needs: the class short name, namespace, public/protected methods (as MethodObject instances), virtual methods parsed from [at-sign]method PHPDoc tags (as VirtualMethodObject instances), and metadata flags (isException, isInstantiable, isIllegal). </sup></sub>



<sub><sup>isIllegal() determines whether the class should be excluded from generated docs: it returns true for classes carrying the DontDocument attribute, traits, abstracts, and interfaces — leaving only concrete, documentable classes in the output. </sup></sub>



<sub><sup>getPhpDocs() parses the class-level doc comment into trimmed lines for use by formatters, and getVirtualMethods() extracts [at-sign]method tag lines from those docs and attempts to parse each one into a VirtualMethodObject. </sup></sub>



#### How to use the Class:

```php
$classObject = new ClassObject(ReflectionClass $class);
```

### - `getMethods` Function

<sub><sup>Returns all public and protected methods declared in the Sakoo framework namespace as MethodObject instances, skipping inherited non-framework methods. </sup></sub>



<sub><sup>@return MethodObject[] </sup></sub>



```php
// --- Contract
public function getMethods(): array
// --- Usage
$classObject->getMethods();
```

### - `getNamespace` Function

<sub><sup>Returns the fully-qualified namespace name of the class (excluding the class name itself), used to group classes into NamespaceObject bags. </sup></sub>



```php
// --- Contract
public function getNamespace(): string
// --- Usage
$classObject->getNamespace();
```

### - `isIllegal` Function

<sub><sup>Returns true when this class should be excluded from documentation. </sup></sub>



<sub><sup>A class is illegal when it carries the DontDocument attribute, is a trait, is abstract, or is an interface. </sup></sub>



```php
// --- Contract
public function isIllegal(): bool
// --- Usage
$classObject->isIllegal();
```

### - `isInstantiable` Function

<sub><sup>Returns true when the class can be instantiated with new (not abstract, not interface). </sup></sub>



```php
// --- Contract
public function isInstantiable(): bool
// --- Usage
$classObject->isInstantiable();
```

### - `isException` Function

<sub><sup>Returns true when the class is a subclass of the framework base Exception, used by formatters to apply the 🟥 icon. </sup></sub>



```php
// --- Contract
public function isException(): bool
// --- Usage
$classObject->isException();
```

### - `getName` Function

<sub><sup>Returns the unqualified short class name (without namespace prefix). </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$classObject->getName();
```

### - `getRawDoc` Function

```php
// --- Contract
public function getRawDoc(): string
// --- Usage
$classObject->getRawDoc();
```

### - `getPhpDocObject` Function

```php
// --- Contract
public function getPhpDocObject(): PhpDocObject
// --- Usage
$classObject->getPhpDocObject();
```

### - `getVirtualMethods` Function

<sub><sup>Parses [at-sign]method tag lines from the class-level PHPDoc and returns them as VirtualMethodObject instances. Lines that fail to parse are silently skipped. </sup></sub>



<sub><sup>@return VirtualMethodObject[] </sup></sub>



```php
// --- Contract
public function getVirtualMethods(): array
// --- Usage
$classObject->getVirtualMethods();
```

### - `getInterfaces` Function

<sub><sup>Returns all interfaces implemented by this class as a map of interface-name → ReflectionClass, used by MethodObject to locate inherited PHPDoc comments from interface definitions. </sup></sub>



<sub><sup>@return array&lt;string, \ReflectionClass&lt;object&gt;&gt; </sup></sub>



```php
// --- Contract
public function getInterfaces(): array
// --- Usage
$classObject->getInterfaces();
```

### - `shouldNotDocument` Function

```php
// --- Contract
public function shouldNotDocument(): bool
// --- Usage
$classObject->shouldNotDocument();
```

## 📦 Sakoo\Framework\Core\Doc\Object\Parameter

### 🟢 ParameterObject

<sub><sup>Reflection-backed value object representing a single method parameter. </sup></sub>



<sub><sup>Wraps a ReflectionParameter and exposes the two pieces of information formatters need: the parameter name (for usage examples) and the type (for contract signatures), delegating type resolution to TypeObject. </sup></sub>



#### How to use the Class:

```php
$parameterObject = new ParameterObject(ReflectionParameter $parameter);
```

### - `getName` Function

<sub><sup>Returns the parameter name without the leading &#039;$&#039; sigil. </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$parameterObject->getName();
```

### - `getType` Function

<sub><sup>Returns a TypeObject wrapping the parameter&#039;s declared type, or wrapping null when no type hint is present. </sup></sub>



```php
// --- Contract
public function getType(): TypeObject
// --- Usage
$parameterObject->getType();
```

### 🟢 TypeObject

<sub><sup>Resolves a ReflectionType to a documentation-friendly short name string. </sup></sub>



<sub><sup>PHP&#039;s reflection API returns three distinct type classes — ReflectionNamedType, ReflectionUnionType, and ReflectionIntersectionType. This value object normalises them into a single nullable string suitable for documentation output: </sup></sub>



<sub><sup>- Null (untyped)          → null - Built-in named type     → the type name as-is (e.g. &#039;string&#039;, &#039;int&#039;, &#039;array&#039;) - Non-built-in named type → the short class name without namespace prefix - Union type              → pipe-joined list of short names (e.g. &#039;string|int|Foo&#039;) - Intersection type       → not explicitly handled; getName() returns null </sup></sub>



#### How to use the Class:

```php
$typeObject = new TypeObject(ReflectionType $type);
```

### - `getName` Function

<sub><sup>Returns the human-readable type name, or null when no type hint was declared or the type cannot be resolved to a short representation. </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$typeObject->getName();
```

### - `getReflectionUnionTypeName` Function

<sub><sup>Joins each member of a union type into a pipe-separated string, using the short class name for non-built-in types. Trailing pipes are stripped. </sup></sub>



```php
// --- Contract
public function getReflectionUnionTypeName(ReflectionUnionType $type): string
// --- Usage
$typeObject->getReflectionUnionTypeName($type);
```

## 📦 Sakoo\Framework\Core\Doc\Object\Method

### 🟢 MethodObject

<sub><sup>Reflection-backed value object representing a real PHP method for documentation. </sup></sub>



<sub><sup>Wraps a ReflectionMethod and a parent ClassObject, implementing MethodInterface so the documentation formatters can treat it identically to VirtualMethodObject. </sup></sub>



<sub><sup>getPhpDocs() first checks the method&#039;s own doc comment; if absent it looks for the same method on any interface implemented by the owning class, enabling interface-level docs to propagate automatically to concrete implementations. </sup></sub>



<sub><sup>shouldNotDocument() excludes private methods, methods carrying the DontDocument attribute, and non-constructor magic methods (__toString, __get, etc.). </sup></sub>



<sub><sup>isFrameworkFunction() guards against documenting inherited methods from third-party dependencies by checking that the declaring class belongs to the Sakoo namespace. </sup></sub>



<sub><sup>isStaticInstantiator() identifies static named constructors (e.g. Money::of()) so formatters can render them as &quot;How to use the Class&quot; instantiation snippets rather than regular method contracts. </sup></sub>



#### How to use the Class:

```php
$methodObject = new MethodObject(ClassObject $classObject, ReflectionMethod $method);
```

### - `getClass` Function

<sub><sup>Returns the ClassObject that owns this method. </sup></sub>



```php
// --- Contract
public function getClass(): ClassObject
// --- Usage
$methodObject->getClass();
```

### - `getMethodParameters` Function

<sub><sup>Returns all parameters of this method as an ordered list of ParameterObject instances. </sup></sub>



<sub><sup>@return ParameterObject[] </sup></sub>



```php
// --- Contract
public function getMethodParameters(): array
// --- Usage
$methodObject->getMethodParameters();
```

### - `getName` Function

<sub><sup>Returns the method name as a plain string. </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$methodObject->getName();
```

### - `isPrivate` Function

<sub><sup>Returns true when the method has private visibility. </sup></sub>



```php
// --- Contract
public function isPrivate(): bool
// --- Usage
$methodObject->isPrivate();
```

### - `isProtected` Function

<sub><sup>Returns true when the method has protected visibility. </sup></sub>



```php
// --- Contract
public function isProtected(): bool
// --- Usage
$methodObject->isProtected();
```

### - `isPublic` Function

<sub><sup>Returns true when the method has public visibility. </sup></sub>



```php
// --- Contract
public function isPublic(): bool
// --- Usage
$methodObject->isPublic();
```

### - `isStatic` Function

<sub><sup>Returns true when the method is declared static. </sup></sub>



```php
// --- Contract
public function isStatic(): bool
// --- Usage
$methodObject->isStatic();
```

### - `isConstructor` Function

<sub><sup>Returns true when the method is __construct. </sup></sub>



```php
// --- Contract
public function isConstructor(): bool
// --- Usage
$methodObject->isConstructor();
```

### - `isMagicMethod` Function

<sub><sup>Returns true when the method name begins with &#039;__&#039;. </sup></sub>



```php
// --- Contract
public function isMagicMethod(): bool
// --- Usage
$methodObject->isMagicMethod();
```

### - `getMethodReturnTypes` Function

<sub><sup>Returns the human-readable return type string, resolving union types and short class names via TypeObject. Returns an empty string when no return type is declared. </sup></sub>



```php
// --- Contract
public function getMethodReturnTypes(): string
// --- Usage
$methodObject->getMethodReturnTypes();
```

### - `getRawDoc` Function

```php
// --- Contract
public function getRawDoc(): string
// --- Usage
$methodObject->getRawDoc();
```

### - `getPhpDocObject` Function

```php
// --- Contract
public function getPhpDocObject(): PhpDocObject
// --- Usage
$methodObject->getPhpDocObject();
```

### - `getModifiers` Function

<sub><sup>Returns the human-readable modifier names (e.g. [&#039;public&#039;, &#039;static&#039;]) via PHP&#039;s Reflection::getModifierNames(). </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public function getModifiers(): array
// --- Usage
$methodObject->getModifiers();
```

### - `isFrameworkFunction` Function

<sub><sup>Returns true when the declaring class belongs to the Sakoo framework namespace, filtering out methods inherited from third-party libraries. </sup></sub>



```php
// --- Contract
public function isFrameworkFunction(): bool
// --- Usage
$methodObject->isFrameworkFunction();
```

### - `getDefaultValues` Function

<sub><sup>Returns a comma-separated list of bare parameter variable names prefixed with &#039;$&#039; (e.g. &#039;$name, $value&#039;), used in call-site usage examples. </sup></sub>



```php
// --- Contract
public function getDefaultValues(): string
// --- Usage
$methodObject->getDefaultValues();
```

### - `getDefaultValueTypes` Function

<sub><sup>Returns a comma-separated list of typed parameter declarations (e.g. &#039;string $name, int $value&#039;), used in method contract examples. </sup></sub>



```php
// --- Contract
public function getDefaultValueTypes(): string
// --- Usage
$methodObject->getDefaultValueTypes();
```

### - `shouldNotDocument` Function

<sub><sup>Returns true when this method should be excluded from generated documentation: private visibility, DontDocument attribute, or a non-constructor magic method. </sup></sub>



```php
// --- Contract
public function shouldNotDocument(): bool
// --- Usage
$methodObject->shouldNotDocument();
```

### - `isStaticInstantiator` Function

<sub><sup>Returns true when this is a public static method on a non-instantiable class that returns self, static, or the method&#039;s own name — identifying it as a static named constructor to be rendered as a &quot;How to use the Class&quot; snippet. </sup></sub>



```php
// --- Contract
public function isStaticInstantiator(): bool
// --- Usage
$methodObject->isStaticInstantiator();
```

### 🟥 InvalidVirtualMethodDefinitionException

<sub><sup>Thrown when a [at-sign]method PHPDoc tag cannot be parsed into a VirtualMethodObject. </sup></sub>



<sub><sup>Raised by VirtualMethodObject::parse() when the tag line is structurally invalid — for example when parentheses are missing or unbalanced. The Doc generator catches this exception and silently skips the malformed tag so a single bad [at-sign]method annotation does not abort the entire documentation generation run. </sup></sub>



### 🟢 VirtualMethodObject

<sub><sup>Parsed value object representing a virtual method declared via a [at-sign]method PHPDoc tag. </sup></sub>



<sub><sup>PHP classes can document methods that do not exist in source using [at-sign]method tags, typically on classes that use __call() magic. This class parses such a tag line into structured data (name, return type, parameters, static flag, description) so the documentation generator can render virtual methods alongside real ones. </sup></sub>



<sub><sup>Parse rules (applied by parse() during construction): - Leading &#039;[at-sign]method&#039; is stripped. - An optional &#039;static&#039; keyword sets isStatic = true. - An optional return-type token precedes the method name. - Parameters inside parentheses are extracted and further parsed by parseParams(). - Trailing text after the closing &#039;)&#039; is stored as the description. </sup></sub>



<sub><sup>Throws InvalidVirtualMethodDefinitionException when the line is malformed (missing parentheses, unbalanced brackets). </sup></sub>



#### How to use the Class:

```php
$virtualMethodObject = new VirtualMethodObject(ClassInterface $class, string $line);
```

### - `getClass` Function

<sub><sup>Returns the ClassInterface that declared this [at-sign]method tag. </sup></sub>



```php
// --- Contract
public function getClass(): ClassInterface
// --- Usage
$virtualMethodObject->getClass();
```

### - `getName` Function

<sub><sup>Returns the parsed method name. </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$virtualMethodObject->getName();
```

### - `isPrivate` Function

<sub><sup>Always returns false — virtual methods declared in PHPDoc are implicitly public. </sup></sub>



```php
// --- Contract
public function isPrivate(): bool
// --- Usage
$virtualMethodObject->isPrivate();
```

### - `isProtected` Function

<sub><sup>Always returns false — virtual methods declared in PHPDoc are implicitly public. </sup></sub>



```php
// --- Contract
public function isProtected(): bool
// --- Usage
$virtualMethodObject->isProtected();
```

### - `isPublic` Function

<sub><sup>Always returns true — virtual methods are public by convention. </sup></sub>



```php
// --- Contract
public function isPublic(): bool
// --- Usage
$virtualMethodObject->isPublic();
```

### - `isStatic` Function

<sub><sup>Returns true when the [at-sign]method tag included the &#039;static&#039; keyword. </sup></sub>



```php
// --- Contract
public function isStatic(): bool
// --- Usage
$virtualMethodObject->isStatic();
```

### - `isConstructor` Function

<sub><sup>Returns true when the parsed method name is &#039;__construct&#039;. </sup></sub>



```php
// --- Contract
public function isConstructor(): bool
// --- Usage
$virtualMethodObject->isConstructor();
```

### - `isMagicMethod` Function

<sub><sup>Returns true when the method name starts with &#039;__&#039;. </sup></sub>



```php
// --- Contract
public function isMagicMethod(): bool
// --- Usage
$virtualMethodObject->isMagicMethod();
```

### - `getMethodReturnTypes` Function

<sub><sup>Returns the parsed return type string, or null when none was declared. </sup></sub>



```php
// --- Contract
public function getMethodReturnTypes(): string
// --- Usage
$virtualMethodObject->getMethodReturnTypes();
```

### - `getRawDoc` Function

```php
// --- Contract
public function getRawDoc(): string
// --- Usage
$virtualMethodObject->getRawDoc();
```

### - `getPhpDocObject` Function

```php
// --- Contract
public function getPhpDocObject(): PhpDocObject
// --- Usage
$virtualMethodObject->getPhpDocObject();
```

### - `getModifiers` Function

<sub><sup>Returns the modifier names for this virtual method ([&#039;public&#039;] or [&#039;public&#039;, &#039;static&#039;] when declared static). </sup></sub>



<sub><sup>@return string[] </sup></sub>



```php
// --- Contract
public function getModifiers(): array
// --- Usage
$virtualMethodObject->getModifiers();
```

### - `isFrameworkFunction` Function

<sub><sup>Always returns false — virtual methods exist only in PHPDoc, not in framework source. </sup></sub>



```php
// --- Contract
public function isFrameworkFunction(): bool
// --- Usage
$virtualMethodObject->isFrameworkFunction();
```

### - `getDefaultValues` Function

<sub><sup>Returns a comma-separated string of the non-null default values parsed from the parameter list, used in call-site usage examples. </sup></sub>



```php
// --- Contract
public function getDefaultValues(): string
// --- Usage
$virtualMethodObject->getDefaultValues();
```

### - `getDefaultValueTypes` Function

<sub><sup>Returns a pipe-joined string of the non-null parameter types parsed from the parameter list, used in method contract examples. </sup></sub>



```php
// --- Contract
public function getDefaultValueTypes(): string
// --- Usage
$virtualMethodObject->getDefaultValueTypes();
```

### - `shouldNotDocument` Function

<sub><sup>Returns true when the description contains &#039;@internal&#039;, excluding the method from generated documentation. </sup></sub>



```php
// --- Contract
public function shouldNotDocument(): bool
// --- Usage
$virtualMethodObject->shouldNotDocument();
```

### - `isStaticInstantiator` Function

<sub><sup>Returns true when this virtual method is a public static named constructor (returns self, static, or its own name). </sup></sub>



```php
// --- Contract
public function isStaticInstantiator(): bool
// --- Usage
$virtualMethodObject->isStaticInstantiator();
```

## 📦 Sakoo\Framework\Core\Doc\Object

### 🟢 NamespaceObject

<sub><sup>Immutable value object grouping a namespace string with its ClassObject members. </sup></sub>



<sub><sup>Acts as a bag that the Doc generator populates during source-file introspection, grouping all documentable classes that share the same PHP namespace into one unit for formatters to iterate over. The namespace name is used as the section heading in generated documentation. </sup></sub>



#### How to use the Class:

```php
$namespaceObject = new NamespaceObject(string $namespace, array $classes);
```

### - `getClasses` Function

<sub><sup>Returns the ClassObject instances belonging to this namespace. </sup></sub>



<sub><sup>@return ClassObject[] </sup></sub>



```php
// --- Contract
public function getClasses(): array
// --- Usage
$namespaceObject->getClasses();
```

### - `getName` Function

<sub><sup>Returns the fully-qualified namespace string (e.g. &#039;Sakoo\Framework\Core\Set&#039;). </sup></sub>



```php
// --- Contract
public function getName(): string
// --- Usage
$namespaceObject->getName();
```

## 📦 Sakoo\Framework\Core\Commands

### 🟢 ContainerCacheCommand

<sub><sup>Console command for managing the container binding cache. </sup></sub>



<sub><sup>Provides two operations selected by the presence of the --clear option: </sup></sub>



<sub><sup>- Default (no --clear): serialises the current container bindings and singletons to a PHP cache file via ContainerInterface::dumpCache(), allowing subsequent boots to skip all reflection-based autowiring. - --clear: deletes the existing cache file via ContainerInterface::flushCache(), forcing the next boot to re-run all ServiceLoaders and rebuild the cache. </sup></sub>



<sub><sup>The container is injected via the constructor so the command operates on the same ContainerInterface instance that was used during the current boot cycle. </sup></sub>



#### How to use the Class:

```php
$containerCacheCommand = new ContainerCacheCommand(ContainerInterface $container);
```

### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;container:cache&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
ContainerCacheCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
ContainerCacheCommand::getDescription();
```

### - `run` Function

<sub><sup>Flushes the cache when --clear is present, otherwise dumps a fresh cache. Prints a success message and returns Output::SUCCESS in both cases. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$containerCacheCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$containerCacheCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$containerCacheCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$containerCacheCommand->getApplication();
```

### 🟢 ZenCommand

<sub><sup>Console command that displays the framework identity banner. </sup></sub>



<sub><sup>Prints a decorative ASCII art block in cyan followed by the framework name, current version in green, and a copyright line crediting the maintainer. Modelled after the &quot;Zen of Python&quot; easter egg — a lighthearted way to confirm that the framework is correctly installed and the CLI is functional. </sup></sub>



### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;zen&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
ZenCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
ZenCommand::getDescription();
```

### - `run` Function

<sub><sup>Renders the decorative banner, framework name, version, and copyright year. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$zenCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$zenCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$zenCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$zenCommand->getApplication();
```

### 🟢 DevCommand

<sub><sup>Console command that displays runtime developer diagnostics. </sup></sub>



<sub><sup>Currently reports OPcache JIT status (Enabled / Disabled / Unknown) by inspecting the opcache_get_status() result. Additional diagnostics can be appended here as the framework evolves without changing the command name or registration. </sup></sub>



<sub><sup>Intended as a quick sanity-check during local development and CI to confirm that performance-sensitive runtime extensions are active. </sup></sub>



### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;dev&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
DevCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
DevCommand::getDescription();
```

### - `run` Function

<sub><sup>Queries OPcache for JIT status and prints the result in green. Reports &#039;Unknown&#039; when OPcache is not loaded or its status is unavailable. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$devCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$devCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$devCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$devCommand->getApplication();
```

### 🟢 DocGenCommand

<sub><sup>Console command that generates Markdown API documentation from PHP source files. </sup></sub>



<sub><sup>Produces two output artefacts for wiki-style documentation hosting: </sup></sub>



<sub><sup>1. Main doc file ($docPath)     — full API reference rendered by DocFormatter, covering every undocumented public class and method found in the source tree. 2. Sidebar file ($sidebarPath)  — table-of-contents rendered by TocFormatter, suitable for use as a wiki _Sidebar.md navigation file. 3. Footer file ($footerPath)    — a one-line &quot;Powered by …&quot; attribution string written directly without a formatter. </sup></sub>



<sub><sup>The source tree to document defaults to the framework core directory but can be overridden by passing a path as the second positional CLI argument. </sup></sub>



<sub><sup>All three output paths are injected via the constructor so they can be configured per-project without subclassing. </sup></sub>



#### How to use the Class:

```php
$docGenCommand = new DocGenCommand(string $docPath, string $sidebarPath, string $footerPath);
```

### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;doc:gen&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
DocGenCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
DocGenCommand::getDescription();
```

### - `run` Function

<sub><sup>Discovers PHP files in the target directory, generates the main documentation and TOC sidebar via Doc, writes the footer attribution, then reports success. The optional second positional argument overrides the source directory path. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$docGenCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$docGenCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$docGenCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$docGenCommand->getApplication();
```

## 📦 Sakoo\Framework\Core\Commands\Watcher

### 🟢 PhpBundler

<sub><sup>FileSystemAction implementation that auto-formats PHP files on change. </sup></sub>



<sub><sup>Extends WatcherActions so it inherits the per-file Locker debounce guard in fileModified(). When a PHP file is modified: </sup></sub>



<sub><sup>1. The parent debounce check is applied — the event is dropped if the locker is already held (i.e. a previous format run for the same file is still in progress). 2. The changed file path is logged to the console output with a timestamp. 3. PHP CS Fixer is run silently on the changed file via exec(). 4. The locker is released so future MODIFY events for the same file are processed. 5. A &quot;Watching …&quot; status line is re-printed to confirm the watcher is still active. </sup></sub>



<sub><sup>fileMoved() and fileDeleted() are inherited as no-ops from WatcherActions. </sup></sub>



#### How to use the Class:

```php
$phpBundler = new PhpBundler(Input $input, Output $output);
```

### - `fileModified` Function

<sub><sup>Debounces, logs, lints, unlocks, and re-prints the watcher status for every detected MODIFY event on a watched PHP file. </sup></sub>



```php
// --- Contract
public function fileModified(Event $event): void
// --- Usage
$phpBundler->fileModified($event);
```

### - `fileMoved` Function

<sub><sup>Called when a watched file is moved or renamed. No-op by default. </sup></sub>



```php
// --- Contract
public function fileMoved(Event $event): void
// --- Usage
$phpBundler->fileMoved($event);
```

### - `fileDeleted` Function

<sub><sup>Called when a watched file is deleted. No-op by default. </sup></sub>



```php
// --- Contract
public function fileDeleted(Event $event): void
// --- Usage
$phpBundler->fileDeleted($event);
```

### 🟢 WatchCommand

<sub><sup>Console command that starts the filesystem watcher for PHP source files. </sup></sub>



<sub><sup>Registers all PHP files found under the project root with the injected Watcher, using a PhpBundler instance as the change handler, then enters the Watcher&#039;s infinite event loop. The command blocks indefinitely — it is intended to be run in a dedicated terminal window or supervised process during development. </sup></sub>



<sub><sup>On each detected file change PhpBundler runs PHP CS Fixer on the modified file and logs the event to the console output. </sup></sub>



#### How to use the Class:

```php
$watchCommand = new WatchCommand(Watcher $watcher);
```

### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;watch&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
WatchCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
WatchCommand::getDescription();
```

### - `run` Function

<sub><sup>Registers all project PHP files with the watcher, prints a &quot;Watching …&quot; status line, then enters the blocking event loop. Never returns under normal operation. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$watchCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$watchCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$watchCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$watchCommand->getApplication();
```

## 📦 Sakoo\Framework\Core\Profiler

### 🟢 Profiler

<sub><sup>Millisecond-precision profiler backed by a PSR-20 ClockInterface. </sup></sub>



<sub><sup>Timestamps are captured in Unix milliseconds (seconds × 1000 + milliseconds) by formatting the ClockInterface::now() result with the &#039;Uv&#039; format string, where &#039;U&#039; is Unix epoch seconds and &#039;v&#039; is milliseconds. This approach keeps the implementation fully deterministic in tests when the clock is pinned via Clock::setTestNow(). </sup></sub>



<sub><sup>Multiple concurrent timings can coexist by using distinct key strings. Keys are not validated — callers must ensure they call start() before elapsedTime() for any given key to avoid an undefined array key error. </sup></sub>



#### How to use the Class:

```php
$profiler = new Profiler(ClockInterface $clock);
```

### - `start` Function

<sub><sup>Records the current millisecond timestamp as the start time for $key. </sup></sub>



```php
// --- Contract
public function start(string $key): void
// --- Usage
$profiler->start($key);
```

### - `elapsedTime` Function

<sub><sup>Returns the number of milliseconds elapsed since start() was called for $key. </sup></sub>



```php
// --- Contract
public function elapsedTime(string $key): int
// --- Usage
$profiler->elapsedTime($key);
```

## 📦 Sakoo\Framework\Core\Set\Exceptions

### 🟥 GenericMismatchException

<sub><sup>Thrown when a value whose type does not match the Set&#039;s inferred generic type is added to or retrieved with a default from a Set instance. </sup></sub>



<sub><sup>Set&lt;T&gt; infers T from the first element inserted. All subsequent elements must share the same PHP gettype() result. This exception signals a violation of that contract, preventing silent type coercion inside collections. </sup></sub>



## 📦 Sakoo\Framework\Core\Set

### 🟢 Set

<sub><sup>Type-safe generic collection. </sup></sub>



<sub><sup>Set&lt;T&gt; infers the element type T from the first item inserted and rejects any subsequent item whose PHP gettype() differs from that inferred type, throwing GenericMismatchException. This makes the collection behave similarly to a typed generic in languages with native generics, preventing silent type coercion that would be possible with a plain PHP array. </sup></sub>



<sub><sup>Elements can be stored with either an explicit string/int key (associative) or appended without a key (sequential). Integer keys passed to get() and remove() are always treated as positional indices — not as literal array keys — so get(0) reliably returns the first element even in associative sets. </sup></sub>



<sub><sup>The ItemAccess trait adds named positional accessors (first(), second(), …, tenth()) for the most commonly accessed positions. </sup></sub>



<sub><sup>Sorting and searching are delegated to pluggable Sorter&lt;T&gt; and Searcher&lt;T&gt; strategy objects injected at the call site, keeping the Set algorithm-agnostic. </sup></sub>



<sub><sup>Magic property access (__get / __set) maps property names to associative keys, enabling object-style access on associative sets. </sup></sub>



<sub><sup>@template T </sup></sub>



<sub><sup>@implements IterableInterface&lt;T&gt; </sup></sub>



#### How to use the Class:

```php
$set = new Set(array $items);
```

### - `exists` Function

<sub><sup>Returns true when an element with the given int or string $name key exists. </sup></sub>



```php
// --- Contract
public function exists(string|int $name): bool
// --- Usage
$set->exists($name);
```

### - `count` Function

<sub><sup>Returns the number of elements in the collection. </sup></sub>



```php
// --- Contract
public function count(): int
// --- Usage
$set->count();
```

### - `each` Function

<sub><sup>Passes each element (value, key) to $callback. Return values are discarded. </sup></sub>



```php
// --- Contract
public function each(callable $callback): void
// --- Usage
$set->each($callback);
```

### - `map` Function

<sub><sup>Returns a new Set whose elements are the results of applying $callback to each element of this Set. The inferred generic type of the new Set is determined by the callback&#039;s return values. </sup></sub>



<sub><sup>@template U </sup></sub>



<sub><sup>@param callable(T): U $callback </sup></sub>



<sub><sup>@return Set&lt;U&gt; </sup></sub>



> @throws GenericMismatchException|\Throwable

```php
// --- Contract
public function map(callable $callback): self
// --- Usage
$set->map($callback);
```

### - `pluck` Function

<sub><sup>Extracts nested values using a dot-notation $key (e.g. &#039;address.city&#039;) from each element via successive array_column calls, and returns them as a new Set. </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



> @throws GenericMismatchException|\Throwable

```php
// --- Contract
public function pluck(string $key): self
// --- Usage
$set->pluck($key);
```

### - `add` Function

<sub><sup>Adds an element to the collection with type validation. </sup></sub>



<sub><sup>When only $key is provided (and $value is null), $key is treated as the value and appended sequentially. When both $key and $value are given, $value is stored under $key (which must be int or string). </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



> @throws GenericMismatchException|\Throwable

```php
// --- Contract
public function add(mixed $key, mixed $value): self
// --- Usage
$set->add($key, $value);
```

### - `remove` Function

<sub><sup>Removes the element at $key from the collection and returns the same instance. Integer $key is treated as a positional index; string $key is an associative key. Does nothing when the key does not exist. </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



```php
// --- Contract
public function remove(string|int $key): self
// --- Usage
$set->remove($key);
```

### - `get` Function

<sub><sup>Returns the element at $key, or $default when the key does not exist. </sup></sub>



<sub><sup>Integer $key is treated as a positional index (via array_slice), allowing consistent positional access even in associative sets. When $default is provided it must satisfy the generic type constraint. </sup></sub>



<sub><sup>@return null|T </sup></sub>



> @throws GenericMismatchException|\Throwable

```php
// --- Contract
public function get(string|int $key, mixed $default): mixed
// --- Usage
$set->get($key, $default);
```

### - `toArray` Function

<sub><sup>Returns all elements as a plain PHP array, preserving keys. </sup></sub>



<sub><sup>@return array&lt;T&gt; </sup></sub>



```php
// --- Contract
public function toArray(): array
// --- Usage
$set->toArray();
```

### - `getIterator` Function

<sub><sup>Returns an ArrayIterator over the internal items array, enabling foreach iteration and satisfying the IteratorAggregate contract. </sup></sub>



<sub><sup>@return \ArrayIterator&lt;int|string, T&gt; </sup></sub>



```php
// --- Contract
public function getIterator(): ArrayIterator
// --- Usage
$set->getIterator();
```

### - `sort` Function

<sub><sup>Delegates sorting to $sorter and returns a new Set with elements reordered according to the strategy&#039;s algorithm. </sup></sub>



<sub><sup>@param Sorter&lt;T&gt; $sorter </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



```php
// --- Contract
public function sort(Sorter $sorter): self
// --- Usage
$set->sort($sorter);
```

### - `search` Function

<sub><sup>Delegates searching to $searcher and returns a new Set containing only the elements that match $needle according to the strategy. </sup></sub>



<sub><sup>@param Searcher&lt;T&gt; $searcher </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



```php
// --- Contract
public function search(mixed $needle, Searcher $searcher): self
// --- Usage
$set->search($needle, $searcher);
```

### - `filter` Function

<sub><sup>Returns a new Set containing only the elements for which $callback returns true. The inferred generic type is preserved in the new Set. </sup></sub>



<sub><sup>@return Set&lt;T&gt; </sup></sub>



> @throws GenericMismatchException|\Throwable

```php
// --- Contract
public function filter(callable $callback): self
// --- Usage
$set->filter($callback);
```

### - `first` Function

<sub><sup>Returns the element at index 0, or null when the Set has fewer than one element.</sup></sub>

```php
// --- Contract
public function first(): mixed
// --- Usage
$set->first();
```

### - `second` Function

<sub><sup>Returns the element at index 1, or null when the Set has fewer than two elements.</sup></sub>

```php
// --- Contract
public function second(): mixed
// --- Usage
$set->second();
```

### - `third` Function

<sub><sup>Returns the element at index 2, or null when the Set has fewer than three elements.</sup></sub>

```php
// --- Contract
public function third(): mixed
// --- Usage
$set->third();
```

### - `fourth` Function

<sub><sup>Returns the element at index 3, or null when the Set has fewer than four elements.</sup></sub>

```php
// --- Contract
public function fourth(): mixed
// --- Usage
$set->fourth();
```

### - `fifth` Function

<sub><sup>Returns the element at index 4, or null when the Set has fewer than five elements.</sup></sub>

```php
// --- Contract
public function fifth(): mixed
// --- Usage
$set->fifth();
```

### - `sixth` Function

<sub><sup>Returns the element at index 5, or null when the Set has fewer than six elements.</sup></sub>

```php
// --- Contract
public function sixth(): mixed
// --- Usage
$set->sixth();
```

### - `seventh` Function

<sub><sup>Returns the element at index 6, or null when the Set has fewer than seven elements.</sup></sub>

```php
// --- Contract
public function seventh(): mixed
// --- Usage
$set->seventh();
```

### - `eighth` Function

<sub><sup>Returns the element at index 7, or null when the Set has fewer than eight elements.</sup></sub>

```php
// --- Contract
public function eighth(): mixed
// --- Usage
$set->eighth();
```

### - `ninth` Function

<sub><sup>Returns the element at index 8, or null when the Set has fewer than nine elements.</sup></sub>

```php
// --- Contract
public function ninth(): mixed
// --- Usage
$set->ninth();
```

### - `tenth` Function

<sub><sup>Returns the element at index 9, or null when the Set has fewer than ten elements.</sup></sub>

```php
// --- Contract
public function tenth(): mixed
// --- Usage
$set->tenth();
```

## 📦 Sakoo\Framework\Core\Exception

### 🟢 Exception

<sub><sup>Base exception class for the Sakoo Framework. </sup></sub>



<sub><sup>All domain-specific and infrastructure exceptions in the framework extend this class, providing a common ancestor for framework-thrown exceptions and enabling catch-all handling at application boundaries without catching PHP&#039;s base Exception directly. </sup></sub>



## 📦 Sakoo\Framework\Core\Str

### 🟢 Str

<sub><sup>Fluent, chainable multibyte string manipulation class. </sup></sub>



<sub><sup>Wraps a plain PHP string and exposes a rich API for common transformations (case conversion, slug/camelCase/snakeCase/kebabCase generation, trimming, searching, replacing, and reversing). Every mutating method modifies the internal value and returns the same instance so calls can be chained freely. </sup></sub>



<sub><sup>The class also implements PHP&#039;s native Stringable contract, meaning any Str instance can be cast to a plain string with (string) or embedded directly in string interpolation without calling get() explicitly. </sup></sub>



<sub><sup>The static factory fromType() provides a human-readable string representation of any PHP value — useful for debug output and assertion messages — without exposing raw var_export or print_r output. </sup></sub>



#### How to use the Class:

```php
$str = new Str(string $value);
```

### - `length` Function

<sub><sup>Returns the number of characters using multibyte-safe counting (mb_strlen). </sup></sub>



```php
// --- Contract
public function length(): int
// --- Usage
$str->length();
```

### - `uppercaseWords` Function

<sub><sup>Capitalises the first letter of every word in the string (ucwords). </sup></sub>



```php
// --- Contract
public function uppercaseWords(): static
// --- Usage
$str->uppercaseWords();
```

### - `uppercase` Function

<sub><sup>Converts the entire string to uppercase using mb_strtoupper. </sup></sub>



```php
// --- Contract
public function uppercase(): static
// --- Usage
$str->uppercase();
```

### - `lowercase` Function

<sub><sup>Converts the entire string to lowercase using mb_strtolower. </sup></sub>



```php
// --- Contract
public function lowercase(): static
// --- Usage
$str->lowercase();
```

### - `upperFirst` Function

<sub><sup>Capitalises only the first character of the string (ucfirst). </sup></sub>



```php
// --- Contract
public function upperFirst(): static
// --- Usage
$str->upperFirst();
```

### - `lowerFirst` Function

<sub><sup>Lowercases only the first character of the string (lcfirst). </sup></sub>



```php
// --- Contract
public function lowerFirst(): static
// --- Usage
$str->lowerFirst();
```

### - `reverse` Function

<sub><sup>Reverses the byte order of the string (strrev). Note: not multibyte-safe for characters encoded in more than one byte. </sup></sub>



```php
// --- Contract
public function reverse(): static
// --- Usage
$str->reverse();
```

### - `contains` Function

<sub><sup>Returns true when the string contains the given substring, false otherwise. </sup></sub>



```php
// --- Contract
public function contains(string $substring): bool
// --- Usage
$str->contains($substring);
```

### - `replace` Function

<sub><sup>Replaces every occurrence of $search with $replace (str_replace). </sup></sub>



```php
// --- Contract
public function replace(string $search, string $replace): static
// --- Usage
$str->replace($search, $replace);
```

### - `trim` Function

<sub><sup>Strips leading and trailing ASCII whitespace (trim). </sup></sub>



```php
// --- Contract
public function trim(): static
// --- Usage
$str->trim();
```

### - `slug` Function

<sub><sup>Produces a URL-friendly slug from the current value. </sup></sub>



<sub><sup>The algorithm splits camelCase boundaries, replaces all special characters with spaces, collapses runs of spaces into a single hyphen, trims the result, and finally lowercases everything. Equivalent to kebabCase(). </sup></sub>



```php
// --- Contract
public function slug(): static
// --- Usage
$str->slug();
```

### - `camelCase` Function

<sub><sup>Converts the string to camelCase. </sup></sub>



<sub><sup>Splits on camelCase boundaries and special characters, lowercases all words, capitalises each word&#039;s first letter, removes all spaces, then lowercases the very first character of the resulting compound word. </sup></sub>



```php
// --- Contract
public function camelCase(): static
// --- Usage
$str->camelCase();
```

### - `snakeCase` Function

<sub><sup>Converts the string to snake_case. </sup></sub>



<sub><sup>Splits on camelCase boundaries and special characters, replaces word separating spaces with underscores, trims, and lowercases the result. </sup></sub>



```php
// --- Contract
public function snakeCase(): static
// --- Usage
$str->snakeCase();
```

### - `kebabCase` Function

<sub><sup>Converts the string to kebab-case by delegating to slug(). </sup></sub>



```php
// --- Contract
public function kebabCase(): static
// --- Usage
$str->kebabCase();
```

### - `get` Function

<sub><sup>Returns the raw underlying string value. </sup></sub>



```php
// --- Contract
public function get(): string
// --- Usage
$str->get();
```

### - `fromType` Function

<sub><sup>Creates a human-readable Str representation of any PHP value. </sup></sub>



<sub><sup>The representation is intentionally safe for logging and assertion messages: null becomes &#039;NULL&#039;, booleans become &#039;true&#039;/&#039;false&#039;, callables include their object hash, objects include their class hash, arrays include their count, and all scalar values are cast via strval(). </sup></sub>



```php
// --- Contract
public static function fromType(mixed $value): self
// --- Usage
Str::fromType($value);
```

## 📦 Sakoo\Framework\Core\Kernel

### 🟢 Mode

<sub><sup>Enumerates the runtime modes in which the Sakoo kernel can operate. </sup></sub>



<sub><sup>The mode determines which parts of the framework are active and influences several framework-level behaviours: </sup></sub>



<sub><sup>- Test     — activated by the test runner; enables Clock::setTestNow(), routes log output to the temporary test directory, and turns on display_errors. - Console  — activated when the application is invoked from the CLI (e.g. running console commands or queue workers). - HTTP     — activated when serving web requests; the standard production mode for handling incoming HTTP traffic. </sup></sub>



### - `cases` Function

```php
// --- Contract
public static function cases(): array
// --- Usage
Mode::cases();
```

#### How to use the Class:

```php
$mode = Mode::from(string|int $value);
```

#### How to use the Class:

```php
$mode = Mode::tryFrom(string|int $value);
```

### 🟢 Environment

<sub><sup>Enumerates the deployment environments recognised by the Sakoo kernel. </sup></sub>



<sub><sup>The environment controls diagnostic output and error visibility: </sup></sub>



<sub><sup>- Debug      — intended for local development; enables display_errors and display_startup_errors so exceptions are visible in the browser or terminal without requiring a log viewer. - Production — intended for live deployments; suppresses raw error output to prevent leaking implementation details to end users. All errors should be captured by structured logging instead. </sup></sub>



### - `cases` Function

```php
// --- Contract
public static function cases(): array
// --- Usage
Environment::cases();
```

#### How to use the Class:

```php
$environment = Environment::from(string|int $value);
```

#### How to use the Class:

```php
$environment = Environment::tryFrom(string|int $value);
```

### 🟢 Kernel

<sub><sup>Process-scoped application kernel and bootstrap coordinator. </sup></sub>



<sub><sup>The Kernel is the single authoritative owner of the container, profiler, mode, and environment for the lifetime of one PHP process. It is a strict singleton: prepare() must be called exactly once (further calls throw KernelTwiceCallException) and getInstance() must only be called after prepare() and run() have completed (earlier calls throw KernelIsNotStartedException). </sup></sub>



<sub><sup>Boot sequence: 1. prepare() — creates the singleton instance, capturing Mode and Environment. 2. (optional setters) — configure timezone, error/exception handlers, service loaders, and replica ID before run() is called. 3. run() — applies timezone and error settings, loads the helpers file, initialises the Container, populates it from a cache file when available or from the registered ServiceLoaders otherwise, then resolves the Profiler. </sup></sub>



<sub><sup>After run() returns the kernel is fully operational and all application code may call kernel(), container(), resolve(), and the other global helpers freely. </sup></sub>



<sub><sup>Error and exception handlers are registered only when non-null values have been provided via setErrorHandler() / setExceptionHandler(). Display errors are enabled automatically in Test and Debug environments. </sup></sub>



<sub><sup>In horizontal scaling scenarios each process replica should be assigned a unique identifier via setReplicaId() so log correlation and distributed tracing remain meaningful across instances. </sup></sub>



#### How to use the Class:

```php
$kernel = Kernel::prepare(Mode $mode, Environment $environment);
```

#### How to use the Class:

```php
$kernel = Kernel::getInstance();
```

### - `run` Function

<sub><sup>Executes the full boot sequence: applies the configured timezone, registers error and exception handlers, enables display_errors in debug/test environments, loads the global helpers file, initialises the Container with the storage path as its cache directory, populates bindings from the cache (when available) or from ServiceLoaders, and finally resolves the ProfilerInterface from the container. </sup></sub>



```php
// --- Contract
public function run(): void
// --- Usage
$kernel->run();
```

### - `getMode` Function

<sub><sup>Returns the current runtime Mode (Test, Console, or HTTP). </sup></sub>



```php
// --- Contract
public function getMode(): Mode
// --- Usage
$kernel->getMode();
```

### - `getEnvironment` Function

<sub><sup>Returns the current deployment Environment (Debug or Production). </sup></sub>



```php
// --- Contract
public function getEnvironment(): Environment
// --- Usage
$kernel->getEnvironment();
```

### - `getProfiler` Function

<sub><sup>Returns the resolved ProfilerInterface instance. Only available after run(). </sup></sub>



```php
// --- Contract
public function getProfiler(): ProfilerInterface
// --- Usage
$kernel->getProfiler();
```

### - `getContainer` Function

<sub><sup>Returns the ContainerInterface instance. Only available after run(). </sup></sub>



```php
// --- Contract
public function getContainer(): ContainerInterface
// --- Usage
$kernel->getContainer();
```

### - `getReplicaId` Function

<sub><sup>Returns the replica identifier assigned to this process instance, or an empty string when no replica ID has been configured. </sup></sub>



```php
// --- Contract
public function getReplicaId(): string
// --- Usage
$kernel->getReplicaId();
```

### - `setExceptionHandler` Function

<sub><sup>Registers a callable to be used as the PHP exception handler via set_exception_handler() during run(). Returns the same Kernel instance for fluent configuration chaining before run() is called. </sup></sub>



```php
// --- Contract
public function setExceptionHandler(callable $handler): static
// --- Usage
$kernel->setExceptionHandler($handler);
```

### - `setErrorHandler` Function

<sub><sup>Registers a callable to be used as the PHP error handler via set_error_handler() during run(). Returns the same Kernel instance for fluent configuration chaining before run() is called. </sup></sub>



```php
// --- Contract
public function setErrorHandler(callable $handler): static
// --- Usage
$kernel->setErrorHandler($handler);
```

### - `setServerTimezone` Function

<sub><sup>Sets the server timezone applied via date_default_timezone_set() during run(). Has no effect when called after run(). </sup></sub>



```php
// --- Contract
public function setServerTimezone(string $timezone): static
// --- Usage
$kernel->setServerTimezone($timezone);
```

### - `setServiceLoaders` Function

<sub><sup>Registers the list of ServiceLoader class names to be instantiated and invoked during run() when no container cache is present. Must be called before run(). </sup></sub>



<sub><sup>@param array&lt;ServiceLoader&gt; $serviceLoaders </sup></sub>



```php
// --- Contract
public function setServiceLoaders(array $serviceLoaders): static
// --- Usage
$kernel->setServiceLoaders($serviceLoaders);
```

### - `setReplicaId` Function

<sub><sup>Assigns a unique identifier to this process replica. Used for log correlation and distributed tracing in horizontally scaled deployments. </sup></sub>



```php
// --- Contract
public function setReplicaId(string $replicaId): static
// --- Usage
$kernel->setReplicaId($replicaId);
```

### - `isInTestMode` Function

<sub><sup>Returns true when the kernel is running in Test mode. </sup></sub>



```php
// --- Contract
public function isInTestMode(): bool
// --- Usage
$kernel->isInTestMode();
```

### - `isInHttpMode` Function

<sub><sup>Returns true when the kernel is running in HTTP mode. </sup></sub>



```php
// --- Contract
public function isInHttpMode(): bool
// --- Usage
$kernel->isInHttpMode();
```

### - `isInConsoleMode` Function

<sub><sup>Returns true when the kernel is running in Console mode. </sup></sub>



```php
// --- Contract
public function isInConsoleMode(): bool
// --- Usage
$kernel->isInConsoleMode();
```

### - `isInDebugEnv` Function

<sub><sup>Returns true when the kernel is configured for the Debug environment. </sup></sub>



```php
// --- Contract
public function isInDebugEnv(): bool
// --- Usage
$kernel->isInDebugEnv();
```

### - `isInProductionEnv` Function

<sub><sup>Returns true when the kernel is configured for the Production environment. </sup></sub>



```php
// --- Contract
public function isInProductionEnv(): bool
// --- Usage
$kernel->isInProductionEnv();
```

## 📦 Sakoo\Framework\Core\Kernel\Exceptions

### 🟥 KernelTwiceCallException

<sub><sup>Thrown when Kernel::prepare() is called a second time within the same process. </sup></sub>



<sub><sup>The Kernel is a process-scoped singleton; instantiating it more than once would produce two separate container and profiler instances, breaking the assumption that there is exactly one authoritative service registry per process. This exception guards against accidental double-initialisation in integration tests, long-running Swoole workers, or any other scenario where boot code might be executed more than once. </sup></sub>



### 🟥 KernelIsNotStartedException

<sub><sup>Thrown when kernel() or Kernel::getInstance() is called before Kernel::prepare() and Kernel::run() have completed. </sup></sub>



<sub><sup>Any code that resolves the kernel or the container during the bootstrap phase — before the singleton instance is populated — will receive this exception rather than a silent null-dereference. It serves as an explicit signal that the application boot order has been violated. </sup></sub>



## 📦 Sakoo\Framework\Core\Kernel\Handlers

### 🟢 ExceptionHandler

<sub><sup>Default PHP exception handler for the Sakoo kernel. </sup></sub>



<sub><sup>Registered via set_exception_handler() during Kernel::run() when the host application does not supply a custom handler. On invocation it prints the five innermost stack frames (without arguments, to avoid leaking sensitive data) and re-throws the original exception so the full stack trace is ultimately surfaced by PHP&#039;s fatal-error mechanism or a higher-level error reporter. </sup></sub>



<sub><sup>Re-throwing rather than calling exit() preserves the original exception type and message for logging infrastructure that wraps the handler chain. </sup></sub>



### 🟢 ErrorHandler

<sub><sup>Default PHP error handler for the Sakoo kernel. </sup></sub>



<sub><sup>Registered via set_error_handler() during Kernel::run() when the host application does not supply a custom handler. On invocation it prints the five innermost stack frames (without arguments, to avoid leaking sensitive data) and terminates the process with a formatted error summary containing the error code, message, file, and line number. </sup></sub>



<sub><sup>Because it calls exit() the handler satisfies the never return type and prevents PHP from continuing execution after a non-fatal error that has been escalated to this level. </sup></sub>



## 📦 Sakoo\Framework\Core\Clock\Exceptions

### 🟥 ClockTestModeException

<sub><sup>Thrown when Clock::setTestNow() is called outside of test mode. </sup></sub>



<sub><sup>Overriding the current time is a test-only capability; calling it in any other kernel mode would introduce non-deterministic behaviour in production or console runs. This exception acts as an explicit guard against accidental misuse. </sup></sub>



## 📦 Sakoo\Framework\Core\Clock

### 🟢 Clock

<sub><sup>PSR-20 ClockInterface implementation with test-time override support. </sup></sub>



<sub><sup>In production and console modes, now() always returns the real current instant as an immutable DateTimeImmutable. In test mode, the static setTestNow() method allows tests to pin the clock to any date-time string accepted by the DateTimeImmutable constructor, making time-dependent logic fully deterministic without monkey-patching or mocking. </sup></sub>



<sub><sup>The test-time override is stored as a static string so a single call to setTestNow() affects all Clock instances resolved from the container during the same test run. Resetting to &#039;now&#039; (the default) restores real-time behaviour. </sup></sub>



<sub><sup>Clock should always be injected via the ClockInterface PSR-20 contract rather than instantiated directly, ensuring the concrete implementation can be swapped or decorated without changing call sites. </sup></sub>



### - `setTestNow` Function

<sub><sup>Pins the clock to a specific date-time string for the duration of a test. Calling setTestNow(&#039;now&#039;) resets it back to real-time behaviour. </sup></sub>



<sub><sup>Only callable when the kernel is running in test mode; throws otherwise to prevent accidental time manipulation in non-test environments. </sup></sub>



> @throws ClockTestModeException|\Throwable

```php
// --- Contract
public static function setTestNow(string $datetime): void
// --- Usage
Clock::setTestNow($datetime);
```

### - `now` Function

<sub><sup>Returns the current instant as a DateTimeImmutable. </sup></sub>



<sub><sup>In normal operation this is the real system time. When setTestNow() has been called in a test, the pinned date-time string is used instead. </sup></sub>



> @throws \Exception

```php
// --- Contract
public function now(): DateTimeImmutable
// --- Usage
$clock->now();
```

## 📦 Sakoo\Framework\Core\ServiceLoader

### 🟢 VarDumpLoader

<sub><sup>Service loader that registers the appropriate VarDump driver for the current mode. </sup></sub>



<sub><sup>Selects between two rendering stacks depending on whether the kernel is running in HTTP mode or in a CLI/test context: </sup></sub>



<sub><sup>- HTTP mode  → HttpDumper + HttpFormatter (HTML output into the response body) - CLI/Test   → CliDumper  + CliFormatter  (ANSI-coloured output to the terminal) </sup></sub>



<sub><sup>Both Dumper and Formatter are registered as singletons because there is only ever one active output channel per process and constructing new formatters on every dump() call would be wasteful. </sup></sub>



### - `load` Function

<sub><sup>Registers the Dumper and Formatter singletons appropriate for the current kernel mode into $container. </sup></sub>



```php
// --- Contract
public function load(Container $container): void
// --- Usage
$varDumpLoader->load($container);
```

### 🟢 MainLoader

<sub><sup>Core service loader that registers the primary framework bindings. </sup></sub>



<sub><sup>Wires the essential interfaces to their default implementations: </sup></sub>



<sub><sup>- LoggerInterface  → FileLogger    (singleton — one logger per process) - Markup           → Markdown      (transient — stateless renderer) - ClockInterface   → Clock         (transient — PSR-20 clock) - Stringable       → Str           (transient — fluent string wrapper) - ProfilerInterface→ Profiler      (transient — millisecond profiler) - ContainerInterface→ Container    (transient — self-reference for code that needs the container via its interface) </sup></sub>



<sub><sup>Loaded by Kernel::run() as part of the default Loaders list. Application-level service loaders may override these bindings by registering after MainLoader. </sup></sub>



### - `load` Function

<sub><sup>Registers the core framework interface-to-implementation bindings into $container. </sup></sub>



```php
// --- Contract
public function load(Container $container): void
// --- Usage
$mainLoader->load($container);
```

### 🟢 WatcherLoader

<sub><sup>Service loader that registers the filesystem watcher bindings. </sup></sub>



<sub><sup>Wires the three watcher contracts to their Linux inotify-backed implementations: </sup></sub>



<sub><sup>- FileSystemAction → PhpBundler  (action executed on every detected file change) - WatcherDriver    → Inotify     (inotify-based filesystem event driver) - File             → InotifyFile (value object representing a watched file event) </sup></sub>



<sub><sup>All three are registered as transient bindings because each WatchCommand invocation creates its own watcher lifecycle and shares no state across runs. </sup></sub>



### - `load` Function

<sub><sup>Registers the watcher interface-to-implementation bindings into $container. </sup></sub>



```php
// --- Contract
public function load(Container $container): void
// --- Usage
$watcherLoader->load($container);
```

## 📦 Sakoo\Framework\Core\Console

### 🟢 Application

<sub><sup>Console application dispatcher. </sup></sub>



<sub><sup>Manages a registry of Command instances and dispatches each CLI invocation to the correct command based on the first positional argument. Three built-in commands are always available and take precedence over registered commands: </sup></sub>



<sub><sup>- VersionCommand — invoked when --version / -v is passed or the argument is &#039;version&#039;. - HelpCommand    — invoked when --help / -h is passed, the argument is &#039;help&#039;, or no argument is provided and no default command has been set. - NotFoundCommand — invoked when the requested argument does not match any registered command. </sup></sub>



<sub><sup>A default command can be configured via setDefaultCommand(); it is executed when the user provides no positional argument and the application has a known fallback. </sup></sub>



<sub><sup>When --help / -h is detected regardless of the resolved command, the command&#039;s help() method is called instead of run(), allowing every command to expose usage documentation through a consistent interface. </sup></sub>



#### How to use the Class:

```php
$application = new Application(Input $input, Output $output);
```

### - `run` Function

<sub><sup>Resolves the command that should execute for the current CLI invocation, then calls either help() (when --help / -h is present) or run(). Returns the command&#039;s exit code. </sup></sub>



```php
// --- Contract
public function run(): int
// --- Usage
$application->run();
```

### - `addCommands` Function

<sub><sup>Registers multiple commands at once, delegating to addCommand() for each. </sup></sub>



<sub><sup>@param Command[] $commands </sup></sub>



```php
// --- Contract
public function addCommands(array $commands): void
// --- Usage
$application->addCommands($commands);
```

### - `addCommand` Function

<sub><sup>Registers a single command in the dispatch registry, keyed by its static getName() value, and injects the running application reference into it. </sup></sub>



```php
// --- Contract
public function addCommand(Command $command): void
// --- Usage
$application->addCommand($command);
```

### - `setDefaultCommand` Function

<sub><sup>Sets the command to execute when no positional argument is given. The command must already be registered via addCommand(). </sup></sub>



<sub><sup>@param class-string&lt;Command&gt; $command </sup></sub>



> @throws \Throwable when the command has not been registered

```php
// --- Contract
public function setDefaultCommand(string $command): void
// --- Usage
$application->setDefaultCommand($command);
```

### - `getCommands` Function

<sub><sup>Returns all registered commands keyed by their name. </sup></sub>



<sub><sup>@return Command[] </sup></sub>



```php
// --- Contract
public function getCommands(): array
// --- Usage
$application->getCommands();
```

### 🟢 Output

<sub><sup>ANSI-aware console output writer. </sup></sub>



<sub><sup>Wraps PHP&#039;s echo with optional ANSI escape-code formatting for foreground colour, background colour, and text style. The Output auto-detects whether the terminal supports ANSI colours at construction time; when colour support is absent (e.g. Windows without ANSICON, non-TTY pipes), formatText() returns plain strings with no escape codes so output remains readable. </sup></sub>



<sub><sup>Every write goes through the internal $buffer array so tests can retrieve all output via getBuffer() / getDisplay() without capturing stdout. Silent mode suppresses all echo calls while still populating the buffer, enabling assertions in unit tests without console noise. </sup></sub>



<sub><sup>Constants are grouped into three namespaces: - SUCCESS / ERROR          — standard process exit codes. - STYLE_*                  — ANSI SGR style codes (bold, underline, blink, reverse). - COLOR_* / BG_*           — ANSI foreground and background colour codes. </sup></sub>



#### How to use the Class:

```php
$output = new Output(bool $forceColors);
```

### - `newLine` Function

<sub><sup>Writes two consecutive newlines to produce a blank line in the output. </sup></sub>



```php
// --- Contract
public function newLine(): void
// --- Usage
$output->newLine();
```

### - `write` Function

<sub><sup>Writes $message to stdout (unless silent mode is active) and appends it to the internal buffer. </sup></sub>



```php
// --- Contract
public function write(string $message): void
// --- Usage
$output->write($message);
```

### - `text` Function

<sub><sup>Formats $message with optional ANSI codes and writes it without a trailing newline. Useful for inline prompts or progress indicators. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function text(array|string $message, int $foreground, int $background, int $style): void
// --- Usage
$output->text($message, $foreground, $background, $style);
```

### - `block` Function

<sub><sup>Formats $message with optional ANSI codes and writes it followed by a trailing newline. This is the standard method for printing a complete line of output. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function block(array|string $message, int $foreground, int $background, int $style): void
// --- Usage
$output->block($message, $foreground, $background, $style);
```

### - `success` Function

<sub><sup>Writes $message in bold green, indicating a successful outcome. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function success(array|string $message): void
// --- Usage
$output->success($message);
```

### - `info` Function

<sub><sup>Writes $message in bold blue, indicating an informational notice. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function info(array|string $message): void
// --- Usage
$output->info($message);
```

### - `warning` Function

<sub><sup>Writes $message in bold yellow, indicating a non-fatal warning. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function warning(array|string $message): void
// --- Usage
$output->warning($message);
```

### - `error` Function

<sub><sup>Writes $message in bold red, indicating a fatal error or failure. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function error(array|string $message): void
// --- Usage
$output->error($message);
```

### - `setSilentMode` Function

<sub><sup>Enables or disables silent mode. When active, write() populates the buffer but suppresses all echo output. Useful for capturing output in tests. </sup></sub>



```php
// --- Contract
public function setSilentMode(bool $isSilentMode): void
// --- Usage
$output->setSilentMode($isSilentMode);
```

### - `supportsColors` Function

<sub><sup>Returns true when the current environment supports ANSI colour codes. </sup></sub>



```php
// --- Contract
public function supportsColors(): bool
// --- Usage
$output->supportsColors();
```

### - `getBuffer` Function

<sub><sup>Returns all strings written since the Output was constructed. </sup></sub>



<sub><sup>@return list&lt;string&gt; </sup></sub>



```php
// --- Contract
public function getBuffer(): array
// --- Usage
$output->getBuffer();
```

### - `getDisplay` Function

<sub><sup>Returns the entire buffered output as a single concatenated string, equivalent to imploding getBuffer() with an empty separator. </sup></sub>



```php
// --- Contract
public function getDisplay(): string
// --- Usage
$output->getDisplay();
```

### - `formatText` Function

<sub><sup>Wraps $message in ANSI SGR escape codes for the given foreground colour, background colour, and text style. Returns the plain message when the terminal does not support colours or no formatting parameters are given. Arrays are joined with PHP_EOL before formatting. </sup></sub>



<sub><sup>@param list&lt;string&gt;|string $message </sup></sub>



```php
// --- Contract
public function formatText(array|string $message, int $foreground, int $background, int $style): string
// --- Usage
$output->formatText($message, $foreground, $background, $style);
```

### 🟢 Input

<sub><sup>Parses and exposes CLI arguments and options for a console command. </sup></sub>



<sub><sup>On construction the raw $argv array is parsed into two separate collections: </sup></sub>



<sub><sup>- Arguments — positional values with no leading dashes, stored by zero-based integer index in the order they appear on the command line. - Options   — named flags prefixed with &#039;--&#039; (long) or &#039;-&#039; (short): - Long options:  &#039;--name=value&#039; stores key &#039;name&#039; with &#039;value&#039;; &#039;--flag&#039; stores key &#039;flag&#039; with &#039;true&#039;. - Short options: &#039;-x&#039; stores key &#039;x&#039; with &#039;true&#039;. Values are not supported for short options. </sup></sub>



<sub><sup>When no $args array is passed to the constructor, $_SERVER[&#039;argv&#039;] is used automatically and the script name (argv[0]) is stripped, matching standard PHP CLI behaviour. </sup></sub>



#### How to use the Class:

```php
$input = new Input(array $args);
```

### - `getArguments` Function

<sub><sup>Returns all positional arguments indexed by their zero-based position. </sup></sub>



<sub><sup>@return array&lt;string&gt; </sup></sub>



```php
// --- Contract
public function getArguments(): array
// --- Usage
$input->getArguments();
```

### - `getArgument` Function

<sub><sup>Returns the positional argument at $position, or null when the position does not exist. </sup></sub>



```php
// --- Contract
public function getArgument(int $position): string
// --- Usage
$input->getArgument($position);
```

### - `getOptions` Function

<sub><sup>Returns all parsed options as an associative array of name → value strings. </sup></sub>



<sub><sup>@return array&lt;string&gt; </sup></sub>



```php
// --- Contract
public function getOptions(): array
// --- Usage
$input->getOptions();
```

### - `hasOption` Function

<sub><sup>Returns true when an option with the given $name was present on the command line, false otherwise. </sup></sub>



```php
// --- Contract
public function hasOption(string $name): bool
// --- Usage
$input->hasOption($name);
```

### - `getOption` Function

<sub><sup>Returns the value of the named option, or null when the option was not provided. Boolean flags store the string &#039;true&#039; as their value. </sup></sub>



```php
// --- Contract
public function getOption(string $name): string
// --- Usage
$input->getOption($name);
```

### - `getUserInput` Function

<sub><sup>Reads a line of text from the terminal (via readline) and returns it. Returns an empty string when readline produces no input. </sup></sub>



```php
// --- Contract
public function getUserInput(): string
// --- Usage
$input->getUserInput();
```

### - `radio` Function

<sub><sup>Displays an interactive radio-button selection prompt with the given $options and $title, and returns the option chosen by the user. </sup></sub>



<sub><sup>@param string[] $options </sup></sub>



```php
// --- Contract
public function radio(array $options, string $title): string
// --- Usage
$input->radio($options, $title);
```

## 📦 Sakoo\Framework\Core\Console\Exceptions

### 🟥 CommandNotFoundException

<sub><sup>Thrown when a command name cannot be found in the Application&#039;s registry. </sup></sub>



<sub><sup>Raised by Application::setDefaultCommand() when the provided class has not been registered via addCommand() first, preventing an undefined-key error at dispatch time. </sup></sub>



## 📦 Sakoo\Framework\Core\Console\Components

### 🟢 RadioButton

<sub><sup>Interactive terminal radio-button selection component. </sup></sub>



<sub><sup>Presents a list of options to the user and returns the one they select. Two rendering modes are supported: </sup></sub>



<sub><sup>- Interactive mode — used when running in a real TTY with stty available. Renders a live-updating list using ANSI cursor control; the user navigates with ↑/↓ arrow keys or j/k, selects with Enter, and cancels with Esc or Ctrl-C. Numeric keys 1–N jump directly to that option. </sup></sub>



<sub><sup>- Fallback mode — used in non-interactive environments (pipes, CI, Windows). Prints a numbered list and prompts for a numeric input, re-prompting on invalid input until a valid selection is made. </sup></sub>



<sub><sup>Terminal raw mode is managed via stty: the original settings are captured before entering interactive mode and always restored in a finally block, even if an exception is thrown, to prevent leaving the terminal in a broken state. </sup></sub>



#### How to use the Class:

```php
$radioButton = new RadioButton(string $prompt, array $options);
```

### - `show` Function

<sub><sup>Displays the radio-button prompt and blocks until the user makes a selection. Uses interactive mode when a real TTY with stty is available, otherwise falls back to a numbered list prompt. Returns the selected option string. </sup></sub>



```php
// --- Contract
public function show(): string
// --- Usage
$radioButton->show();
```

## 📦 Sakoo\Framework\Core\Console\Commands

### 🟢 HelpCommand

<sub><sup>Built-in command that lists all registered commands and their descriptions. </sup></sub>



<sub><sup>Invoked automatically by Application when the user passes --help / -h with no specific command, types &#039;help&#039; as the first argument, or provides no argument when no default command is configured. Iterates the Application&#039;s command registry and prints each command&#039;s name (in bold green) and description (in white) as a formatted block. </sup></sub>



### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;help&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
HelpCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
HelpCommand::getDescription();
```

### - `run` Function

<sub><sup>Prints all registered commands with their names and descriptions, then returns Output::SUCCESS. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$helpCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$helpCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$helpCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$helpCommand->getApplication();
```

### 🟢 VersionCommand

<sub><sup>Built-in command that prints the framework name and version string. </sup></sub>



<sub><sup>Invoked automatically by Application when the user passes --version / -v or types &#039;version&#039; as the first argument. Reads the framework identity from the Constants class so the displayed string always reflects the current release without being duplicated in multiple places. </sup></sub>



### - `getName` Function

<sub><sup>Returns the CLI argument name &#039;version&#039; used to invoke this command. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
VersionCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
VersionCommand::getDescription();
```

### - `run` Function

<sub><sup>Prints the framework name and version in green, then returns Output::SUCCESS. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$versionCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$versionCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$versionCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$versionCommand->getApplication();
```

### 🟢 NotFoundCommand

<sub><sup>Built-in fallback command executed when the requested command name is not registered. </sup></sub>



<sub><sup>Application resolves this command whenever the first positional argument does not match any entry in the command registry and is not a built-in trigger (help, version). It prints an error message in red and a usage hint in green, then returns Output::ERROR to signal a non-zero exit code to the shell. </sup></sub>



### - `getName` Function

<sub><sup>Returns the internal name &#039;not-found&#039;. This command is never invoked by the user directly — it is selected automatically by Application as the fallback. </sup></sub>



```php
// --- Contract
public static function getName(): string
// --- Usage
NotFoundCommand::getName();
```

### - `getDescription` Function

<sub><sup>Returns a single-line description of this command for help listings. </sup></sub>



```php
// --- Contract
public static function getDescription(): string
// --- Usage
NotFoundCommand::getDescription();
```

### - `run` Function

<sub><sup>Prints a &quot;command not found&quot; error and a usage hint, then returns Output::ERROR. </sup></sub>



```php
// --- Contract
public function run(Input $input, Output $output): int
// --- Usage
$notFoundCommand->run($input, $output);
```

### - `help` Function

<sub><sup>Prints the command description as a yellow console block and returns Output::SUCCESS. Override to provide more detailed usage instructions for the command. </sup></sub>



```php
// --- Contract
public function help(Input $input, Output $output): int
// --- Usage
$notFoundCommand->help($input, $output);
```

### - `setRunningApplication` Function

<sub><sup>Injects the running Application instance so the command can access the full command registry if needed. Called automatically by Application::addCommand(). </sup></sub>



```php
// --- Contract
public function setRunningApplication(Application $app): void
// --- Usage
$notFoundCommand->setRunningApplication($app);
```

### - `getApplication` Function

<sub><sup>Returns the Application instance that owns this command. Only available after setRunningApplication() has been called. </sup></sub>



```php
// --- Contract
public function getApplication(): Application
// --- Usage
$notFoundCommand->getApplication();
```

