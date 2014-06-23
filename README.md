Interactive SSH2 for PHP
========================
This module is a PHP wrapper for the libssh2 extension with support for interactive sessions. With interactive session
support you can mimic a user terminal allowing you access to things such as sudo which might not typically be available
to remote sessions. Unlike most libssh2 libraries, you do not need a public key file to authenticate using keys pairs.

Installation
============
This project requires PHP-SSH2, which can be installed with `pecl`:

    yum install libssh2-devel
    # apt-get install libssh2-1-dev
    pecl install -f ssh2
    echo "extension=ssh2.so" > /etc/php.d/ssh2.ini

Usage
=====
* [Connections](docs/Connections.md)
* [Command Execution](docs/ExecutionStream.md)
* [Interactive Shell](docs/Shell.md)
* [Transfers](docs/Transfers.md)
* [Keypair Authentication](docs/KeypairAuthentication.md)

History
=======
1.4.2
-----
* Extended the timeout and normalise options to smart commands
* Added the ability to read from stderr only

1.4.1
-----
* Added the ability to detect remote shell type
* Smart commands now work on C-shell variants
* Added pause timeouts to all other shell read functions
* Added the ability to normalise line-endings in shell output

1.4.0
-----
* Deprecated version 1.3.x
* Fixed logic of the ssh2_tunnel() function

1.3.0
-----
* Added support to tunnel connections

1.2.0
-----
* Added ability to connect using just a private PEM key

1.1.0
-----
* Added SCP support

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
#### Features implemented:

* Password and key-pair authentication
* Optional fingerprint checking
* Command execution
* Interactive shell (mimic user)
* SCP transfers
* Public key generation (connect using just a private key)
* Tunnels (remote connections)

#### Notable features not implemented:

* Agent and host authentication
* SFTP transfers
