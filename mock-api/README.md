# Purpose

The purpose of this micro application is to provide mock API data for FE development during BE API dev efforts. 

!!! DO NOT use this in production !!!

Notes:

The API application includes a Symfony MockApi event listener class & service that are configured to listen for 
requests the contain a `x-mock-api` request header. The listener will return early in the event that either:

a) the `x-mock-api` header is not present
b) the application is currently running in prod mode

# Requirements

Note: Update apt-get before installing node `sudo apt-get update`

* [node](https://nodejs.org/)
* [npm](https://www.npmjs.com/)
* [grunt](http://gruntjs.com/)

# Installation

1. Install packages `npm install`

## Resources

A useful chrome extension used to mock request headers
[Modheader](https://chrome.google.com/webstore/detail/modheader/idgpnmonknjnojddfkpgkljpfnnfcklj)

Additional chance methods & options that can be defined in the swagger.json doc
[chance](http://chancejs.com/)