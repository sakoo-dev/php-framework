<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Session\Exception;

/**
 * Thrown when a caller requests a specific chat session that does not exist
 * in the chat-history storage directory.
 */
final class ChatSessionNotFoundException extends \RuntimeException {}
