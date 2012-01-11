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

namespace Phix_Project\ComponentManager\PhixCommands;

use Phix_Project\Phix\CommandsList;
use Phix_Project\Phix\Context;
use Phix_Project\PhixExtensions\CommandInterface;
use Phix_Project\CommandLineLib\CommandLineParser;
use Phix_Project\CommandLineLib\DefinedSwitches;
use Phix_Project\CommandLineLib\DefinedSwitch;
use Phix_Project\ComponentManager\Entities\LibraryComponentFolder;
use Phix_Project\ValidationLib\MustBeIntegerInRange;

class PhpLibraryUpgrade extends ComponentCommandBase implements CommandInterface
{
        public function getCommandName()
        {
                return 'php-library:upgrade';
        }

        public function getCommandDesc()
        {
                return 'upgrade the structure of a php-library component to the latest version';
        }

        public function getCommandOptions()
        {
                $switches = new DefinedSwitches();

                $switches->addSwitch('from', 'upgrade from a given component.version, ignoring what build.properties file says')
                         ->setWithLongSwitch('from')
                         ->setWithRequiredArg('<version>', 'the component.version to upgade from')
                         ->setArgValidator(new MustBeIntegerInRange(1, LibraryComponentFolder::LATEST_VERSION - 1));

                return $switches;
        }

        public function getCommandArgs()
        {
                return array
                (
                        '<folder>'      => '<folder> is a path to an existing folder, which you must have permission to write to.',
                );
        }

        public function validateAndExecute($args, $argsIndex, Context $context)
        {
                $so = $context->stdout;
                $se = $context->stderr;

                // step 1: parse the options
                $options  = $this->getCommandOptions();
                $parser   = new CommandLineParser();
                list($parsedSwitches, $argsIndex) = $parser->parseSwitches($args, $argsIndex, $options);

                // step 2: verify the args
                $errors = $parsedSwitches->validateSwitchValues();
                if (count($errors) > 0)
                {
                        // validation failed
                        foreach ($errors as $errorMsg)
                        {
                                $se->output($context->errorStyle, $context->errorPrefix);
                                $se->outputLine(null, $errorMsg);
                        }

                        // return the error code to the caller
                        return 1;
                }

                // step 3: extract the values we need to carry on
                // var_dump($parsedSwitches);

                $upgradeFrom = null;
                if ($parsedSwitches->testHasSwitch('from'))
                {
                        $upgradeFrom = $parsedSwitches->getFirstArgForSwitch('from');
                }

                // do we have a folder to init?
                $errorCode = $this->validateFolder($args, $argsIndex, $context);
                if ($errorCode !== null)
                {
                        return $errorCode;
                }
                $folder = $args[$argsIndex];

                // has the folder already been initialised?
                $lib = new LibraryComponentFolder($folder);
                if ($lib->state != LibraryComponentFolder::STATE_NEEDSUPGRADE)
                {
                        // what do we need to tell the user to do?
                        switch ($lib->state)
                        {
                                case LibraryComponentFolder::STATE_UPTODATE:
                                        if ($upgradeFrom == null)
                                        {
                                                $se->output($context->errorStyle, $context->errorPrefix);
                                                $se->outputLine(null, "folder is already at latest version");
                                                return 1;
                                        }
                                        break;

                                case LibraryComponentFolder::STATE_EMPTY:
                                        $se->output($context->errorStyle, $context->errorPrefix);
                                        $se->outputLine(null, "folder is not a php-library component");
                                        $se->output(null, 'use ');
                                        $se->output($context->commandStyle, $context->argvZero . ' php-library:init');
                                        $se->outputLine(null, ' to initialise this folder');
                                        return 1;

                                case LibraryComponentFolder::STATE_INCOMPATIBLE:
                                        $se->output($context->errorStyle, $context->errorPrefix);
                                        $se->output($context->errorStyle, $context->errorPrefix);
                                        $se->outputLine(null, 'folder is not a php-library component');
                                        return 1;


                                default:
                                        $se->output($context->errorStyle, $context->errorPrefix);
                                        $se->outputLine(null, 'I do not know what to do with this folder');
                                        return 1;
                        }
                }

                // if we get here, we have a green light
                $lib->upgradeComponent(LibraryComponentFolder::LATEST_VERSION, $upgradeFrom);

                // if we get here, it worked (ie, no exception!!)
                $so->outputLine(null, 'Upgraded php-library component in ' . $folder . ' to the latest version');
        }
}
