<?php

test('the sign in page is reachable', function () {
    $this->get(route('login'))->assertOk();
});
