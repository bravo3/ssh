Interactive SSH2 for PHP
========================
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

Connections
===========
See [Connections](docs/Connections.md)

Known Bugs/Limitations
======================
Bugs
----
This wrapper is subject to the same bugs that exist in the ssh2 extension, notable of those are the encrypted private
key files running in Ubuntu/Debian:

* https://bugs.php.net/bug.php?id=58573
* See [Encrypted Private Keys](docs/EncryptedPrivateKeys.md) for workaround

Limitations
-----------
Features implemented:

* Password and key-pair authentication
* Optional fingerprint checking
* Command execution
* Interactive shell (mimic user)

Notable features not implemented:

* Agent and host authentication
* SCP/SFTP transfers
* Tunnels
