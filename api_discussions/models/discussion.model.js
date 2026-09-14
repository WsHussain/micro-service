const mongoose = require('mongoose');
const { Schema } = mongoose;

const DiscussionSchema = new Schema(
    {
        title: { type: String, required: true, trim: true },
        participants: {
            type: [Number],
            required: true,
            validate: {
                validator: (v) => Array.isArray(v) && v.length > 0,
                message: 'participants must be a non-empty array of user ids',
            },
        },
        messageIds: { type: [Number], default: [] },
    },
    { timestamps: true }
);

module.exports = mongoose.model('Discussion', DiscussionSchema);
