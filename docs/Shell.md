Interactive Shell
=================
It's important to realise that a shell is not a command interface, it's a read/write stream - you must sync your
write data with the state of the server, and it's your responsibility to understand when the server is ready to receive
data.

To make this easier, the Shell class provides a "smart command" feature, which will set the PS1 variable on the server
and auto-detect the command echo and PS1 output, so that you get a response in the way you would expect if you had
used Connection::execute().


Waiting For The Welcome Message
-------------------------------
Once authenticated, the server may take a bit of time before it spits out it's welcome message. You need to both wait
for some initial data, and then for a pause. There is a helper function that does this for you -

    $shell = $connection->getShell();
    $shell->waitForContent(1.5);    // wait 1.5 seconds before assuming the server is done
    $shell->sendln(..);


Smart Commands
--------------
By default, a smart command will automatically override the PS1 variable on the server and trim the response -

    $shell = $connection->getShell();
    $response = $shell->sendSmartCommand("echo hello world");
    // $response will be 'hello world' (new lines trimmed)

You can chose not to trim the response (so it includes the command echo and PS1 suffix) by using the second parameter
of the Shell::sendSmartCommand() function
