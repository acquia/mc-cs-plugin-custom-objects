# Custom Objects plugin for Mautic

Allows Mautic users to define custom objects with custom fields. Then create multiple custom items of each type and associate them to the Contact or Company entities.

You'll be able to 
- filter segments by values in the custom objects
- create campaign conditions based on the values in the custom objects

## Glossary

- `Custom Field` represents one piece of information. Example: Price, Description, Color
- `Custom Object` is set of custom fields that will allow users to create multiple instances of this object. Example: Product, Invoice.
- `Custom Item` is created when Custom Object fields are populated with specific information. Example: Mautic T-shirt, Invoice 2022-02-22-123.

## Example Usage

As an example we can create a `custom object` **Proudct**. To create one go to the right hand side admin menu. This custom object will have these `custom fields`:
- Name is always there by default. No need to create a custom field for it.
- Price _(Number field)_
- Description _(Textarea field)_
- Color _(Select box with options: Red, Green, Blue)_

Once we save such custom object then on the left hand side menu we'll be able to see new **Product** menu item and we'll be able to start creating new product **custom items**:

Product 1:
Name: Mautic T-shirt
Price: $123
Description: Great T-shirt to support your favorite project and market it whenever you walk!
Color: Blue

Product 2:
Name: Mautic Hoodie
Price: $153
Description: Great hoodie to support your favorite project and market it whenever you walk and stay worm at the same time!
Color: Red

Once some of your contacts buy a Mautic T-shirt or hoodie you can link them with the product they've bought. You can automate that with a Mautic campaign action, API or CSV import. Once these links are established you'll be able to build segments based on who bought what or if they for example bought products with price greater than $100 or products of red color. Then your automated workflows can hit the right audience.

## Requirements

- Plugin supports PHP 7.1+.

## Documentation

See [Wiki](wiki)

## Tests

The plugin has currently test coverage of 91%. Each new PR must be covered by tests to be considered to be merged. To run the tests execute `composer test -- --filter CustomObjects` from the Mautic root dir.
