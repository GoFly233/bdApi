'use strict';

var web = exports;
var config = require('./config');
var debug = require('debug')('pushserver:web');
var express = require('express');
var bodyParser = require('body-parser');
var compression = require('compression');

var app = express();
app.use(compression({}));
app.use(bodyParser.urlencoded({extended: true}));
app.use(bodyParser.json());

web._app = app;
web.start = function(port, deviceDb, projectDb, pushQueue, adminSections) {
    require('./web/pubhubsubbub').setup(app, '', deviceDb, pushQueue);

    if (config.web.adminPrefix &&
        config.web.username &&
        config.web.password) {
      require('./web/admin').setup(app, config.web.adminPrefix,
          config.web.username, config.web.password,
          projectDb, adminSections);
    }

    app.listen(port);
    debug('Listening on port', port, '…');

    return web;
  };
