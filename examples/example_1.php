<?php
    include_once "../auto-parse.php";

    $hello_world = "Hello, World!";
    $_SESSION["name"] = "Hellin";

    function hi() {
        $builder = "";

        for ($i = 0; $i < 5; $i++) {
            $builder = $builder . "Hi!<br>";
        }

        return $builder;
    }
?>

<h1>Test</h1>
<h2><hello_world/></h2>
<p>Welcome to the test page, <session:name/>!</p>

<!-- This line is calling the function hi, within this scope, and is using the tilda symbol to ensure the output is not sanitized ensuring the <br> tag will work -->
<p><this:hi()~/></p>