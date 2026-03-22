<h1>
<picture>
  <source media="(prefers-color-scheme: dark)" srcset=".github/static/logo-dark.png">
  <source media="(prefers-color-scheme: light)" srcset=".github/static/logo-light.png">
  <img width="96" src=".github/static/logo-light.png">
</picture>
Sakoo PHP Web Framework
</h1>

![GitHub License](https://img.shields.io/github/license/sakoo-dev/php-framework)
![Static Badge](https://img.shields.io/badge/Status-In_Development-green)
[![Visitor](https://visitor-badge.laobi.icu/badge?page_id=sakoo-dev/php-framework)](https://github.com/sakoo-dev/php-framework)
![Packagist Version](https://img.shields.io/packagist/v/sakoo/framework)

[![Sakoo CI Pipeline](https://github.com/sakoo-dev/php-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/sakoo-dev/php-framework/actions/workflows/ci.yml)
![Coverage Badge](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/pouyaaofficial/ebfe01b7208b0dc6ee0f0302795bd2ee/raw/php-framework_main.json)

![GitHub Release Date](https://img.shields.io/github/release-date/sakoo-dev/php-framework)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/sakoo-dev/php-framework/total)

<a href="https://sakoo.dev" target="_blank">
    <img src=".github/static/undraw-sakoo.svg" width="400"/>
</a>

## :rocket: A Platform for Soaring

> [!WARNING]
>
> **This Project is Under Construction**
>
>It's not a Stable and Reliable Version. Please do not use it in Production Environment.

# Why Sakoo?

### 1. Backend Scaffolding System (App-Modules-Hub)

Quickly generate backend structure using Sakoo’s App-Modules-Hub. Perfect for rapid MVP development and team-based projects.

### 2. Concurrency-Ready, Fast & Scalable

Sakoo is natively designed to support concurrent execution and is suitable for real-time systems or high-throughput applications using Co-Routines and JIT compilation. Its stateless architecture enables efficient horizontal scaling, making it ideal for modern, distributed workloads.

### 3. PWA, Telegram Mini App & Telegram Bot Ready

Not only Perfect for making PWA, Telegram Mini Apps, and Bots, nor Clean and fast in syntax.

### 4. AI-Driven Development (AIDD) and Prompt to Production (P2P)

Sakoo PHP Framework turns acceptance criteria into production-ready, secure code through an automated pipeline. Using Agent with MCP (Access to Docs, Errors, Source), RAG, and PHPArkitect, it enforces architecture and context, while an LLM generates tests first, then the code to make them pass. Each cycle runs through PHPUnit, PHPStan, PHPCSFixer, and security scans—ensuring correctness, quality, and safety. Sakoo guarantees integrity and reliability.

### 5. Domain-Oriented & Customizable Architecture

Sakoo is structured around Domain Contexts, not just MVC. It is built for modularity, testability, and clean separation of business logic, making it a great fit for domain-driven applications. Also, Sakoo's ORM is clean and decoupled: No Active Record coupling, Easy to test and extend, and designed for DDD-friendly patterns like Aggregates and Value Objects. Finally, Sakoo lets you customize your folder structure based on your preferred architecture.

### 6. Zero Third-Party Dependencies

Sakoo doesn’t rely on external Composer packages. This means no update conflicts, better security, and full control over your stack. It is ideal for sensitive or mission-critical applications, using the latest PHP version and complete core components.

## Requirements

Sakoo Just needed _Docker_ Platform to Run.
Make sure [___Docker___ and ___Docker Compose___](https://docker.com) are installed on your system.
___Windows users___ could use the _Windows Subsystem for Linux (WSL-2)_ to Run the Project on _Docker Desktop_.
See [Windows WSL-2 Installation Guide.](https://docs.microsoft.com/en-us/windows/wsl/install)

## Installation (Build)

Run the following command to initialize the Project:

```bash
make
```

Once after the Project initialization, you can use following commands:

```bash
make up     # equals to docker-compose up -d
make down   # equals to docker-compose down
make rm     # removes the all containers and theirs persist data
```

Sakoo uses a ___Docker Proxy___ Program, and it gives you ability to interact with your favorite tools, Easily.
For Example:

```bash
./sakoo php <your command>
./sakoo composer <your command>
./sakoo test
```

## Contributing

Thank you for considering contributing to the Sakoo framework! You can read our contribution guidelines [Here](.github/CONTRIBUTION.md).

## Code of Conduct

In order to ensure that the Sakoo community is welcoming to all, please review and abide by the [Code of Conduct](.github/CODE_OF_CONDUCT.md).

## Security Vulnerabilities

If you discover a security vulnerability within Sakoo, please send an email to [**Pouya Asgharnejad Tehran**](mailto:pouyaaofficial@gmail.com).
All security vulnerabilities will be promptly addressed. You can read this complete [Security Policy Guide](./SECURITY.md).

## License

The **Sakoo PHP Framework** is open-source software licensed under the [MIT License](LICENSE.md).
