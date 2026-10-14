<?php

/**
 * kuartalimpact.org: admin entitlement, newsroom, media and the
 * admin-managed content types (see docs/HANDOFF.md).
 */
return [

    // Kuartal ID entitlement keys (kuartal-login CatalogSeeder).
    'entitlements' => [
        'admin' => env('KUARTAL_ID_ADMIN_ENTITLEMENT', 'kuartalimpact.admin'),
    ],

    'post_categories' => [
        'announcement' => 'Announcement',
        'press_release' => 'Press release',
        'portfolio' => 'Portfolio',
        'programme' => 'Programme',
        'field_notes' => 'Field notes',
        'research' => 'Research',
    ],

    // Media library: images + PDFs only (no SVG: it can carry script).
    'media' => [
        'image_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'pdf_extensions' => ['pdf'],
        'max_image_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 5120),
        'max_pdf_kb' => (int) env('MEDIA_MAX_PDF_KB', 20480),
        'max_image_pixels' => 40_000_000,
    ],

    // Admin-managed site content (App\Models\SiteEntry types).
    'entry_types' => [
        'fund' => 'Fund (programme)',
        'portfolio' => 'Project / portfolio company',
        'person' => 'Team member',
        'partner' => 'Partner',
        'metric' => 'Fund figure',
        'block' => 'Text block',
    ],

    // Focus areas (thesis). A fund's SiteEntry.group is one of these keys.
    'focus_areas' => [
        'climate' => ['label' => 'Climate', 'code' => 'FA-01',
            'body' => 'Capital and technology for cutting emissions, adapting to a warming climate and building clean energy where it is needed most.'],
        'environment' => ['label' => 'Environment', 'code' => 'FA-02',
            'body' => 'Protecting and restoring the landscapes, oceans and biodiversity that people and economies depend on.'],
        'social' => ['label' => 'Social', 'code' => 'FA-03',
            'body' => 'Access to health, education and opportunity for low-income and underserved communities.'],
        'ai_tech' => ['label' => 'AI & technology', 'code' => 'FA-04',
            'body' => 'AI and technology aimed at humanity\'s biggest existential threats, built and governed responsibly.'],
        'grassroots' => ['label' => 'Grassroots solutions', 'code' => 'FA-05',
            'body' => 'Backing local founders and community-led solutions that already work on the ground, and helping them scale.'],
    ],

    'person_groups' => [
        'team' => 'Team',
        'advisors' => 'Advisors',
    ],

    // Text blocks the public pages read (shown only once an admin fills them).
    'blocks' => [
        'home.quote' => 'Home: founder quote',
        'home.quote_by' => 'Home: quote attribution (name, role)',
        'about.how_we_invest' => 'About: how we invest',
        'about.team_intro' => 'About: team introduction',
        'portfolio.intro' => 'Portfolio: framing paragraph',
        'programmes.intro' => 'Programmes: framing paragraph (funds)',
    ],
];
