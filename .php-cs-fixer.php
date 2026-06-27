<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

require_once __DIR__ . '/vendor/autoload.php';

$rules = [
	'@PhpCsFixer' => true,
	'multiline_whitespace_before_semicolons' => false,
	'php_unit_test_class_requires_covers' => false,
	'php_unit_internal_class' => false,
	'concat_space' => ['spacing' => 'one'],
	'ordered_class_elements' => false,
	'php_unit_method_casing' => ['case' => 'snake_case'],
	'explicit_string_variable' => false,
	'phpdoc_add_missing_param_annotation' => false,
	'phpdoc_align' => ['align' => 'left'],
	'blank_line_before_statement' => [
		'statements' => [
			'break',
			'case',
			'continue',
			'declare',
			'default',
			'do',
			'exit',
			'for',
			'foreach',
			'goto',
			'if',
			'include',
			'include_once',
			'require',
			'require_once',
			'return',
			'switch',
			'throw',
			'try',
			'while',
		],
	],
];

$config = new Config();

$config->getFinder()
	->in([
		__DIR__ . '/app',
		__DIR__ . '/core',
		__DIR__ . '/ai',
	])
	->name('*.php')
	->ignoreVCS(true)
	->ignoreVCSIgnored(true)
	->ignoreDotFiles(true);

return $config
	->setParallelConfig(ParallelConfigFactory::detect())
	->setRules($rules)
	->setIndent("\t")
	->setLineEnding("\n");
