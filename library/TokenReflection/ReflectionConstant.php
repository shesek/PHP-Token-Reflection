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

namespace TokenReflection;

use RuntimeException;

/**
 * Tokenized constant reflection.
 */
class ReflectionConstant extends ReflectionBase implements IReflectionConstant
{
	/**
	 * Name of the declaring class.
	 *
	 * @var String
	 */
	private $declaringClassName;

	/**
	 * Constant namespace name.
	 *
	 * @var string
	 */
	private $namespaceName;

	/**
	 * Constant value.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Constant value definition.
	 *
	 * @var string
	 */
	private $valueDefinition = '';

	/**
	 * Processes the parent reflection object.
	 *
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionBase
	 */
	protected function processParent(IReflection $parent)
	{
		if ($parent instanceof ReflectionFileNamespace) {
			$this->namespaceName = $parent->getName();
		} elseif ($parent instanceof ReflectionClass) {
			$this->declaringClassName = $parent->getName();
		} else {
			throw new RuntimeException(sprintf('The parent object has to be an instance of TokenReflection\ReflectionFileNamespace or TokenReflection\ReflectionClass, %s given.', get_class($parent)));
		}

		return parent::processParent($parent);
	}

	/**
	 * Parses the token stream.
	 *
	 * @param \TokenReflection\Stream $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionFileNamespace
	 */
	protected function parse(Stream $tokenStream, IReflection $parent)
	{
		return parent::parse($tokenStream, $parent)
			->parseName($tokenStream)
			->parseValue($tokenStream, $parent);
	}

	/**
	 * Parses the constant name.
	 *
	 * @param \TokenReflection\Stream $tokenStream Token substream
	 * @return \TokenReflection\ReflectionConstant
	 */
	protected function parseName(Stream $tokenStream)
	{
		if ($tokenStream->is(T_CONST)) {
			$tokenStream->skipWhitespaces();
		}

		if (!$tokenStream->is(T_STRING)) {
			throw new RuntimeException('Could not determine the constant name');
		}

		if (null === $this->namespaceName || $this->namespaceName === ReflectionNamespace::NO_NAMESPACE_NAME) {
			$this->name = $tokenStream->getTokenValue();
		} else {
			$this->name = $this->namespaceName . '\\' . $tokenStream->getTokenValue();
		}

		$tokenStream->skipWhitespaces();

		return $this;
	}

	/**
	 * Parses the constant value.
	 *
	 * @param \TokenReflection\Stream $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionConstant
	 */
	private function parseValue(Stream $tokenStream, IReflection $parent)
	{
		if (!$tokenStream->is('=')) {
			throw new RuntimeException('Could not find the value definition start');
		}

		$tokenStream->skipWhitespaces();

		static $acceptedStrings = array('true', 'false', 'null');

		$evalValue = true;
		while ($tokenStream->valid()) {
			$value = $tokenStream->getTokenValue();
			if ($tokenStream->is(T_STRING) && !in_array(strtolower($value), $acceptedStrings)) {
				$evalValue = false;
			}

			$this->valueDefinition .= $value;
			$tokenStream->next();
		}

		if (',' === $value || ';' === $value) {
			$this->valueDefinition = trim(substr($this->valueDefinition, 0, -1));
		} else {
			throw new RuntimeException(sprintf('Invalid value definition: "%s".', $this->valueDefinition));
		}

		if ($evalValue) {
			$this->value = eval(sprintf('return %s;', $this->valueDefinition));
		} else {
			// Another constant's name
			if ('\\' !== $this->valueDefinition{0}) {
				$namespaceName = $this->namespaceName ?: $parent->getNamespaceName();
				if ($pos = strpos($this->valueDefinition, '::')) {
					$className = substr($this->valueDefinition, 0, $pos);
					$this->valueDefinition = ReflectionBase::resolveClassFQN($className, $parent->getNamespaceAliases(), $namespaceName)
						. substr($this->valueDefinition, $pos);
				} elseif(ReflectionNamespace::NO_NAMESPACE_NAME !== $namespaceName) {
					$this->valueDefinition = $namespaceName . '\\' . $this->valueDefinition;
				}
			}
		}

		return $this;
	}

	/**
	 * Returns the constant value definition.
	 *
	 * @return string
	 */
	public function getValueDefinition()
	{
		return $this->valueDefinition;
	}

	/**
	 * Returns the constant value.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		if (null === $this->value && 'null' !== strtolower($this->valueDefinition)) {
			if ($position = strpos($this->valueDefinition, '::')) {
				$className = substr($this->valueDefinition, 0, $position);
				$constantName = substr($this->valueDefinition, $position + 2);
				$this->value = $this->getBroker()->getClass($className)->getConstant($constantName);
			} else {
				$constant = $this->getBroker()->getConstant($this->valueDefinition);
				$this->value = $constant ? $constant->getValue() : null;
			}
		}

		return $this->value;
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * @return string
	 */
	public function getDeclaringClassName()
	{
		return $this->declaringClassName;
	}

	/**
	 * Returns the name of the declaring class.
	 *
	 * @return string
	 */
	public function getClass()
	{
		return $this->getDeclaringClassName();
	}

	/**
	 * Returns a reflection of the declaring class.
	 *
	 * @return \TokenReflection\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		if (null === $this->declaringClassName) {
			return null;
		}

		return $this->getBroker()->getClass($this->declaringClassName);
	}

	/**
	 * Returns the docblock definition of the constant.
	 *
	 * @return string|false
	 */
	public function getInheritedDocComment()
	{
		return $this->getDocComment();
	}

	/**
	 * Returns the namespace name.
	 *
	 * @return string
	 */
	public function getNamespaceName()
	{
		return $this->namespaceName === ReflectionNamespace::NO_NAMESPACE_NAME ? null : $this->namespaceName;
	}

	/**
	 * Returns if the class is defined within a namespace.
	 *
	 * @return boolean
	 */
	public function inNamespace()
	{
		return null !== $this->getNamespaceName();
	}

	/**
	 * Returns the unqualified name.
	 *
	 * @return string
	 */
	public function getShortName()
	{
		$name = $this->getName();
		if (null !== $this->namespaceName && $this->namespaceName !== ReflectionNamespace::NO_NAMESPACE_NAME) {
			$name = substr($name, strlen($this->namespaceName) + 1);
		}

		return $name;
	}
}
