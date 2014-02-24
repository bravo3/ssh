Encrypted Private Keys
======================

A known issue exists (and as of PHP 5.5, still exists) whereby:

    ssh2_auth_pubkey_file() is broken when the public key file is protected with a password AND libssh2 is compiled
    with libgcrypt, which is what debian/ubuntu and probably others do. I’m working on a solution for this bug, but
    if you need this working rebuild libssh2 yourself with OpenSSL.

The Problem
-----------
By default, Ubuntu/Debian encrypted private keys won't work.

The Solution
------------
All you need to do is make sure your encrypted private key is in PEM format, which is easy to convert:

    openssl rsa -in privatekey -outform pem > privatekey.pem
