<?php
declare(strict_types=1);

namespace Kdyby\Autowired;

use Nette\DI\MissingServiceException as NetteMissingServiceException;
use Nette\MemberAccessException as NetteMemberAccessException;


interface Exception
{

	public function getReflector(): ?\Reflector;

}



/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends \RuntimeException implements Exception
{

	private ?\Reflector $reflector = NULL;

	public function __construct(string $message = '', ?\Reflector $reflector = NULL, ?\Throwable $previous = NULL)
	{
		parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
		$this->reflector = $reflector;
	}

	public function getReflector(): ?\Reflector
	{
		return $this->reflector;
	}

}



/**
 * The exception that is thrown when a value (typically returned by function) does not match with the expected value.
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{

	private ?\Reflector $reflector = NULL;

	public function __construct(string $message = '', ?\Reflector $reflector = NULL, ?\Throwable $previous = NULL)
	{
		parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
		$this->reflector = $reflector;
	}

	public function getReflector(): ?\Reflector
	{
		return $this->reflector;
	}

}


class MemberAccessException extends NetteMemberAccessException implements Exception
{

	private ?\Reflector $reflector = NULL;

	public function __construct(string $message = '', ?\Reflector $reflector = NULL, ?\Throwable $previous = NULL)
	{
		parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
		$this->reflector = $reflector;
	}

	public function getReflector(): ?\Reflector
	{
		return $this->reflector;
	}

}



class MissingServiceException extends NetteMissingServiceException implements Exception
{

	private ?\Reflector $reflector = NULL;

	public function __construct(string $message = '', ?\Reflector $reflector = NULL, ?\Throwable $previous = NULL)
	{
		parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
		$this->reflector = $reflector;
	}

	public function getReflector(): ?\Reflector
	{
		return $this->reflector;
	}

}



class MissingClassException extends InvalidStateException
{

}
