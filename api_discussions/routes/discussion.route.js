const express = require('express');
const router = express.Router();
const controller = require('../controllers/discussion.controller');

router.get('/', controller.list);
router.post('/', controller.create);
router.get('/:id', controller.get);
router.put('/:id', controller.update);
router.post('/:id/messages', controller.addMessage);
router.delete('/:id', controller.remove);

module.exports = router;
