const mongoose = require('mongoose');
const Discussion = require('../models/discussion.model');

function isValidId(id) {
    return mongoose.Types.ObjectId.isValid(id);
}

exports.list = async (req, res) => {
    const discussions = await Discussion.find();
    res.json(discussions);
};

exports.create = async (req, res) => {
    const { title, participants } = req.body;

    if (!title || !Array.isArray(participants) || participants.length === 0) {
        return res.status(422).json({ error: 'title and a non-empty participants array are required' });
    }

    try {
        const discussion = await Discussion.create({ title, participants });
        res.status(201).json(discussion);
    } catch (err) {
        res.status(422).json({ error: err.message });
    }
};

exports.get = async (req, res) => {
    if (!isValidId(req.params.id)) {
        return res.status(400).json({ error: 'invalid id' });
    }

    const discussion = await Discussion.findById(req.params.id);

    if (!discussion) {
        return res.status(404).json({ error: 'not found' });
    }

    res.json(discussion);
};

exports.update = async (req, res) => {
    if (!isValidId(req.params.id)) {
        return res.status(400).json({ error: 'invalid id' });
    }

    const updates = {};
    if (req.body.title) updates.title = req.body.title;
    if (Array.isArray(req.body.participants)) updates.participants = req.body.participants;
    if (Array.isArray(req.body.messageIds)) updates.messageIds = req.body.messageIds;

    try {
        const discussion = await Discussion.findByIdAndUpdate(req.params.id, updates, {
            new: true,
            runValidators: true,
        });

        if (!discussion) {
            return res.status(404).json({ error: 'not found' });
        }

        res.json(discussion);
    } catch (err) {
        res.status(422).json({ error: err.message });
    }
};

exports.addMessage = async (req, res) => {
    if (!isValidId(req.params.id)) {
        return res.status(400).json({ error: 'invalid id' });
    }

    const { messageId } = req.body;

    if (typeof messageId !== 'number') {
        return res.status(422).json({ error: 'messageId (number) is required' });
    }

    const discussion = await Discussion.findByIdAndUpdate(
        req.params.id,
        { $addToSet: { messageIds: messageId } },
        { new: true }
    );

    if (!discussion) {
        return res.status(404).json({ error: 'not found' });
    }

    res.json(discussion);
};

exports.remove = async (req, res) => {
    if (!isValidId(req.params.id)) {
        return res.status(400).json({ error: 'invalid id' });
    }

    const discussion = await Discussion.findByIdAndDelete(req.params.id);

    if (!discussion) {
        return res.status(404).json({ error: 'not found' });
    }

    res.status(204).send();
};
