Keypair Authentication
======================

Supported Keys
--------------
RSA and DSA keys are supported for the SSH2 protocol.

Private keys should be in PEM format, public keys should be in the OpenSSH format.


Automatic Public Key Generation
-------------------------------
When authenticating with a key-pair it is possible to pass only the private key to the `KeyCredential` and allow it to
automatically extract the public key information and format it for OpenSSH, before passing it to the ssh2 extension.

This is useful when you don't have the public key available and don't want the user to need to extract the public key.

By default, if you do this it will create a temporary file in the operating systems temp directory. This file will
contain an OpenSSH formatted public key, and is deleted when the PHP execution finishes.

If you want more control over the temporary file generation, you can call `KeyCredential#generatePublicKey()`
manually after constructing it with just a private key. The first argument will allow you to choose where to save the
public key. Calling this function will also populate your `KeyCredential`'s public key, ready for authentication.

If you want even more control, you can manually use the `KeyUtility#generateSshPublicKey()` function, which will return
the public key as a string.

