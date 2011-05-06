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

use Reflector;

/**
 * Basic internal reflection interface.
 *
 * Common interface for all internal reflection classes.
 */
interface IReflection extends TokenReflection\IReflection
{
	/**
	 * Creates a reflection instance.
	 *
	 * @param \Reflector Internal reflection instance
	 * @param \TokenReflection\Broker Reflection broker instance
	 * @return \TokenReflection\Php\IReflection
	 */
	public static function create(Reflector $internalReflection, TokenReflection\Broker $broker);
}
