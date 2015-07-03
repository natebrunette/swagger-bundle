'use strict';

var path = require('path');
var argv = require('minimist')(process.argv.slice(2));
var mockApi = require('swagger-mock-api');

var swaggerFile = path.join(argv.file);

// mock api service
var server = mockApi({
    swaggerFile: swaggerFile
});

// mock request
var req = {
    url: argv.url,
    method: argv.method
};

// mock response
var res = {
    end: function() {},
    write: console.log,
    setHeader: function() {}
};

// handle mock request
server(req, res, function() {});