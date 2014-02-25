Command Execution
=================
When you call `Connection::execute()` you are returned an ExecutionStream object, this object allows you to receive
the command output in various ways. This is a single-use object, you cannot run additions commands on this object,
you must call `Connection::execute()` again.


Receiving Stdout Only
---------------------

    $exec = $command->execute("...");
    echo $exec->getOutput();    // will echo everything except stderr


Receiving Stdout & Stderr Separately
------------------------------------

    $exec = $command->execute("...");
    $out = $exec->getSegmentedOutput();
    echo $out['stdout'];    // the good
    echo $out['stderr'];    // the bad


Receiving Stdout & Stderr Together
----------------------------------
This can only be achieved by piping stderr to stdout -

    $exec = $command->execute("... 2>&1");
    echo $exec->getOutput();    // stderr inline with stdout
