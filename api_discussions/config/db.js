const mongoose = require('mongoose');

function connect() {
    const dbUrl = process.env.MONGODB_URI || 'mongodb://127.0.0.1:27017/my_micro_services';

    mongoose.connect(dbUrl);
    mongoose.Promise = global.Promise;

    const db = mongoose.connection;
    db.on('error', console.error.bind(console, 'Connexion error on MongoDB: '));
    db.once('open', () => console.log('Connected to MongoDB'));

    return db;
}

module.exports = { connect };
