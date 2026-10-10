<?php

/** @var \Ilch\View $this */

// Answer for the JavaScript of the payment page, see Orders::sendJson().
echo json_encode($this->get('json'));
