require('dotenv').config();

const express = require('express');
const bodyParser = require('body-parser');
const { connect } = require('./config/db');
const discussionRoutes = require('./routes/discussion.route');

connect();

const app = express();

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));

app.use('/discussions', discussionRoutes);

const port = process.env.PORT || 5555;

app.listen(port, () => {
    console.log('Server running on: ' + port);
});

module.exports = app;
