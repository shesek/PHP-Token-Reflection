<?php
/**
 * PHP Token Reflection
 *
 * Development version
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file license.txt.
 *
 * @author Ondřej Nešpor <andrew@andrewsville.cz>
 * @author Jaroslav Hanslík <kukulich@kukulich.cz>
 */

namespace TokenReflection\Php;
use TokenReflection;

use TokenReflection\Broker, Reflector, ReflectionProperty as InternalReflectionProperty;
use RuntimeException;

/**
 * Reflection of a not tokenized but defined function/method property.
 *
 * Descendant of the internal reflection with additional features.
 */
class ReflectionProperty extends InternalReflectionProperty implements IReflection, TokenReflection\IReflectionProperty
{
	/**
	 * Reflection broker.
	 *
	 * @var \TokenReflection\Broker
	 */
	private $broker;

	/**
	 * Constructor.
	 *
	 * @param string|\TokenReflection\Php\ReflectionClass|\ReflectionClass Defining class
	 * @param string $className Property name
	 * @param \TokenReflection\Broker $broker Reflection broker
	 */
	public function __construct($class, $propertyName, Broker $broker)
	{
		parent::__construct($class, $propertyName);
		$this->broker = $broker;
	}

	/**
	 * Returns the reflection broker used by this reflection object.
	 *
	 * @return \TokenReflection\Broker
	 */
	public function getBroker()
	{
		return $this->broker;
	}

	/**
	 * Magic __get method.
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	final public function __get($key)
	{
		return TokenReflection\ReflectionBase::get($this, $key);
	}

	/**
	 * Magic __isset method.
	 *
	 * @param string $key Variable name
	 * @return boolean
	 */
	final public function __isset($key) {
		return TokenReflection\ReflectionBase::exists($this, $key);
	}

	/**
	 * Returns the declaring class reflection.
	 *
	 * @return \TokenReflection\Php\IReflectionClass
	 */
	public function getDeclaringClass()
	{
		return ReflectionClass::create(parent::getDeclaringClass(), $this->broker);
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string
	 */
	public function getDeclaringClassName()
	{
		return $this->getDeclaringClass()->getName();
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * Apigen compatibility.
	 *
	 * @return string
	 */
	public function getClass()
	{
		return $this->getDeclaringClassName();
	}

	/**
	 * Returns the appropriate docblock definition.
	 *
	 * @return string|false
	 */
	public function getDocComment()
	{
		return false;
	}

	/**
	 * Returns the docblock definition of the property or its parent.
	 *
	 * @return string|false
	 */
	public function getInheritedDocComment()
	{
		return $this->getDocComment();
	}

	/**
	 * Returns the definition start line number in the file.
	 *
	 * @return integer
	 */
	public function getStartLine()
	{
		return false;
	}

	/**
	 * Returns the definition end line number in the file.
	 *
	 * @return integer
	 */
	public function getEndLine()
	{
		return false;
	}

	/**
	 * Returns parsed docblock.
	 *
	 * @return array
	 */
	public function getAnnotations()
	{
		return array();
	}

	/**
	 * Returns a particular annotation value.
	 *
	 * @param string $name Annotation name
	 * @param boolean $forceArray Always return values as array
	 * @return string|array|null
	 */
	public function getAnnotation($name)
	{
		return null;
	}

	/**
	 * Checks if there is a particular annotation.
	 *
	 * @param string $name Annotation name
	 * @return boolean
	 */
	public function hasAnnotation($name)
	{
		return false;
	}

	/**
	 * Returns if the property is internal.
	 *
	 * @return boolean
	 */
	public function isInternal()
	{
		return $this->getDeclaringClass()->isInternal();
	}

	/**
	 * Returns if the property is user defined.
	 *
	 * @return boolean
	 */
	public function isUserDefined()
	{
		return $this->getDeclaringClass()->isUserDefined();
	}

	/**
	 * Returns the property default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		$values = $this->getDeclaringClass()->getDefaultProperties();
		return $values[$this->getName()];
	}

	/**
	 * Returns the part of the source code defining the property default value.
	 *
	 * @return string
	 */
	public function getDefaultValueDefinition()
	{
		$value = $this->getDefaultValue();
		return null === $value ? null : var_export($value, true);
	}

	/**
	 * Returns if the current reflection comes from a tokenized source.
	 *
	 * @return boolean
	 */
	public function isTokenized()
	{
		return false;
	}

	/**
	 * Returns if the reflection subject is deprecated.
	 *
	 * @return boolean
	 */
	public function isDeprecated()
	{
		return false;
	}

	/**
	 * Creates a reflection instance.
	 *
	 * @param \ReflectionProperty Internal reflection instance
	 * @param \TokenReflection\Broker Reflection broker instance
	 * @return \TokenReflection\Php\ReflectionProperty
	 */
	public static function create(Reflector $internalReflection, Broker $broker)
	{
		static $cache = array();

		if (!$internalReflection instanceof InternalReflectionProperty) {
			throw new RuntimeException(sprintf('Invalid reflection instance provided (%s), ReflectionProperty expected.', get_class($internalReflection)));
		}

		$key = $internalReflection->getDeclaringClass()->getName() . '::' . $internalReflection->getName();
		if (!isset($cache[$key])) {
			$cache[$key] = new self($internalReflection->getDeclaringClass()->getName(), $internalReflection->getName(), $broker);
		}

		return $cache[$key];
	}
}
