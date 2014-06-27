<?php

/**
 * Unit test properties
 *
 * To override any of the default properties, duplicate this file as 'properties.php' and replace any
 * members of the TestProperties class that you wish to change
 */
class properties extends TestProperties
{
    public static $host = 'localhost';
    public static $port = 22;

    public static $user = 'ssh-test';
    public static $pass = 'ssh-test-password';

}
