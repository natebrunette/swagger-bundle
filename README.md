# Swagger Bundle

Symfony 2 bundle for leveraging swagger library to generate application code

# Installation

Add vcs repository to composer json:
    
    "repositories": [
        {
            "type": "git",
            "url": "https://git.nerderylabs.com/FI.swagger-bundle"
        },
        {
            "type": "git",
            "url": "https://git.nerderylabs.com/FI.swagger-php"
        }
    ]
    
* Require package `composer require nerdery/swagger-bundle`
* Install packages `composer install`

Note: This bundle should only be used in dev mode and be included as a dev only dependency. The purpose of these 
      resources are to assist the creation of an API symfony application during development 

# Usage

There are 2 main bundle parameters to you can override in your parameters.yml:

1. `swagger_bundle.swagger_file` default: Resources/docs/swagger.yaml - specify swagger file
2. `swagger_bundle.mock_api` default: true - turn on/off to deactivate the mock API listener

More deatils on each below...

# Behat Usage

Behat documentation: [behat](http://docs.behat.org/en/master/)

* Add the SwaggerContext file to your behat.yml config suite(s)

## Sample Behat Config

Sample Behat configuration using the SwaggerContext for validating json schemas

    default:
        suites:
            app:
                paths:
                    - %paths.base%/src/AppBundle/Tests/Features/
                contexts:
                    - SwaggerBundle\Behat\SwaggerContext: ~
        extensions:
            Behat\Symfony2Extension: ~
            Behat\MinkExtension:
              sessions:
                default:
                  symfony2: ~
        testers:
            rerun_cache: .behat_rerun_cache

## Context Details

### @Given I have the request payload:

Takes in a TableNode for key value pairs to be used as request payload

Format:
  | key | value |
  
### @When I request :path
### @When I request :path with method :method

Make a request to the API with or without a request method (defaults to GET)

### @When I use the :schema schema
### @When I am using the :schema schema

Store a swagger definition schema from your swagger doc to be used to validate API json response data

Note: The swagger documentation used is the file defined in the `swagger_bundle.swagger_file` parameter

### @When I test swagger path :path
### @When I test swagger path :path with operation :operation
### @When I test swagger path :path with operation :operation and response :response

Store a swagger response schema from your swagger doc to be used to validate API json response data

Note: The swagger documentation used is the file defined in the `swagger_bundle.swagger_file` parameter

### @Then the response should be json

Assert the the response is json

### @Then the response json should contain key :key

Assert that the response json contains "key"

### @Then the response json key :key should equal :value

Assert the response json key equals "value"

### @Then The json response data should be valid
### @Then The json response key :key should be valid

Validates the response data agains the previously specified swagger definition or response schema

# Mock API Usage

The mock API can be disabled by setting `swagger_bundle.mock_api` to false. Also note the listener will not run in 
prod mode if you forget to move it to require-dev or added it to the wrong are of your kernel

1. Install `app/console swagger:install:mock-api`
2. Add the `x-mock-api` header to the request to trigger the listener to trigger the mock response

### Resources

A useful chrome extension used to mock request headers
[Modheader](https://chrome.google.com/webstore/detail/modheader/idgpnmonknjnojddfkpgkljpfnnfcklj)

Additional chance methods & options that can be defined in the swagger.json doc
[chance](http://chancejs.com/)