A simple PHP script, making it much easier to print out PHP variables through simple HTML elements.
This script supports nested objects/arrays, and even very basic PHP function calling ability, all from HTML.

As this is just a prototype, there may be bugs or other issues I don't know about, use with caution.

An important caveat to consider aswell is, you MUST ensure to sanitize against XSS, as this tool will make XSS 10 times worse
Another caveat to consider is that 'this' can only be used to refer to variables/functions within the same scope as auto-parse.php was included