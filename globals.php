<?php
/**
 * These are global functions that are used throughout the system, and used in the coaching system. There is a copy of this file in the coaching system.
 * If changes are made here, they need copied to the coaching plugin.
 * All sql queries should not use variable table names, but should be fully qualified.
 */

if ( !function_exists( 'zume_mirror_url' ) ) {
    function zume_mirror_url() {
        return 'https://storage.googleapis.com/zume-file-mirror/';
    }
}
if ( !function_exists( 'zume_landing_page_post_id' ) ) {
    function zume_landing_page_post_id( int $number ): int {
        /**
         * These are the root post ids for the english page, which is used to find the translation page in the
         * polylang system.
         */
        $list = array(
            1 => 20730, // God uses ordinary people
            2 => 20731, // teach them to obey
            3 => 20732, // spiritual breathing
            4 => 20733, // soaps bible reading
            5 => 20735, // accountability groups
            6 => 20737, // consumers vs producers
            7 => 20738, // prayer cycle
            8 => 20739, // list of 100
            9 => 20740, // kingdom economy
            10 => 20741, // the gospel
            11 => 20742, // baptism
            12 => 20743, // 3-minute testimony
            13 => 20744, // greatest blessing
            14 => 20745, // duckling discipleship
            15 => 20746, // seeing where God's kingdom isn't
            16 => 20747, // the lord's supper
            17 => 20748, // prayer walking
            18 => 20750, // person of peace
            19 => 20749, // bless prayer
            20 => 20751, // faithfulness
            21 => 20752, // 3/3 group pattern
            22 => 20753, // training cycle
            23 => 20755, // leadership cells
            24 => 20756, // non-sequential
            25 => 20757, // pace
            26 => 20758, // part of two churches
            27 => 19848, // 3-month plan
            28 => 20759, // coaching checklist
            29 => 20760, // leadership in networks
            30 => 20761, // peer mentoring groups
            31 => 20762, // four fields tool
            32 => 20763, // generation mapping
            20730 => 1, // God uses ordinary people
            20731 => 2, // teach them to obey
            20732 => 3, // spiritual breathing
            20733 => 4, // soaps bible reading
            20735 => 5, // accountability groups
            20737 => 6, // consumers vs producers
            20738 => 7, // prayer cycle
            20739 => 8, // list of 100
            20740 => 9, // kingdom economy
            20741 => 10, // the gospel
            20742 => 11, // baptism
            20743 => 12, // 3-minute testimony
            20744 => 13, // greatest blessing
            20745 => 14, // duckling discipleship
            20746 => 15, // seeing where God's kingdom isn't
            20747 => 16, // the lord's supper
            20748 => 17, // prayer walking
            20750 => 18, // person of peace
            20749 => 19, // bless prayer
            20751 => 20, // faithfulness
            20752 => 21, // 3/3 group pattern
            20753 => 22, // training cycle
            20755 => 23, // leadership cells
            20756 => 24, // non-sequential
            20757 => 25, // pace
            20758 => 26, // part of two churches
            19848 => 27, // 3-month plan
            20759 => 28, // coaching checklist
            20760 => 29, // leadership in networks
            20761 => 30, // peer mentoring groups
            20762 => 31, // four fields tool
            20763 => 32, // generation mapping

        );

        return $list[$number] ?? 0;
    }
}
if ( !function_exists( 'zume_languages' ) ) {
    function zume_languages() {
        global $zume_languages;
        $zume_languages = array(
            array(
                'enDisplayName' => 'English',
                'code' => 'en',
                'locale' => 'en',
                'nativeName' => 'English',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Arabic',
                'code' => 'ar',
                'locale' => 'ar_LB',
                'nativeName' => 'العربية',
                'rtl' => true
            ),
            array(
                'enDisplayName' => 'Arabic (JO)',
                'code' => 'ar_jo',
                'locale' => 'ar_JO',
                'nativeName' => 'العربية - الأردن',
                'rtl' => true
            ),
            array(
                'enDisplayName' => 'American Sign Language',
                'code' => 'asl',
                'locale' => 'asl',
                'nativeName' => 'Sign Language',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Bhojpuri',
                'code' => 'bho',
                'locale' => 'bho',
                'nativeName' => 'भोजपुरी',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Bengali (India)',
                'code' => 'bn',
                'locale' => 'bn_IN',
                'nativeName' => 'বাংলা',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Bosnian',
                'code' => 'bs',
                'locale' => 'bs_BA',
                'nativeName' => 'Bosanski',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Cantonese (Traditional)',
                'code' => 'zhhk',
                'locale' => 'zh_HK',
                'nativeName' => '粵語 (繁體)',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Chinese (Simplified)',
                'code' => 'zhcn',
                'locale' => 'zh_CN',
                'nativeName' => '国语（简体)',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Chinese (Traditional)',
                'code' => 'zhtw',
                'locale' => 'zh_TW',
                'nativeName' => '國語（繁體)',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Croatian',
                'code' => 'hr',
                'locale' => 'hr',
                'nativeName' => 'Hrvatski',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Farsi/Persian',
                'code' => 'fa',
                'locale' => 'fa_IR',
                'nativeName' => 'فارسی',
                'rtl' => true
            ),
            array(
                'enDisplayName' => 'French',
                'code' => 'fr',
                'locale' => 'fr_FR',
                'nativeName' => 'Français',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'German',
                'code' => 'de',
                'locale' => 'de_DE',
                'nativeName' => 'Deutsch',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Gujarati',
                'code' => 'gu',
                'locale' => 'gu',
                'nativeName' => 'ગુજરાતી',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Hausa',
                'code' => 'ha',
                'locale' => 'ha_NG',
                'nativeName' => 'Hausa',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Hindi',
                'code' => 'hi',
                'locale' => 'hi_IN',
                'nativeName' => 'हिन्दी',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Indonesian',
                'code' => 'id',
                'locale' => 'id_ID',
                'nativeName' => 'Bahasa Indonesia',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Italian',
                'code' => 'it',
                'locale' => 'it_IT',
                'nativeName' => 'Italiano',
                'rtl' => false
            ),

            array(
                'enDisplayName' => 'Kannada',
                'code' => 'kn',
                'locale' => 'kn',
                'nativeName' => 'ಕನ್ನಡ',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Korean',
                'code' => 'ko',
                'locale' => 'ko_KR',
                'nativeName' => '한국어',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Kurdish',
                'code' => 'ku',
                'locale' => 'ku',
                'nativeName' => 'کوردی',
                'rtl' => true
            ),
            array(
                'enDisplayName' => 'Lao',
                'code' => 'lo',
                'locale' => 'lo',
                'nativeName' => 'ພາສາລາວ',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Maithili',
                'code' => 'mai',
                'locale' => 'mai',
                'nativeName' => '𑒧𑒻𑒟𑒱𑒪𑒲',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Marathi',
                'code' => 'mr',
                'locale' => 'mr',
                'nativeName' => 'मराठी',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Malayalam',
                'code' => 'ml',
                'locale' => 'ml',
                'nativeName' => 'മലയാളം',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Nepali',
                'code' => 'ne',
                'locale' => 'ne_NP',
                'nativeName' => 'नेपाली',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Oriya',
                'code' => 'or',
                'locale' => 'or_IN',
                'nativeName' => 'ଓଡ଼ିଆ',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Punjabi',
                'code' => 'pa',
                'locale' => 'pa_IN',
                'nativeName' => 'ਪੰਜਾਬੀ',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Portuguese',
                'code' => 'pt',
                'locale' => 'pt_PT',
                'nativeName' => 'Português',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Russian',
                'code' => 'ru',
                'locale' => 'ru_RU',
                'nativeName' => 'Русский',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Romanian',
                'code' => 'ro',
                'locale' => 'ro_RO',
                'nativeName' => 'Română',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Slovenian',
                'code' => 'sl',
                'locale' => 'sl_Sl',
                'nativeName' => 'Slovenščina',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Spanish',
                'code' => 'es',
                'locale' => 'es',
                'nativeName' => 'Español',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Somali',
                'code' => 'so',
                'locale' => 'so',
                'nativeName' => 'Soomaali',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Swahili',
                'code' => 'swa',
                'locale' => 'swa',
                'nativeName' => 'Kiswahili',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Tamil',
                'code' => 'ta',
                'locale' => 'ta_IN',
                'nativeName' => 'தமிழ்',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Telugu',
                'code' => 'te',
                'locale' => 'te',
                'nativeName' => 'తెలుగు',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Thai',
                'code' => 'th',
                'locale' => 'th',
                'nativeName' => 'ไทย',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Turkish',
                'code' => 'tr',
                'locale' => 'tr_TR',
                'nativeName' => 'Türkçe',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Urdu',
                'code' => 'ur',
                'locale' => 'ur',
                'nativeName' => 'اردو',
                'rtl' => true
            ),
            array(
                'enDisplayName' => 'Vietnamese',
                'code' => 'vi',
                'locale' => 'vi',
                'nativeName' => 'Tiếng Việt',
                'rtl' => false
            ),
            array(
                'enDisplayName' => 'Yoruba',
                'code' => 'yo',
                'locale' => 'yo',
                'nativeName' => 'Yorùbá',
                'rtl' => false
            )
        );
    }
}

