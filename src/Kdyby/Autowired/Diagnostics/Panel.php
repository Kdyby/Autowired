<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Autowired\Diagnostics;

use Kdyby;
use Kdyby\Autowired\MissingClassException;
use Kdyby\Autowired\MissingServiceException;
use Nette;
use Nette\Diagnostics\BlueScreen;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Panel extends Nette\Object
{

	public static function renderException(\Exception $e = NULL)
	{
		if (!$e instanceof Kdyby\Autowired\Exception || !$e->getReflector()) {
			return NULL;
		}

		return array(
			'tab' => 'Autowired',
			'panel' => self::highlightException($e),
		);
	}



	/**
	 * @param \Kdyby\Autowired\Exception $e
	 * @return string
	 */
	protected static function highlightException(Kdyby\Autowired\Exception $e)
	{
		$refl = $e->getReflector();
		/** @var \Reflector|\Nette\Reflection\Property|\Nette\Reflection\Method $refl */
		$file = $refl->getDeclaringClass()->getFileName();
		$line = $refl instanceof Nette\Reflection\Property ? self::getPropertyLine($refl) : $refl->getStartLine();

		return '<p><b>File:</b> ' . Nette\Diagnostics\Helpers::editorLink($file, $line) . '</p>' .
			BlueScreen::highlightFile($file, $line);
	}



	/**
	 * @param \ReflectionProperty $property
	 * @return int
	 */
	protected static function getPropertyLine(\ReflectionProperty $property)
	{
		$class = $property->getDeclaringClass();

		$context = 'file';
		$contextBrackets = 0;
		foreach (token_get_all(file_get_contents($class->getFileName())) as $token) {
			if ($token === '{') {
				$contextBrackets += 1;

			} elseif ($token === '}') {
				$contextBrackets -= 1;
			}

			if (!is_array($token)) {
				continue;
			}

			if ($token[0] === T_CLASS) {
				$context = 'class';
				$contextBrackets = 0;

			} elseif ($context === 'class' && $contextBrackets === 1 && $token[0] === T_VARIABLE) {
				if ($token[1] === '$' . $property->getName()) {
					return $token[2];
				}
			}
		}

		return NULL;
	}

}
