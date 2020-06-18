<?php
declare(strict_types=1);


namespace Kdyby\Autowired;


use Nette\StaticClass;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;


/**
 * Taken from Nette\Reflection\AnnotationsParser (nette/reflection)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 * (BSD-3-Clause license)
 */
final class PhpDocParser
{

	use StaticClass;

	/** single & double quoted PHP string */
	private const RE_STRING = '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"';

	/** identifier */
	private const RE_IDENTIFIER = '[_a-zA-Z\x7F-\xFF][_a-zA-Z0-9\x7F-\xFF-\\\]*';


	/**
	 * @param string $comment
	 * @return mixed[]
	 */
	public static function parseComment(string $comment): array
	{
		static $tokens = ['true' => true, 'false' => false, 'null' => null, '' => true];

		$res = [];
		$comment = preg_replace('#^\s*\*\s?#ms', '', trim($comment, '/*'));
		$parts = preg_split('#^\s*(?=@' . self::RE_IDENTIFIER . ')#m', $comment, 2);

		$description = trim($parts[0]);
		if ($description !== '') {
			$res['description'] = [$description];
		}

		$matches = Strings::matchAll(
			isset($parts[1]) ? $parts[1] : '',
			'~
				(?<=\s|^)@(' . self::RE_IDENTIFIER . ')[ \t]*      ##  annotation
				(
					\((?>' . self::RE_STRING . '|[^\'")@]+)+\)|  ##  (value)
					[^(@\r\n][^@\r\n]*|)                     ##  value
			~xi'
		);

		foreach ($matches as $match) {
			list(, $name, $value) = $match;

			if (substr($value, 0, 1) === '(') {
				$items = [];
				$key = '';
				$val = true;
				$value[0] = ',';
				while ($m = Strings::match(
					$value,
					'#\s*,\s*(?>(' . self::RE_IDENTIFIER . ')\s*=\s*)?(' . self::RE_STRING . '|[^\'"),\s][^\'"),]*)#A')
				) {
					$value = substr($value, strlen($m[0]));
					list(, $key, $val) = $m;
					$val = rtrim($val);
					if ($val[0] === "'" || $val[0] === '"') {
						$val = substr($val, 1, -1);

					} elseif (is_numeric($val)) {
						$val = 1 * $val;

					} else {
						$lval = strtolower($val);
						$val = array_key_exists($lval, $tokens) ? $tokens[$lval] : $val;
					}

					if ($key === '') {
						$items[] = $val;

					} else {
						$items[$key] = $val;
					}
				}

				$value = count($items) < 2 && $key === '' ? $val : $items;

			} else {
				$value = trim($value);
				if (is_numeric($value)) {
					$value = 1 * $value;

				} else {
					$lval = strtolower($value);
					$value = array_key_exists($lval, $tokens) ? $tokens[$lval] : $value;
				}
			}

			$res[$name][] = is_array($value) ? ArrayHash::from($value) : $value;
		}

		return $res;
	}


}
