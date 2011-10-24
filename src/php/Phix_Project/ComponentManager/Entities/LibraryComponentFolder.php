<?php

/**
 * Copyright (c) 2011 Stuart Herbert.
 * Copyright (c) 2010 Gradwell dot com Ltd.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the 
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Phix_Project
 * @subpackage  ComponentManager
 * @author      Stuart Herbert <stuart@stuartherbert.com>
 * @copyright   2011 Stuart Herbert. www.stuartherbert.com
 * @copyright   2010 Gradwell dot com Ltd. www.gradwell.com
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://www.phix-project.org
 * @version     @@PACKAGE_VERSION@@
 */

namespace Phix_Project\ComponentManager\Entities;

class LibraryComponentFolder extends ComponentFolder
{
        const COMPONENT_TYPE = 'php-library';
        const LATEST_VERSION = 11;
        const DATA_FOLDER = '@@DATA_DIR@@/ComponentManagerPhpLibrary/php-library';

        public function createComponent()
        {
                // step 1: create the folders required
                $this->createFolders();

                // step 2: create the build file
		$this->createBuildFile();
		$this->createBuildLocalFile();
                $this->createBuildProperties();

                // step 3: create the package.xml file
                $this->createPackageXmlFile();

                // step 4: add in the doc files
                $this->createDocFiles();

                // step 5: add in config files for popular source
                // control systems
                $this->createScmIgnoreFiles();

                // step 6: don't forget the files for the unit tests
                $this->createBootstrapFile();
                $this->createPhpUnitXmlFile();

		// step 7: add a dummy PHP file so that an empty
		// component can build-vendor once the metadata
		// has been edited
		$this->createDummyPhpFile();
                
                // step 8: add the README file into the src folder
                $this->createSrcReadmeFile();

                // if we get here, job done
        }

        protected function createFolders()
        {
                $foldersToMake = array
                (
                        'src',
                        'src/php',
                        'src/bin',
                        'src/data',
                        'src/www',
			'src/docs',
                        'src/tests',
                        'src/tests/unit-tests',
                        'src/tests/unit-tests/bin',
                        'src/tests/unit-tests/php',
                        'src/tests/unit-tests/www',
                        'src/tests/integration-tests',
                        'src/tests/functional-tests',
                );

                foreach ($foldersToMake as $folderToMake)
                {
                        $folder = $this->folder . '/' . $folderToMake;

                        // does the folder already exist?
                        if (!is_dir($folder))
                        {
                                // no it does not ... create it
                                if (!mkdir ($folder))
                                {
                                        // it all went wrong
                                        throw new \Exception('unable to create folder ' . $this->folder . '/' . $folderToMake);
                                }
                        }
                        
                        $this->touchFile($folderToMake . '/.empty');
                }
        }

        protected function createBuildFile()
        {
                $this->copyFilesFromDataFolder(array('build.xml'));
	}

	protected function createBuildLocalFile()
	{
                $this->copyFilesFromDataFolder(array('build.local.xml'));
	}

        protected function createBuildProperties()
        {
                $this->copyFilesFromDataFolder(array('build.properties'));
        }

        protected function createPackageXmlFile()
        {
                $this->copyFileFromDataFolderWithNewName('package-xml.xml', 'package.xml');
        }

        protected function createDocFiles()
        {
                $this->copyFilesFromDataFolder(array('README.md', 'LICENSE.txt'));
        }

        protected function createScmIgnoreFiles()
        {
                $this->copyFilesFromDataFolder(array('.gitignore', '.hgignore'));
        }

        protected function createBootstrapFile()
        {
                $this->copyFilesFromDataFolder(array('bootstrap.php'), '/src/tests/unit-tests/');
        }

	protected function createDummyPhpFile()
	{
		$this->copyFilesFromDataFolder(array('dummy.php'), '/src/php/');
	}
        
        protected function createPhpUnitXmlFile()
        {
                $this->copyFilesFromDataFolder(array('phpunit.xml'));
        }
        
        protected function createSrcReadmeFile()
        {
                $this->copyFilesFromDataFolder(array('src/README.md'), '/src/');
        }

