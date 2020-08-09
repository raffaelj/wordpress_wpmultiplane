<?php

$this->module('privacy')->extend([

    'name'        => 'privacy',
    'title'       => 'Privacy',
    'description' => 'Cookie Popup - Mit etwas HTML-Kenntnissen lassen sich die Texte leicht ändern - aber bitte nichts mit "{{ ... }}" oder "@base(...)" etc. anfassen, wenn du dir nicht sicher bist, was das bedeutet. Der Inhalt wird direkt via PHP ausgeführt und es gibt keine Sicherheits-Checks, ob die Eingabe korrekt oder gefährlich ist.',
    'hasSettings' => true,

]);

$this('settings')->addOptions([
    [
        'id'      => 'popup_content',
        'title'   => 'Cookie Popup (de)',
        'type'    => 'code',
        'storage' => '#storage:privacy/privacy-notice.php',
    ],
    [
        'id'      => 'popup_content_en',
        'title'   => 'Cookie Popup (en)',
        'type'    => 'code',
        'storage' => '#storage:privacy/privacy-notice_en.php',
    ]
], 'privacy');
