<?php
    include_once "../auto-parse.php";

    function greet(string $name, $age) {
        return "Hello, " . htmlspecialchars($name) . ", you are " . $age . " years old!";
    }
?>

<h1><this:greet("Hellin", 20)/></h1>