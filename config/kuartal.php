<?php

/**
 * kuartalimpact.org: Kuartal's arm for social, environmental and climate
 * causes. Site chrome (nav, footer, contacts, social) lives here.
 */
return [

    'site_name' => env('KUARTAL_SITE_NAME', 'Kuartal Impact'),
    'site_tag' => env('KUARTAL_SITE_TAG', 'Impact'),

    // General enquiries: the address the previous kuartalimpact.org listed.
    'contact_email' => env('KUARTAL_CONTACT_EMAIL', 'hello@kuartalimpact.org'),

    'contacts' => [
        ['key' => 'general', 'label' => 'General enquiries', 'email' => env('KUARTAL_CONTACT_EMAIL', 'hello@kuartalimpact.org'),
            'body' => 'Founders, partners, communities and anyone with a question about Kuartal Impact.'],
        ['key' => 'investors', 'label' => 'Investors', 'email' => env('KUARTAL_IR_EMAIL', 'ir@kuartal.id'),
            'body' => 'Co-investment and limited-partner enquiries across Kuartal Group.'],
        ['key' => 'media', 'label' => 'Media', 'email' => env('KUARTAL_MEDIA_EMAIL', 'media@kuartal.id'),
            'body' => 'Press, interviews and brand assets.'],
    ],

    'nav' => [
        ['label' => 'About', 'url' => '/about', 'links' => [
            ['label' => 'Our thesis', 'url' => '/about#thesis'],
            ['label' => 'How we invest', 'url' => '/about#how-we-invest'],
            ['label' => 'Team', 'url' => '/about#team'],
        ]],
        ['label' => 'Focus Areas', 'url' => '/focus-areas'],
        ['label' => 'Portfolio', 'url' => '/portfolio'],
        ['label' => 'Programmes', 'url' => '/programmes'],
        ['label' => 'Newsroom', 'url' => '/newsroom'],
        ['label' => 'Contact', 'url' => '/contact'],
    ],

    'footer' => [
        ['heading' => 'Kuartal Impact', 'links' => [
            ['label' => 'About', 'url' => '/about'],
            ['label' => 'Our thesis', 'url' => '/about#thesis'],
            ['label' => 'Team', 'url' => '/about#team'],
        ]],
        ['heading' => 'Work', 'links' => [
            ['label' => 'Focus areas', 'url' => '/focus-areas'],
            ['label' => 'Portfolio', 'url' => '/portfolio'],
            ['label' => 'Programmes', 'url' => '/programmes'],
        ]],
        ['heading' => 'News', 'links' => [
            ['label' => 'Newsroom', 'url' => '/newsroom'],
            ['label' => 'RSS feed', 'url' => '/newsroom/feed'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]],
        ['heading' => 'Kuartal', 'links' => [
            ['label' => 'Kuartal.id', 'url' => 'https://kuartal.id'],
            ['label' => 'Kuartal Group', 'url' => 'https://kuartalgroup.com'],
            ['label' => 'Careers', 'url' => 'https://careers.kuartal.id'],
        ]],
    ],

    'social' => [
        ['label' => 'Facebook',  'icon' => 'facebook',  'url' => 'https://facebook.com/kuartal.id'],
        ['label' => 'Instagram', 'icon' => 'instagram', 'url' => 'https://instagram.com/kuartal_id'],
        ['label' => 'LinkedIn',  'icon' => 'linkedin',  'url' => 'https://linkedin.com/company/kuartal'],
        ['label' => 'YouTube',   'icon' => 'youtube',   'url' => 'https://youtube.com/@kuartal'],
        ['label' => 'X',         'icon' => 'x',          'url' => 'https://x.com/kuartal_id'],
    ],

];
