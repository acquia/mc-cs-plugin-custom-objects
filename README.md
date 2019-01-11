# Custom Objects plugin for Mautic

Allows Mautic users to define custom object types with custom fields. Then create multiple custom objects of each type and associate them to the Contact or Company entities.

You'll be able to 
- filter segments by values in the custom objects
- create campaign conditions based on the values in the custom objects

## Requirements

- Plugin supports PHP 7.1+.

## Terms

### Custom Fields

Defines type of a field value like text, date, number, ... and properties like default value, is required, options, ...

### Custom Object

Defines a type of objects with same properties (fields). Imagine it as a blueprint for physical object. It has singular and plural name to make the UI smoother. But the most important Custom Object definition is list of Custom Fields. Examples: Product, Order, House.

### Custom Item

Custom Item is an instance of a Custom Object and defines 1 concrete thing. A Custom Item holds values of Custom Fields defined in Custom Object. Examples: Toaster, Order 1234, White House.

## Unit tests

With this command you can run the plugin unit tests:

`$ bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --filter CustomObjectsBundle`

*Run it from Mautic root folder*
