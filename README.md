# JSON to PHP model classes
A tool to generate PSR-2 compliant PHP model classes from JSON.

## Usage

[Online tool](https://json2php.strikebit.io/)

### Command Line
Example: 
`php ./bin/cli.php ./input-file.json User 1 1 Acme\\Entity > output.txt`

Arguments:
1. Location of your input JSON file.
2. Desired class name
3. Type hinting (0|1)
4. Fluent setters (0|1)
5. Desired namespace (optional)


