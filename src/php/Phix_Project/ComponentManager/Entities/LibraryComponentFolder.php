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

use Phix_Project\Phix\Context;
use Phix_Project\ContractLib\Contract;

class LibraryComponentFolder extends ComponentFolder
{
        const COMPONENT_TYPE = 'php-library';
        const LATEST_VERSION = 12;
        const DATA_FOLDER = '@@DATA_DIR@@/ComponentManagerPhpLibrary/php-library';

        protected $activeRoles = array();

        public function __construct($folder)
        {
                parent::__construct($folder);
                $this->activeRoles = $this->determineActiveRoles();
        }


        public function createComponent($subsetRoles)
        {
                // step 0: catch silly programmer errors
                Contract::Preconditions(function() use ($subsetRoles)
                {
                        Contract::RequiresValue($subsetRoles, is_array($subsetRoles), '$subsetRoles must be an array');
                        Contract::RequiresValue($subsetRoles, count($subsetRoles) > 0, '$subsetRoles cannot be an empty array');
                });

                // step 1: create the folders required
                $this->createFolders($subsetRoles);

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

        public function addRoles($roles)
        {
                $this->createFolders($roles);

                // role-specific actions that we now need to do
                if (in_array($roles, 'test'))
                {
                        $this->createBootstrapFile();
                        $this->createPhpUnitXmlFile();
                }
        }

        public function removeUnusedRoles(Context $context, $dryRun = true)
        {
                $so = $context->stdout;
                $roles = $this->determineUnusedRoles();

                // did we find any roles to remove?
                if (count($roles) == 0)
                {
                        $so->outputLine(null, "No unused roles found");
                        return 0;
                }

                // are we doing a dry run?
                if ($dryRun)
                {
                        $so->outputLine(null, "Attempting dry-run ...");
                        foreach ($roles as $role => $folders)
                        {
                                $so->outputLine(null, "Detected unused PEAR-Installer file role '$role'");
                                foreach ($folders as $folder)
                                {
                                        $so->outputLine(null, "- Would remove folder '$folder'");
                                }
                        }
                        $so->outputLine(null, "Dry-run complete.");
                        return 0;
                }

                // if we get here, then we are removing the folders!
                foreach ($roles as $role => $folders)
                {
                        $so->outputLine(null, "Detected unused PEAR-Installer file role '$role' ... removing");
                        $this->removeFolders($folders);
                }

                $so->outputLine(null, "Done.");
                return 0;
        }

        protected function createFolders($subsetRoles)
        {
                $foldersByRole = array
                (
                        'bin'  => array(
                            'src',
                            'src/bin',
                        ),
                        'data' => array(
                            'src',
                            'src/data'
                        ),
                        'doc'  => array(
                            'src',
                            'src/docs'
                        ),
                        'php'  => array(
                            'src',
                            'src/php',
                        ),
                        'test' => array(
                            'src',
                            'src/tests',
                            'src/tests/unit-tests',
                            'src/tests/integration-tests',
                            'src/tests/functional-tests',
                        ),
                        'www'  => array(
                            'src',
                            'src/www',
                        )
                );

                $foldersToMake = array();
                foreach ($subsetRoles as $role)
                {
                        foreach($foldersByRole[$role] as $folder)
                        {
                                $foldersToMake[] = $folder;
                        }
                }

                // ha! you didn't think it was quite that easy, did you?
                // if the 'test' role is enabled, we need to go and add
                // in the folders for tests for the others

                if (in_array('test', $subsetRoles))
                {
                        $testFoldersByRole = array
                        (
                                'bin' => array (
                                        'src/tests/unit-tests/bin',
                                ),
                                'php' => array (
                                        'src/tests/unit-tests/php',
                                ),
                                'www' => array (
                                        'src/tests/unit-tests/www'
                                ),
                        );

                        foreach ($subsetRoles as $role)
                        {
                                if (!isset($testFoldersByRole[$role]))
                                {
                                        continue;
                                }

                                foreach ($testFoldersByRole[$role] as $folder)
                                {
                                        $foldersToMake[] = $folder;
                                }
                        }
                }

                // at this point, $foldersToMake contains a list of the
                // folders that we want to create
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

                // now, we need to check again to see which roles are active
                $this->determineActiveRoles();
        }

        protected function determineActiveRoles()
        {
                $foldersForRoles = array (
                        'bin'  => 'src/bin',
                        'data' => 'src/data',
                        'doc'  => 'src/docs',
                        'php'  => 'src/php',
                        'test' => 'src/tests',
                        'www'  => 'src/www'
                );

                $activeRoles = array();
                foreach ($foldersForRoles as $role => $folder)
                {
                        if (is_dir($this->folder . '/' . $folder))
                        {
                                $activeRoles[$role] = true;
                        }
                }

                return $activeRoles;
        }

        protected function testHasActiveRole($role)
        {
                return isset($this->activeRoles[$role]);
        }

        /**
         * Determine which PEAR-Installer roles are definitely not being used
         * in this component, so that they can be removed
         *
         * The basic idea is to peek inside every 'active' role, and see if
         * there are any user-created files in there.  If not, the role is not
         * currently being used, and can be dropped!
         *
         * @return array
         *         List of roles to remove, and the folders to remove
         *         per role
         */
        protected function determineUnusedRoles()
        {
                $unusedRoles = array();

                // where do we need to look, to see whether a role is
                // currently being used or not?
                $foldersToCheck = array
                (
                        'bin' => array(
                                'folders' => array (
                                        'src/bin',
                                        'src/tests/unit-tests/bin'
                                ),
                                'testFoldersToRemove' => array (
                                        'bin',
                                ),
                        ),
                        'data' => array(
                                'folders' => array(
                                        'src/data'
                                ),
                                'testFoldersToRemove' => array (
                                        'data',
                                ),
                        ),
                        'doc' => array(
                                'folders' => array(
                                        'src/docs'
                                ),
                        ),
                        'php' => array(
                                'folders' => array (
                                        'src/php',
                                        'src/tests/unit-tests/php',
                                ),
                                'testFoldersToRemove' => array (
                                        'php',
                                ),
                        ),
                        'www' => array (
                                'folders' => array (
                                        'src/www',
                                        'src/tests/unit-tests/www'
                                ),
                                'testFoldersToRemove' => array (
                                        'www'
                                ),
                        ),
                        'test' => array(
                                'folders' => array (
                                        'src/tests/functional-tests',
                                        'src/tests/integration-tests',
                                        'src/tests/unit-tests/',
                                ),
                                'filesWeCanDelete' => array (
                                        'bootstrap.php',
                                        'functional-tests',
                                        'integration-tests',
                                        'unit-tests',
                                        '.empty',
                                        'dummy.php'
                                ),
                        ),
                );

                // what do we need to remove for each role that is unused?
                //
                // this is *almost* the same list that we check for an
                // active role ... but not quite

                $foldersToRemove = $foldersToCheck;
                $foldersToRemove['test']['folders'][] = 'src/tests';

                foreach ($foldersToCheck as $role => $folders)
                {
                        // are all the folders for this role unused?
                        $usedFolders = count($foldersToCheck[$role]['folders']);
                        foreach ($foldersToCheck[$role]['folders'] as $folder)
                        {
                                $filesWeCanDelete = array();
                                if (isset($foldersToCheck[$role]['filesWeCanDelete']))
                                {
                                        $filesWeCanDelete = $foldersToCheck[$role]['filesWeCanDelete'];
                                }

                                // var_dump('>> looking at folder ' . $folder);
                                // var_dump($filesWeCanDelete);

                                if ($this->determineIsUnusedFolder($this->folder . '/' . $folder, $filesWeCanDelete))
                                {
                                        // var_dump('>> folder is unused');
                                        $usedFolders--;
                                }
                        }

                        // we've looked inside all of the folders for this
                        // role
                        //
                        // are there any used folders at all?
                        if ($usedFolders == 0)
                        {
                                // no ... we can safely remove this role
                                $unusedRoles[$role] = $foldersToRemove[$role]['folders'];

                                // special case ... we need to update the
                                // 'test' role to say that we can get rid
                                // of any test folders for this role too
                                if (isset($foldersToCheck[$role]['testFoldersToRemove']))
                                {
                                        $foldersToCheck['test']['filesWeCanDelete'] = array_merge($foldersToCheck['test']['filesWeCanDelete'], $foldersToCheck[$role]['testFoldersToRemove']);
                                }
                        }
                }

                return $unusedRoles;
        }

        /**
         * Work out if a folder is unused, and can safely be removed
         *
         * This is here to help us work out which PEAR-Installer roles
         * are not being used in this component, so that we can delete
         * empty folders that might be annoying some users.
         *
         * We try to do this in the most paranoid way possible, because
         * we never ever want to delete a user's real work!
         *
         * @param  string $folder
         *         The full path to the folder to examine
         * @return boolean
         *         true if the folder can be safely removed, false otherwise
         */
        protected function determineIsUnusedFolder($folder, $filesWeCanDelete = array())
        {
                // step 0: catch silly programmer errors
                Contract::Preconditions(function() use ($filesWeCanDelete)
                {
                        Contract::RequireValue($filesWeCanDelete, is_array($filesWeCanDelete), '$filesWeCanDelete must be an array');
                });

                // step 1: has the user given us any files to ignore?
                if (count($filesWeCanDelete) == 0)
                {
                        $filesWeCanDelete = array
                        (
                                '.empty',
                                'dummy.php'
                        );
                }

                // step 1: does the folder exist?
                if (!is_dir($folder))
                {
                        return true;
                }

                // step 2: is the folder empty?
                $empty = true;
                $dh = opendir($folder);
                while (($entry = readdir($dh)) !== false)
                {
                        // ignore standard contents
                        if ($entry == '.')
                        {
                                continue;
                        }

                        if ($entry == '..')
                        {
                                continue;
                        }

                        // ignore our dummy files
                        if (in_array($entry, $filesWeCanDelete))
                        {
                                continue;
                        }

                        // if we get here, then we have to assume that
                        // the user is actually using the folder
                        $empty = false;
                }

                if (!$empty)
                {
                        return false;
                }

                // add additional steps here

                // all done
                // if we get here, then we assume that the folder is
                // unused
                return true;
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
                // we do not install or upgrade this file if this component
                // does not include the 'test' role
                if (!$this->testHasActiveRole('test'))
                {
                        return;
                }

                $this->copyFilesFromDataFolder(array('bootstrap.php'), '/src/tests/unit-tests/');
        }

	protected function createDummyPhpFile()
	{
               // we do not install this dummy file if this component does not
               // include the 'php' role
               if (!$this->testHasActiveRole('php'))
               {
                       return;
               }

	       $this->copyFilesFromDataFolder(array('dummy.php'), '/src/php/');
	}

        protected function createPhpUnitXmlFile()
        {
                // we do not install the phpunit.xml file if this component
                // does not have the 'test' role
                if (!$this->testHasActiveRole('test'))
                {
                        return;
                }

                $this->copyFilesFromDataFolder(array('phpunit.xml.dist'));
        }

        protected function createSrcReadmeFile()
        {
                $this->copyFilesFromDataFolder(array('src/README.txt'), '/src/');
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
         * * phpunit.xml becomes phpunit.xml.dist
         * * updated build.xml file to support components with only a
         *   subset of the standard src/ folders
         * * updated unit-tests/bootstrap.php to enable ContractLib if
         *   it is installed
         */
        protected function upgradeFrom10To11()
        {
                $this->createSrcReadmeFile();
                if (!$this->renameOrReplaceFileFromDataFolder('phpunit.xml.dist', 'phpunit.xml', '32eeecfcb95eb236a6f152b83df2a97c'))
                {
                        // unable to rename phpunit.xml
                        //
                        // we assume this is because it has been modified
                        // by the local user
                        //
                        // we will respect this, and drop our new version
                        // into place alongside their file
                        $this->copyFilesFromDataFolder(array('phpunit.xml.dist'));
                }

                $this->createBuildFile();
                $this->createBootstrapFile();
        }

        /**
         * The changes between v11 and v12 are:
         *
         * * the 'build-vendor' step in build.xml now removes the
         *   component's code and tests from the vendor/ folder
         */
        protected function upgradeFrom11To12()
        {
                $this->createBuildFile();
        }
}
