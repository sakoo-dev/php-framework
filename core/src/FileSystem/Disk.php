<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

use Sakoo\Framework\Core\FileSystem\Storages\Local\Local;

/**
 * Enumerates the available filesystem storage backends.
 *
 * Each case carries the fully-qualified class name of the corresponding Storage
 * implementation as its string value. File::open() uses this value directly to
 * instantiate the driver, so adding a new backend requires only a new enum case —
 * no changes to the factory are necessary.
 *
 * Currently available drivers:
 * - Local — reads and writes files on the local host filesystem via standard PHP
 *            file functions.
 */
enum Disk: string
{
	case Local = Local::class;
}
