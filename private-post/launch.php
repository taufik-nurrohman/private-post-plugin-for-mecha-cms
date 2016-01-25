<?php

define('PRIVATE_POST_SALT', 'VGF1ZmlrIE51cnJvaG1hbg==' . date('Y-m-d')); // should be valid for 1 day

Route::accept(File::B(__DIR__) . '/do:access', function() use($config, $speak) {
    if($request = Request::post()) {
        Guardian::checkToken($request['token'], $request['kick']);
        if( ! isset($request['_'])) {
            Notify::error($speak->plugin_private_post->error);
        }
        // your answer can't contains a `:` because `:` is the separator
        // if the `:` is very important for the answer, then you must
        // replace all `:` character(s) in the password field with `&#58;`
        $request['access'] = str_replace(':', '&#58;', $request['access']);
        $access = md5($request['access'] . PRIVATE_POST_SALT);
        if((string) $request['_'] === (string) $access) {
            Session::set('is_allow_post_access', $access);
            Guardian::kick($request['kick']);
        }
        Notify::error($speak->plugin_private_post->error);
        Guardian::kick($request['kick']);
    }
    Shield::abort();
});

function do_private_post($content, $results) {
    global $config, $speak;
    $results = Mecha::O($results);
    $results = $config->is->post ? Get::postHeader($results->path, POST . DS . $config->page_type, '/', $config->page_type . ':') : false;
    if($results === false) return $speak->plugin_private_post->description;
    $s = isset($results->fields->pass) ? $results->fields->pass : "";
    if(strpos($s, ':') !== false) {
        $s = explode(':', $s, 2);
        if(isset($s[1])) $speak->plugin_private_post->hint = ltrim($s[1]); // override password hint
        $s = $s[0];
    }
    $hash = md5($s . PRIVATE_POST_SALT);
    $html = Notify::read(false) . '<div class="overlay--' . File::B(__DIR__) . '"></div><form class="form--' . File::B(__DIR__) . '" action="' . $config->url . '/' . File::B(__DIR__) . '/do:access" method="post">' . NL;
    $html .= TAB . Form::hidden('token', Guardian::token()) . NL;
    $html .= TAB . Form::hidden('_', $hash) . NL;
    $html .= TAB . Form::hidden('kick', $config->url_current) . NL;
    $html .= TAB . '<p>' . $speak->plugin_private_post->hint . '</p>' . NL;
    $html .= TAB . '<p>' . Form::text('access', "", $speak->password . '&hellip;', array('autocomplete' => 'off')) . ' ' . Form::button($speak->submit, null, 'submit') . '</p>' . NL;
    $html .= '</form>' . O_END;
    if($results && isset($results->fields->pass) && trim($results->fields->pass) !== "") {
        if( ! Guardian::happy() && Session::get('is_allow_post_access') !== $hash) return $html;
    }
    return $content;
}

$filters = Mecha::walk(glob(POST . DS . '*', GLOB_NOSORT | GLOB_ONLYDIR), function($v) {
    return File::B($v) . ':content';
});

Filter::add($filters, 'do_private_post', 30);