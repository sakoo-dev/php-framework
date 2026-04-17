<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\Controller;

/**
 * Bridges a multi-action Controller method into a RequestHandlerInterface.
 *
 * The Router creates one instance per matched route dispatch and delegates
 * to Controller::callAction(), keeping the anonymous-class allocation out
 * of the hot path.
 */
final readonly class ControllerActionHandler implements RequestHandlerInterface
{
	public function __construct(
		private Controller $controller,
		private string $action,
	) {}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->controller->callAction($this->action, $request);
	}
}
