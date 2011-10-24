Your src/ folder
================

This src/ folder is where you put all of your code for release.  There's
a folder for each type of file that the PEAR Installer supports.

[More Info](http://blog.stuartherbert.com/php/2011/04/04/explaining-file-roles/)

  * bin/

    If you're creating any command-line tools, this is where you'd put
    them.  Files in here get installed into /usr/bin on Linux et al.

    [More info](http://blog.stuartherbert.com/php/2011/04/06/php-components-shipping-a-command-line-program/)
    [Example](https://github.com/stuartherbert/phix/tree/master/src/bin)

  * data/

    If you have any data files (any files that aren't PHP code, and which
    don't belong in the www/ folder), this is the folder to put them in.

    [More info](http://blog.stuartherbert.com/php/2011/04/11/php-components-shipping-data-files-with-your-components/)
    [Example](https://github.com/stuartherbert/ComponentManagerPhpLibrary/tree/master/src/data)

  * php/

    This is where your component's PHP code belongs.  Everything that goes
    into this folder must be PSR0-compliant, so that it works with the
    supplied autoloader.

    [More info](http://blog.stuartherbert.com/php/2011/04/05/php-components-shipping-reusable-php-code/)
    [Example](https://github.com/stuartherbert/ContractLib/tree/master/src/php)

  * tests/functional-tests/

    Right now, this folder is just a placeholder for future functionality.
    You're welcome to make use of it yourself.

  * tests/integration-tests/

    Right now, this folder is just a placeholder for future functionality.
    You're welcome to make use of it yourself.

  * tests/unit-tests/

    This is where all of your PHPUnit tests go.

    It needs to contain _exactly_ the same folder structure as the src/php/
    folder.  For each of your PHP classes in src/php/, there should be a
    corresponding test file in test/unit-tests.

    [More info](http://blog.stuartherbert.com/php/2011/08/15/php-components-shipping-unit-tests-with-your-component/)
    [Example](https://github.com/stuartherbert/ContractLib/tree/master/test/unit-tests)

  * www/

    This folder is for any files that should be published in a web server's
    DocRoot folder.

    It's quite unusual for components to put anything in this folder, but
    it is there just in case.

    [More info](http://blog.stuartherbert.com/php/2011/08/16/php-components-shipping-web-pages-with-your-components/)
