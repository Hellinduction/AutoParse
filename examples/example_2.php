<?php
    include_once "../auto-parse.php";

    class Cookie {
        public int $tasty = 100;
        public String $type = "Chocolate Chip";
        public float $weight_grams = 5;
        public float $price = 1.25;
        public array $ingredients = ["Flour", "Sugar", "Butter", "Chocolate Chips"];
    }

    // Initializing a new Cookie object
    $cookie_obj = new Cookie();
    global $cookie_obj;
?>

<h1>Hi! Here's a cookie:</h1>
<p><cookie_obj::pjson/></p>