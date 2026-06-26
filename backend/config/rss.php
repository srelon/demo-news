<?php

return [

    // Minimum body length (plain text, characters) for an imported article
    'min_body_length' => 300,

    // Items containing any of these words in title or body are rejected.
    // NOTE: matching is substring-based, so only include strings that
    // cannot appear inside normal words (no 'ass', 'anal', 'sex', etc.)
    'blacklist' => [
        // Russian
        'реклама',
        'промокод',
        'казино',
        'букмекер',
        '18+',

        // English profanity
        'fuck',
        'shit',
        'bitch',
        'cunt',
        'asshole',
        'dickhead',
        'faggot',
        'nigger',
        'nigga',
        'wanker',
        'bollocks',
        'twat',
        'slut',
        'whore',

        // Adult content
        'porn',
        'xxx',
        'hentai',
        'erotic',
        'blowjob',
        'handjob',
        'milf',
        'onlyfans',

        // Gambling / spam
        'casino',
        'viagra',
        'cialis',
        'promo code',
        'betting bonus',
        'free spins',
    ],

    // Entries whose link contains any of these substrings are skipped:
    // live blogs and galleries are streams, not articles, and extract as garbage
    'skip_url_patterns' => [
        '/live/',
        '/gallery/',
        '/video/',
        '/videos/',
        '/audio/',
    ],

    // Lines (whole element text) removed from extracted bodies:
    // timestamps, photo credits and embedded call-to-action blocks
    'junk_lines' => [
        '/^\d+\s+(minute|hour|day)s?\s+ago$/i',
        '/^(pa media|getty images|reuters|afp|epa|ap photo)$/i',
        '/^get in touch$/i',
        '/^send us your questions$/i',
        '/^contact form$/i',
        '/^(read|watch|listen) more.{0,60}$/i',
        '/^related (articles|topics|stories)$/i',
        '/^(share|comments?)$/i',
        '/^follow .{0,60} on (x|twitter|instagram|facebook|telegram).{0,30}$/i',
    ],

    // Automatic tag matching for imported articles:
    // a title hit scores 5, each body hit scores 1; tags below
    // min_score are skipped, top `max` by score are attached
    'auto_tags' => [
        'max' => 5,
        'min_score' => 2,
    ],

    // HTTP timeout for feed and article page requests, seconds
    'http_timeout' => 15,

    // Max items processed per source per run
    'max_items_per_run' => 20,

];
