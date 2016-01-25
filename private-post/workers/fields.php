<?php

$scopes = Mecha::walk(glob(POST . DS . '*', GLOB_NOSORT | GLOB_ONLYDIR), function($v) {
    return File::B($v);
});

return array(
    'pass' => array(
        'title' => $speak->password,
        'type' => 'text',
        'scope' => implode(',', $scopes),
        'description' => $speak->plugin_private_post->__description
    )
);