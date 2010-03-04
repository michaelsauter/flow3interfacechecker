<?php
declare(ENCODING = 'utf-8');
namespace F3\InterfaceChecker\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 package "InterfaceChecker".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Standard controller for the InterfaceChecker package 
 *
 * @version $Id: $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StandardController extends \F3\FLOW3\MVC\Controller\ActionController {


	/**
	 * @var \F3\FLOW3\Object\ObjectFactoryInterface
	 * @inject
	 */
	protected $objectFactory;


	/**
	 * Index action
	 *
	 * @return void
	 */
	public function indexAction() {

	}
	
	
	/**
	 * Check package action
	 *
	 * @param string packagePath path to package
	 * @return void
	 */
	public function checkPackageAction($packagePath) {
		$interfacesMissingMethods = array();
		$validPackage = true;
		
		$packageParts = explode('/', $packagePath);
		
		if (count($packageParts) == 2) {
			try {
				$package = $this->objectFactory->create('F3\FLOW3\Package\Package', $packageParts[1], FLOW3_PATH_PACKAGES . $packagePath . '/');
				$classFiles = $package->getClassFiles();

				foreach ($classFiles as $className => $classFile) {
					$temp = $this->getInterfacesWithMissingMethodsFromClassName($className);
					if (count($temp)) {
						$interfacesMissingMethods[] = $temp;
					}
				}			
			}
			catch (\Exception $error) {
				$validPackage = false;
			}			
		}
		else {
			$validPackage = false;
		}
	
		$this->view->assign('validPackage', $validPackage);
		$this->view->assign('packagePath', $packagePath);
		$this->view->assign('interfacesMissingMethods', $interfacesMissingMethods);
	}
	
	
	/**
	 * Check class action
	 *
	 * @param string className the name of the class
	 * @return void
	 */
	public function checkClassAction($className) {
		$validClass = true;
		$interfacesMissingMethods = array();
		
		if (class_exists($className)) {
			$temp = $this->getInterfacesWithMissingMethodsFromClassName($className);
			if (count($temp)) {
				$interfacesMissingMethods[] = $temp;
			}
		}
		else {
			$validClass = false;
		}
		
		$this->view->assign('validClass', $validClass);
		$this->view->assign('className', $className);
		$this->view->assign('interfacesMissingMethods', $interfacesMissingMethods);
	}
	
	
	/**
	 * @param string className the name of the class
	 * @return array interfaces, missing methods and checked class name
	 */
	protected function getInterfacesWithMissingMethodsFromClassName($className)
	{
		$interfacesWithMissingMethods = array();
	
		if (class_exists($className)) {
			// get reflection class of given class
			$reflectionClass = new \ReflectionClass($className);

			// get interfaces of given class
			$implementedInterfaceNames = $reflectionClass->getInterfaceNames();
			if (count($implementedInterfaceNames)) {
				$implementedInterfaceObjects = $reflectionClass->getInterfaces();

				// get all public methods defined in given class (filter out some obviously not needed methods)
				$publicClassMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
				$filteredPublicClassMethods = array();
				foreach ($publicClassMethods as $key => $publicClassMethod) {
					if ($publicClassMethod->name != '__construct' && substr($publicClassMethod->name, 0, 6) != 'inject'  && strpos($publicClassMethod->name, 'Action') === false) {
						$filteredPublicClassMethods[] = $publicClassMethod->name;
					}
				}

				// get all methods from the interface
				$interfaceMethods = array();
				foreach ($implementedInterfaceNames as $interfaceName) {
					$tempInterfaceMethods = $implementedInterfaceObjects[$interfaceName]->getMethods(\ReflectionMethod::IS_PUBLIC);
					foreach ($tempInterfaceMethods as $interfaceMethod) {
						$interfaceMethods[] = $interfaceMethod->name;
					}
				}

				// get public methods from class which are not defined in the interface(s)
				$diffMethods = array_diff($filteredPublicClassMethods, $interfaceMethods);

				// add to interfacesWithMissingMethods
				if (count($diffMethods)) {
					$interfacesWithMissingMethods = array('checkedInterfaces' => $implementedInterfaceNames, 'possiblyMissing' => $diffMethods, 'methodsDefinedInClass' => $className);
				}
			}		
		}
		
		return $interfacesWithMissingMethods;
	}
}
?>