<?php

$data = ['foo', 'bar'];
$text = '  hello  ';

// Should trigger errors - ignored pure function outputs
strlen($text);
count($data);
trim($text);

// Should NOT trigger errors - valid usage
$length = strlen($text);
$total = count($data);
echo trim($text);

if (strlen($text) > 5) {
}

someFunction(count($data));

// Should NOT trigger errors - non-pure functions
echo 'hello';
var_dump($data);

// Should trigger error - case insensitive
strlen($text);

// Should NOT trigger error - case insensitive but valid
$len = strlen($text);

// Should NOT trigger error - not a FuncCall (assignment expression statement)
$x = 42;

// Should NOT trigger error - non-pure function called as statement
var_dump($text);

function someFunction($x)
{
	return $x;
}