        protected function touchFile($filename)
        {
                $fullFilename = $this->folder . '/' . $filename;
                \touch($fullFilename);
        }

        /**
         * Upgrade a php-library to v2
         *
         * The changes between v1 and v2 are:
         *
         * * improved build file
         * * improved SCM ignore files
         * * improved bootstrap file
         *
         * Nothing has moved location.
         */
        protected function upgradeFrom1To2()
        {
                $this->createBuildFile();
                $this->createScmIgnoreFiles();
                $this->createBootstrapFile();
        }

        /**
         * Upgrade a php-library to v3
         *
         * The changes between v2 and v3 are:
         *
         * * improved build file
         */
        protected function upgradeFrom2To3()
        {
                $this->createBuildFile();
        }

	/**
	 * Upgrade a php-library to v4
	 * 
	 * The changes between v3 and v4 are:
	 *
	 * * improved build file
	 * * new src/docs/ folder
	 * * new pear.local property
	 * * new project.channel property
	 */
	protected function upgradeFrom3To4()
	{
		$this->createFolders();
		$this->createBuildFile();
		$this->addBuildProperty('project.channel', 'pear.example.com');
		$this->addBuildProperty('pear.local', '/var/www/${project.channel}');
		$this->createDummyPhpFile();
	}

	/**
	 * Upgrade a php-library to v5
	 *
	 * The changes between v4 and v5 are:
	 *
	 * * improved build file
	 */
	protected function upgradeFrom4To5()
	{
		$this->createBuildFile();
	}

	/**
	 * Upgrade a php-library to v6
	 *
	 * The changes between v5 and v6 are:
	 *
	 * * support for build.local.xml
	 */
        protected function upgradeFrom5To6()
        {
		$this->createBuildFile();
		$this->createBuildLocalFile();
	}

	/**
	 * Upgrade a php-library to v7
	 *
	 * The changes between v6 and v7 are:
	 *
	 * * improved PHPUnit bootstrap file
	 */
	protected function upgradeFrom6To7()
	{
		$this->createBootstrapFile();
	}

	/**
	 * Upgrade a php-library to v8
	 *
	 * The changes between v7 and v8 are:
	 *
	 * * new 'phing version' build.xml target
	 */
	protected function upgradeFrom7To8()
	{
		$this->createBuildFile();
	}
        
        /**
         * Upgrade a php-library to v9
         * 
         * The changes between v8 and v9 are:
         * 
         * * new 'phpunit.xml' file in the component's root folder
         */
        protected function upgradeFrom8To9()
        {
                $this->createBuildFile();
                $this->createPhpUnitXmlFile();
        }
        
        /**
         * Upgrade a php-library to v10
         * 
         * The changes between v9 and v10 are:
         * 
         * * support for snapshot versions of components
         * * user-friendly checks for missing vendor/ folder
         * * user-friendly checks for trying to install PEAR package
         *   when it has not been built (v useful for snapshots)
         * * the location of the code coverage report is now a clickable
         *   hyperlink in terminals that detect such things
         * * support for 'local.*' targets in the build.local.xml file
         */
        protected function upgradeFrom9To10()
        {
                $this->createBuildFile();
		$this->addBuildProperty('project.snapshot', 'false', 'project.patchLevel');
                
                // edit the XML tags
                $packageXml = $this->loadPackageXml();
                $packageXml->version[0]->release[0] = '${project.version}';
                $packageXml->stability[0]->release[0] = '${project.stability}';
                $this->savePackageXml($packageXml);
                
                // rename the targets in old build.local.xml files
                $regex = array();
                $replace = array();
                
                // the project in build.local.xml gets a name
                $regex[] = '|project default="local-help"|';
                $replace[] = 'project name="local" default="help"';
                
                // the help target in build.local.xml gets renamed
                $regex[] = '|target name="local-help"|';
                $replace[] = 'target name="help"';
                
                $this->regexFile('build.local.xml', $regex, $replace);
        }
        
        /**
         * The changes between v10 and v11 are:
         * 
         * * new src/README.md file, explaining what each of the folders
         *   in the src/ folder are for
         */
        protected function upgradeFrom10To11()
        {
                $this->createSrcReadmeFile();
        }
}