if ( ! function_exists( 'zume_training_items' ) ) {
    function zume_training_items() : array {

        $training_items = [
            "01" => [
                "key" => "01",
                "title" => "God Uses Ordinary People",
                "description" => "You'll see how God uses ordinary people doing simple things to make a big impact.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "02" => [
                "key" => "02",
                "title" => "Simple Definition of Disciple and Church",
                "description" => "Discover the essence of being a disciple, making a disciple, and what is the church.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "03" => [
                "key" => "03",
                "title" => "Spiritual Breathing is Hearing and Obeying God",
                "description" => "Being a disciple means we hear from God and we obey God.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "04" => [
                "key" => "04",
                "title" => "SOAPS Bible Reading",
                "description" => "A tool for daily Bible study that helps you understand, obey, and share God’s Word.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "05" => [
                "key" => "05",
                "title" => "Accountability Groups",
                "description" => "A tool for two or three people of the same gender to meet weekly and encourage each other in areas that are going well and reveal areas that need correction.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "06" => [
                "key" => "06",
                "title" => "Consumer vs Producer Lifestyle",
                "description" => "You'll discover the four main ways God makes everyday followers more like Jesus.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "07" => [
                "key" => "07",
                "title" => "How to Spend an Hour in Prayer",
                "description" => "See how easy it is to spend an hour in prayer.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "08" => [
                "key" => "08",
                "title" => "Relational Stewardship – List of 100",
                "description" => "A tool designed to help you be a good steward of your relationships.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "09" => [
                "key" => "09",
                "title" => "The Kingdom Economy",
                "description" => "Learn how God's economy is different from the world's. God invests more in those who are faithful with what they've already been given.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "10" => [
                "key" => "10",
                "title" => "The Gospel and How to Share It",
                "description" => "Learn a way to share God’s Good News from the beginning of humanity all the way to the end of this age.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "11" => [
                "key" => "11",
                "title" => "Baptism and How To Do It",
                "description" => "Jesus said, “Go and make disciples of all nations, BAPTIZING them in the name of the Father and of the Son and of the Holy Spirit…” Learn how to put this into practice.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "12" => [
                "key" => "12",
                "title" => "Prepare Your 3-Minute Testimony",
                "description" => "Learn how to share your testimony in three minutes by sharing how Jesus has impacted your life.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "13" => [
                "key" => "13",
                "title" => "Vision Casting the Greatest Blessing",
                "description" => "Learn a simple pattern of making not just one follower of Jesus but entire spiritual families who multiply for generations to come.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "14" => [
                "key" => "14",
                "title" => "Duckling Discipleship – Leading Immediately",
                "description" => "Learn what ducklings have to do with disciple-making.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "15" => [
                "key" => "15",
                "title" => "Eyes to See Where the Kingdom Isn’t",
                "description" => "Begin to see where God’s Kingdom isn’t. These are usually the places where God wants to work the most.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "16" => [
                "key" => "16",
                "title" => "The Lord’s Supper and How To Lead It",
                "description" => "It’s a simple way to celebrate our intimate connection and ongoing relationship with Jesus. Learn a simple way to celebrate.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "17" => [
                "key" => "17",
                "title" => "Prayer Walking and How To Do It",
                "description" => "It’s a simple way to obey God’s command to pray for others. And it's just what it sounds like — praying to God while walking around!",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "18" => [
                "key" => "18",
                "title" => "A Person of Peace and How To Find One",
                "description" => "Learn who a person of peace might be and how to know when you've found one.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "19" => [
                "key" => "19",
                "title" => "The BLESS Prayer Pattern",
                "description" => "Practice a simple mnemonic to remind you of ways to pray for others.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "20" => [
                "key" => "20",
                "title" => "Faithfulness is Better Than Knowledge",
                "description" => "It's important what disciples know — but it's much more important what they DO with what they know.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "21" => [
                "key" => "21",
                "title" => "3/3 Group Meeting Pattern",
                "description" => "A 3/3 Group is a way for followers of Jesus to meet, pray, learn, grow, fellowship and practice obeying and sharing what they've learned. In this way, a 3/3 Group is not just a small group but a Simple Church.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "22" => [
                "key" => "22",
                "title" => "Training Cycle for Maturing Disciples",
                "description" => "Learn the training cycle and consider how it applies to disciple making.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "23" => [
                "key" => "23",
                "title" => "Leadership Cells",
                "description" => "A Leadership Cell is a way someone who feels called to lead can develop their leadership by practicing serving.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "24" => [
                "key" => "24",
                "title" => "Expect Non-Sequential Growth",
                "description" => "See how disciple making doesn't have to be linear. Multiple things can happen at the same time.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "25" => [
                "key" => "25",
                "title" => "Pace of Multiplication Matters",
                "description" => "Multiplying matters and multiplying quickly matters even more. See why pace matters.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "26" => [
                "key" => "26",
                "title" => "Always Part of Two Churches",
                "description" => "Learn how to obey Jesus' commands by going AND staying.",
                "type" => "concept",
                "host" => true,
                "mawl" => true,
            ],
            "27" => [
                "key" => "27",
                "title" => "Three-Month Plan",
                "description" => "Create and share your plan for how you will implement the Zúme tools over the next three months.",
                "type" => "tool",
                "host" => true,
                "mawl" => false,
            ],
            "28" => [
                "key" => "28",
                "title" => "Coaching Checklist",
                "description" => "A powerful tool you can use to quickly assess your own strengths and vulnerabilities when it comes to making disciples who multiply.",
                "type" => "tool",
                "host" => true,
                "mawl" => false,
            ],
            "29" => [
                "key" => "29",
                "title" => "Leadership in Networks",
                "description" => "Learn how multiplying churches stay connected and live life together as an extended, spiritual family.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "30" => [
                "key" => "30",
                "title" => "Peer Mentoring Groups",
                "description" => "This is a group that consists of people who are leading and starting 3/3 Groups. It also follows a 3/3 format and is a powerful way to assess the spiritual health of God’s work in your area.",
                "type" => "concept",
                "host" => true,
                "mawl" => false,
            ],
            "31" => [
                "key" => "31",
                "title" => "Four Fields Tool",
                "description" => "The four fields diagnostic chart is a simple tool to be used by a leadership cell to reflect on the status of current efforts and the kingdom activity around them.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
            "32" => [
                "key" => "32",
                "title" => "Generational Mapping",
                "description" => "Generation mapping is another simple tool to help leaders in a movement understand the growth around them.",
                "type" => "tool",
                "host" => true,
                "mawl" => true,
            ],
        ];

        $list = [];
        foreach( $training_items as $training_item ) {
            $index = $training_item["key"];
            $list[] = [
                "key" => $index,
                "type" => $training_item["type"],
                "title" => $training_item["title"],
                "description" => $training_item["description"],
                "host" => $training_item["host"] ? [
                    [
                        "label" => "Heard",
                        "short_label" => "H",
                        "type" => "training",
                        "subtype" => $index."_heard",
                    ],
                    [
                        "label" => "Obeyed",
                        "short_label" => "O",
                        "type" => "training",
                        "subtype" => $index."_obeyed",
                    ],
                    [
                        "label" => "Shared",
                        "short_label" => "S",
                        "type" => "training",
                        "subtype" => $index."_shared",
                    ],
                    [
                        "label" => "Trained",
                        "short_label" => "T",
                        "type" => "training",
                        "subtype" => $index."_trained",
                    ],
                ] : [],
                "mawl" => $training_item["mawl"] ? [
                    [
                        "label" => "Modeling",
                        "short_label" => "M",
                        "type" => "coaching",
                        "subtype" => $index."_modeling",
                    ],
                    [
                        "label" => "Assisting",
                        "short_label" => "A",
                        "type" => "coaching",
                        "subtype" => $index."_assisting",
                    ],
                    [
                        "label" => "Watching",
                        "short_label" => "W",
                        "type" => "coaching",
                        "subtype" => $index."_watching",
                    ],
                    [
                        "label" => "Launching",
                        "short_label" => "L",
                        "type" => "coaching",
                        "subtype" => $index."_launching",
                    ],
                ] : [],
            ];
        }

        return $list;
    }
}

if ( ! function_exists( 'zume_funnel_stages' ) ) {
    function zume_funnel_stages() : array {
        return [
            0 => [
                'label' => 'Anonymous',
                'short_label' => 'Anonymous',
                'description' => 'Anonymous visitors to the website.',
                'stage' => 0
            ],
            1 => [
                'label' => 'Registrant',
                'short_label' => 'Registered',
                'description' => 'Trainee who has registered for the training.',
                'stage' => 1
            ],
            2 => [
                'label' => 'Active Training Trainee',
                'short_label' => 'Active Training',
                'description' => 'Trainee who is in active training.',
                'stage' => 2
            ],
            3 => [
                'label' => 'Post-Training Trainee',
                'short_label' => 'Post-Training',
                'description' => 'Trainee who has completed training.',
                'stage' => 3
            ],
            4 => [
                'label' => '(S1) Partial Practitioner',
                'short_label' => 'Partial Practitioner',
                'description' => 'Practitioner still coaching through MAWL checklist.',
                'stage' => 4
            ],
            5 => [
                'label' => '(S2) Completed Practitioner',
                'short_label' => 'Practitioner',
                'description' => 'Practitioner who has completed the MAWL checklist but is not multiplying.',
                'stage' => 5
            ],
            6 => [
                'label' => '(S3) Multiplying Practitioner',
                'short_label' => 'Multiplying Practitioner',
                'description' => 'Practitioner who is seeing generational fruit.',
                'stage' => 6
            ],
        ];
    }
}
if ( ! function_exists( 'zume_get_stage' ) ) {
    function zume_get_stage( $user_id, $log = NULL, $number_only = false ) {

        if ( is_null( $log ) ) {
            $log = zume_user_log( $user_id );
        }

        $funnel = zume_funnel_stages();
        $stage = $funnel[0];

        if ( empty( $log ) ) {
            return $stage;
        }

        if ( count($log) > 0 ) {

            $funnel_steps = [
                1 => false,
                2 => false,
                3 => false,
                4 => false,
                5 => false,
                6 => false,
            ];

            foreach( $log as $index => $value ) {
                if ( 'registered' == $value['subtype'] ) {
                    $funnel_steps[1] = true;
                }
                if ( 'plan_created' == $value['subtype'] ) {
                    $funnel_steps[2] = true;
                }
                if ( 'training_completed' == $value['subtype'] ) {
                    $funnel_steps[3] = true;
                }
                if ( 'first_practitioner_report' == $value['subtype'] ) {
                    $funnel_steps[4] = true;
                }
                if ( 'mawl_completed' == $value['subtype'] ) {
                    $funnel_steps[5] = true;
                }
                if ( 'seeing_generational_fruit' == $value['subtype'] ) {
                    $funnel_steps[6] = true;
                }
            }

            if ( $funnel_steps[6] ) {
                $stage = $funnel[6];
            } else if ( $funnel_steps[5] ) {
                $stage = $funnel[5];
            } else if ( $funnel_steps[4] ) {
                $stage = $funnel[4];
            } else if ( $funnel_steps[3] ) {
                $stage = $funnel[3];
            } else if ( $funnel_steps[2] ) {
                $stage = $funnel[2];
            } else if ( $funnel_steps[1] ) {
                $stage = $funnel[1];
            } else {
                $stage = $funnel[0];
            }

        }

        if ( $number_only ) {
            return $stage['stage'];
        } else {
            return $stage;
        }
    }
}
if ( ! function_exists( 'zume_user_log' ) ) {
    function zume_user_log( $user_id ) {
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT CONCAT( r.type, '_', r.subtype ) as log_key, r.*
                FROM wp_dt_reports r
                WHERE r.user_id = %s
                AND r.post_type = 'zume'
                ", $user_id );
        return $wpdb->get_results( $sql, ARRAY_A );
    }
}
if( ! function_exists( 'zume_get_user_location' ) ) {
    function zume_get_user_location( $user_id, $ip_lookup = false  ) {
        global $wpdb;
        $location = $wpdb->get_row( $wpdb->prepare(
            "SELECT lng, lat, level, label, grid_id
                    FROM wp_postmeta pm
                    JOIN wp_dt_location_grid_meta lgm ON pm.post_id=lgm.post_id
                    WHERE pm.meta_key = 'corresponds_to_user' AND pm.meta_value = %d
                    ORDER BY grid_meta_id desc
                    LIMIT 1"
            , $user_id ), ARRAY_A );

        if ( empty( $location ) && $ip_lookup ) {
            $result = DT_Ipstack_API::get_location_grid_meta_from_current_visitor();
            if ( ! empty( $result ) ) {
                $location = [
                    'lng' => $result['lng'],
                    'lat' => $result['lat'],
                    'level' => $result['level'],
                    'label' => $result['label'],
                    'grid_id' => $result['grid_id'],
                ];
            }
        }

        if ( empty( $location ) ) {
            return false;
        }

        return [
            'lng' => $location['lng'],
            'lat' => $location['lat'],
            'level' => $location['level'],
            'label' => $location['label'],
            'grid_id' => $location['grid_id'],
        ];
    }
}
if( ! function_exists( 'zume_get_contact_id' ) ) {
    function zume_get_contact_id( $user_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM wp_postmeta WHERE meta_key = 'corresponds_to_user' AND meta_value = %s", $user_id ) );
    }
}

