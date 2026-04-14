<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

final class UriTest extends TestCase
{
	#[Test]
	public function it_parses_full_uri(): void
	{
		$uri = Uri::fromString('https://user:pass@example.com:8080/path?query=val#frag');

		$this->assertSame('https', $uri->getScheme());
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/path', $uri->getPath());
		$this->assertSame('query=val', $uri->getQuery());
		$this->assertSame('frag', $uri->getFragment());
	}

	#[Test]
	public function it_creates_empty_uri(): void
	{
		$uri = Uri::fromString('');

		$this->assertSame('', $uri->getScheme());
		$this->assertSame('', $uri->getHost());
		$this->assertSame('', $uri->getPath());
		$this->assertSame('', (string) $uri);
	}

	#[Test]
	public function it_normalizes_scheme_to_lowercase(): void
	{
		$uri = Uri::fromString('HTTPS://example.com');

		$this->assertSame('https', $uri->getScheme());
	}

	#[Test]
	public function it_normalizes_host_to_lowercase(): void
	{
		$uri = Uri::fromString('http://EXAMPLE.COM');

		$this->assertSame('example.com', $uri->getHost());
	}

	#[Test]
	public function it_returns_null_port_for_standard_http(): void
	{
		$uri = Uri::fromString('http://example.com:80/path');

		$this->assertNull($uri->getPort());
	}

	#[Test]
	public function it_returns_null_port_for_standard_https(): void
	{
		$uri = Uri::fromString('https://example.com:443/path');

		$this->assertNull($uri->getPort());
	}

	#[Test]
	public function it_returns_non_standard_port(): void
	{
		$uri = Uri::fromString('http://example.com:8080/path');

		$this->assertSame(8080, $uri->getPort());
	}

	#[Test]
	public function it_builds_authority(): void
	{
		$uri = Uri::fromString('http://user:pass@example.com:8080/path');

		$this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
	}

	#[Test]
	public function it_omits_standard_port_in_authority(): void
	{
		$uri = Uri::fromString('http://example.com:80/path');

		$this->assertSame('example.com', $uri->getAuthority());
	}

	#[Test]
	public function it_returns_empty_authority_without_host(): void
	{
		$uri = new Uri(path: '/path');

		$this->assertSame('', $uri->getAuthority());
	}

	#[Test]
	public function with_scheme_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com');
		$new = $uri->withScheme('https');

		$this->assertSame('http', $uri->getScheme());
		$this->assertSame('https', $new->getScheme());
		$this->assertNotSame($uri, $new);
	}

	#[Test]
	public function with_host_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com');
		$new = $uri->withHost('other.com');

		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame('other.com', $new->getHost());
	}

	#[Test]
	public function with_port_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com');
		$new = $uri->withPort(9090);

		$this->assertNull($uri->getPort());
		$this->assertSame(9090, $new->getPort());
	}

	#[Test]
	public function with_port_null_removes_port(): void
	{
		$uri = Uri::fromString('http://example.com:8080');
		$new = $uri->withPort(null);

		$this->assertNull($new->getPort());
	}

	#[Test]
	public function with_port_throws_on_invalid(): void
	{
		$uri = Uri::fromString('http://example.com');

		$this->expectException(\InvalidArgumentException::class);
		$uri->withPort(70000);
	}

	#[Test]
	public function with_path_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com/old');
		$new = $uri->withPath('/new');

		$this->assertSame('/old', $uri->getPath());
		$this->assertSame('/new', $new->getPath());
	}

	#[Test]
	public function with_query_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com?foo=1');
		$new = $uri->withQuery('bar=2');

		$this->assertSame('foo=1', $uri->getQuery());
		$this->assertSame('bar=2', $new->getQuery());
	}

	#[Test]
	public function with_fragment_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com#old');
		$new = $uri->withFragment('new');

		$this->assertSame('old', $uri->getFragment());
		$this->assertSame('new', $new->getFragment());
	}

	#[Test]
	public function with_user_info_returns_new_instance(): void
	{
		$uri = Uri::fromString('http://example.com');
		$new = $uri->withUserInfo('user', 'pass');

		$this->assertSame('', $uri->getUserInfo());
		$this->assertSame('user:pass', $new->getUserInfo());
	}

	#[Test]
	public function to_string_builds_full_uri(): void
	{
		$uri = Uri::fromString('https://user:pass@example.com:8080/path?q=1#frag');

		$this->assertSame('https://user:pass@example.com:8080/path?q=1#frag', (string) $uri);
	}

	#[Test]
	public function to_string_prefixes_rootless_path_with_slash_when_authority_present(): void
	{
		$uri = new Uri(scheme: 'http', host: 'example.com', path: 'rootless');

		$this->assertSame('http://example.com/rootless', (string) $uri);
	}

	#[Test]
	public function to_string_reduces_multiple_leading_slashes_without_authority(): void
	{
		$uri = new Uri(path: '//leading');

		$this->assertSame('/leading', (string) $uri);
	}

	#[Test]
	public function from_string_throws_on_invalid_uri(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		Uri::fromString('http:///invalid');
	}
}
