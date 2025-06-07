<?php
    include_once "../auto-parse.php";

    class Cookie {
        public int $tasty = 100;
        public String $type = "Chocolate Chip";
        public float $weight_grams = 5;
        public float $price = 1.25;
        public array $ingredients = ["Flour", "Sugar", "Butter", "Chocolate Chips"];

        function say_hi() {
            $local_var = $this->type;

            register_local_variable($local_var, $ref);
            echo "Hi, I'm a <registry:$ref/> cookie!";
        }
    }

    // Initializing a new Cookie object
    $cookie_obj = new Cookie();
    global $cookie_obj;
?>

<h1>Hi! Here's a cookie:</h1>
<p><cookie_obj::pjson/></p>
<p>Number of ingredients: <cookie_obj:ingredients::count/></p>

<?php
    $cookie_obj->say_hi();
?>