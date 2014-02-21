_Interactive SSH2 for PHP_
==========================
--------------------------------------------------------

This module is a PHP wrapper for the libssh2 extension with support for interactive sessions. With interactive session
support you can mimic a user terminal allowing you access to things such as sudo which might not typically be available
to remote sessions.


Installation
============
This project requires PHP-SSH2, which can be installed with `pecl`:

    yum install libssh2-devel
    # apt-get install libssh2-1-dev
    pecl install -f ssh2
    echo "extension=ssh2.so" > /etc/php.d/ssh2.ini
