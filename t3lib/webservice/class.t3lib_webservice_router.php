<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2001 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Enter descriptions here
 * @author Thomas Maroschik<tmaroschik@dfau.de>
 */
class t3lib_webservice_Router implements t3lib_Singleton {

	protected $routes = array();

	/**
	 * Walks through all configured routes and calls their respective resolves-method.
	 * When a matching route is found, the corresponding URI is returned.
	 *
	 * @param string $requestString
	 * @param array $routes
	 * @return array $resolvedRoute
	 * @throws InvalidArgumentException
	 */
	public function resolve($requestString) {
		foreach ($this->getRoutes() as $uriPattern => $webserviceClassName) {
			$matchReturn = preg_match_all($uriPattern, $requestString, $matches);
			if ($matchReturn === FALSE) {
				throw new InvalidArgumentException('The route pattern "' . $uriPattern . '" returns the error code: ' . preg_last_error(), 1314720278);
			} elseif ($matchReturn > 0) {
				$resolvedRoute = array(
					'webserviceClassName'	=> $webserviceClassName,
					'resolvedArguments'	=> array(),
				);
				foreach (array_keys($matches) as $matchKey) {
					if (!is_numeric($matchKey) && isset($matches[$matchKey][0])) {
						$resolvedRoute['resolvedArguments'][$matchKey] = $matches[$matchKey][0];
					}
				}
				return $resolvedRoute;
			}
		}
	}

	/**
	 * Sets routes
	 *
	 * @param array $routes
	 */
	public function setRoutes(array $routes) {
		$this->routes = $routes;
		return $this;
	}

	/**
	 * Adds a route
	 *
	 * @param array $route
	 */
	public function addRoute($uriPattern, $webserviceClassName) {
		$this->routes[$uriPattern] = $webserviceClassName;
		return $this;
	}

	/**
	 * Removes a route
	 *
	 * @param string $uriPattern
	 */
	public function removeRoute($uriPattern) {
		unset($this->routes[$uriPattern]);
		return $this;
	}

	/**
	 * Returns routes
	 *
	 * @return array An array routes
	 */
	public function getRoutes() {
		return $this->routes;
	}

}

?>