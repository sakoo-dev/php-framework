<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher;

/**
 * Enumerates the filesystem event types the Watcher subsystem can handle.
 *
 * - MODIFY — the file's content was written or truncated.
 * - MOVE   — the file was renamed or moved to a different path.
 * - DELETE — the file was unlinked from the filesystem.
 */
enum EventTypes
{
	case MODIFY;

	case MOVE;

	case DELETE;
}
