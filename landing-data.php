<?php

$landingarray = [
    'heading' => [
        'background_image' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1267&q=80',
        'header_text' => 'Build your trading skills with us',
        'header_description' => 'Horne your technical analysis skill while building confidence in your strategy using our state of the arts backtesting solution',
        'keynotes' => [
            [
                'header' => 'Create Strategies',
                'icon' => 'fas fa-lightbulb-o',
                'bodies' => ['We provide strategy management tool for you to create and manage you awesome strategy while monitoring its WinRate']
            ],
            [
                'header' => 'Testing Sessions',
                'icon' => 'fas fa-retweet',
                'bodies' => [
                    'You can create backtesting sessions to trade with',
                    'Backtesting Session can be linked to a strategy or standalone',
                    'Tied to a single pair, you are able to monitor your efficiency on an asset to asset bases'
                ]
            ],
            [
                'header' => 'Analytics',
                'icon' => 'fas fa-line-chart',
                'bodies' => [
                    'Access indepth analytics about how your strategies are performing on different backtesting sessions',
                    'Helping you make the right decision about a strategy'
                ]
            ]
        ]
    ],
    'features' => [
        [
            'header' => 'Play and Pause',
            'bodies' => [
                "Play foward a backtesting session at the click of a button",
                "Pause a backtesting session at the click of a button"
            ],
            'key_list' => [],
            'link' => 'Signup Now!',
            'side_card' => [
                'background_image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1051&q=80',
                'header' => 'Replay Speed',
                'bodies' => ['Control the speed at which a testing session is replayed to get you most desired result']
            ]
        ],
        [
            'header' => 'Manage Orders',
            'bodies' => [
                "Place orders at the click of button",
                "Manage SL and TP on the fly",
                "Take partials"
            ],
            'key_list' => [
                'Order management dashboard',
                'Advanced drawing tools',
                'Change timeframes at will'
            ],
            'link' => '',
            'side_card' => [
                'background_image' => 'https://images.unsplash.com/photo-1555212697-194d092e3b8f?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=634&q=80',
                'header' => '',
                'bodies' => []
            ]
        ],
    ],
    'team' => [
        'header' => 'Here are our heroes',
        'description' => 'Meet the brilliant team that are working effortlessly to put together this masterpiece',
        'members' => [
            [
                'img' => '/assets/basttyy.jpg',
                'name' => 'Abdulbasit Mamman',
                'role' => 'Founder',
                'socials' => [
                    [
                        'icon' => 'fab fa-twitter',
                        'style' => 'bg-sky-400',
                        'link' => 'twitter.com'
                    ],
                    [
                        'icon' => 'fab fa-facebook-f',
                        'style' => 'bg-sky-500',
                        'link' => 'https://facebook.com/basttyysignal'
                    ],
                    [
                        'icon' => 'fab fa-instagram',
                        'style' => 'bg-slate-700',
                        'link' => 'https://facebook.com/basttyysignal'
                    ]
                ]
            ]
        ]
    ],
    'pricing' => [
        'header' => 'Our Pricing',
        'description' => 'We know how hard it can be for struggling traders, our prices are carefully tailored for both newbie and professional traders!',
    ],
    'contact' => [
        'header' => 'Have some questions for us?',
        'description' => 'Complete this form and we will get back to you in 24 hours'
    ],
    'footer' => [
        'header' => "Let's keep in touch!",
        'description' => "Find us on any of these platforms, we respond 1-2 business days",
        'socials' => [
            [
                'icon' => 'fab fa-twitter',
                'style' => 'bg-white bg-sky-400'
            ],
            [
                'icon' => 'fab fa-facebook-square',
                'style' => 'bg-white text-sky-600'
            ],
            [
                'icon' => 'fab fa-tiktok',
                'style' => 'bg-white text-gray-400'
            ],
            [
                'icon' => 'fab fa-instagram',
                'style' => 'bg-white bg-slate-700'
            ]
        ],
        'links' => [
            'header' => 'Useful Links',
            'links' => [
                // [
                //     'title' => 'About Us',
                //     'url' => 'https://www.creative-tim.com/presentation?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Blog',
                //     'url' => 'https://blog.creative-tim.com?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Github',
                //     'url' => 'https://www.github.com/creativetimofficial?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Free Products',
                //     'url' => 'https://www.creative-tim.com/bootstrap-themes/free?ref=vn-footer'
                // ]
            ]
        ],
        'other_links' => [
            'header' => 'Other Resources',
            'links' => [
                // [
                //     'title' => 'MIT License',
                //     'url' => 'https://github.com/creativetimofficial/vue-notus/blob/main/LICENSE.md?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Terms & Conditions',
                //     'url' => 'https://creative-tim.com/terms?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Privacy Policy',
                //     'url' => 'https://creative-tim.com/privacy?ref=vn-footer'
                // ],
                // [
                //     'title' => 'Contact Us',
                //     'url' => 'https://creative-tim.com/contact-us?ref=vn-footer'
                // ]
            ]
        ],
        'footer' => ''
    ]
];

$json_data = json_encode($landingarray);

$file = fopen("public/landing.json", "w") or die("Unable to open file!");
fwrite($file, $json_data);

fclose($file);
