<?php

// Sanitizer for checkboxes.
function fcsd_sanitize_bool( $val ) {
    return (bool) $val;
}

add_action(
    'customize_register',
    function ( $wp_customize ) {

        // ====== Header options ======
        $wp_customize->add_section(
            'fcsd_header',
            array(
                'title'    => __( 'Header Options', 'fcsd' ),
                'priority' => 30,
            )
        );

        // Generic logo (fallback when contrast is off or light/dark are not set).
        $wp_customize->add_setting(
            'fcsd_logo',
            array(
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Image_Control(
                $wp_customize,
                'fcsd_logo',
                array(
                    'label'    => __( 'Main logo (generic)', 'fcsd' ),
                    'section'  => 'fcsd_header',
                    'settings' => 'fcsd_logo',
                )
            )
        );

        // Logo light (normal mode, over dark background).
        $wp_customize->add_setting(
            'fcsd_logo_light',
            array(
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Image_Control(
                $wp_customize,
                'fcsd_logo_light',
                array(
                    'label'    => __( 'Light logo (normal mode)', 'fcsd' ),
                    'section'  => 'fcsd_header',
                    'settings' => 'fcsd_logo_light',
                )
            )
        );

        // Logo dark (high-contrast mode, over light background).
        $wp_customize->add_setting(
            'fcsd_logo_dark',
            array(
                'default'           => '',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            new WP_Customize_Image_Control(
                $wp_customize,
                'fcsd_logo_dark',
                array(
                    'label'    => __( 'Dark logo (contrast mode)', 'fcsd' ),
                    'section'  => 'fcsd_header',
                    'settings' => 'fcsd_logo_dark',
                )
            )
        );

        // Show / hide contrast button.
        $wp_customize->add_setting(
            'fcsd_enable_contrast',
            array(
                'default'           => true,
                'sanitize_callback' => 'fcsd_sanitize_bool',
            )
        );

        $wp_customize->add_control(
            'fcsd_enable_contrast',
            array(
                'label'   => __( 'Show Contrast Button', 'fcsd' ),
                'type'    => 'checkbox',
                'section' => 'fcsd_header',
            )
        );

        // Show / hide search field.
        $wp_customize->add_setting(
            'fcsd_enable_search',
            array(
                'default'           => true,
                'sanitize_callback' => 'fcsd_sanitize_bool',
            )
        );

        $wp_customize->add_control(
            'fcsd_enable_search',
            array(
                'label'   => __( 'Show Search Field', 'fcsd' ),
                'type'    => 'checkbox',
                'section' => 'fcsd_header',
            )
        );

        // User URL (kept for backwards compatibility, although header now uses pages).
        $wp_customize->add_setting(
            'fcsd_user_url',
            array(
                'default'           => '#',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            'fcsd_user_url',
            array(
                'label'   => __( 'User/Account URL', 'fcsd' ),
                'type'    => 'url',
                'section' => 'fcsd_header',
            )
        );

        // Cart URL (kept for backwards compatibility).
        $wp_customize->add_setting(
            'fcsd_cart_url',
            array(
                'default'           => '#',
                'sanitize_callback' => 'esc_url_raw',
            )
        );

        $wp_customize->add_control(
            'fcsd_cart_url',
            array(
                'label'   => __( 'Cart URL', 'fcsd' ),
                'type'    => 'url',
                'section' => 'fcsd_header',
            )
        );

        // ====== Donate button (per-language) ======
        $wp_customize->add_setting(
            'fcsd_enable_donate',
            array(
                'default'           => true,
                'sanitize_callback' => 'fcsd_sanitize_bool',
            )
        );

        $wp_customize->add_control(
            'fcsd_enable_donate',
            array(
                'label'       => __( 'Show Donate Button', 'fcsd' ),
                'description' => __( 'Shows a green “Donate” button on the right side of the header (per language label + URL).', 'fcsd' ),
                'type'        => 'checkbox',
                'section'     => 'fcsd_header',
            )
        );

        $donate_label_defaults = array(
            'ca' => 'Fes un donatiu',
            'es' => 'Haz un donativo',
            'en' => 'Donate',
        );

        foreach ( array( 'ca', 'es', 'en' ) as $lang ) {
            $wp_customize->add_setting(
                'donate_label_' . $lang,
                array(
                    'default'           => $donate_label_defaults[ $lang ] ?? '',
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );
            $wp_customize->add_control(
                'donate_label_' . $lang,
                array(
                    'label'       => sprintf( __( 'Donate button label (%s)', 'fcsd' ), strtoupper( $lang ) ),
                    'type'        => 'text',
                    'section'     => 'fcsd_header',
                )
            );

            $wp_customize->add_setting(
                'donate_url_' . $lang,
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );
            $wp_customize->add_control(
                'donate_url_' . $lang,
                array(
                    'label'       => sprintf( __( 'Donate button URL (%s)', 'fcsd' ), strtoupper( $lang ) ),
                    'description' => __( 'If empty, the donate button will be hidden for that language.', 'fcsd' ),
                    'type'        => 'url',
                    'section'     => 'fcsd_header',
                )
            );
        }

        // ====== Social Links (includes TikTok) ======
        $wp_customize->add_section(
            'fcsd_social',
            array(
                'title'    => __( 'Social Links (Top Right)', 'fcsd' ),
                'priority' => 31,
            )
        );

        $socials = array( 'twitter', 'facebook', 'instagram', 'linkedin', 'youtube', 'tiktok' );

        foreach ( $socials as $s ) {
            $wp_customize->add_setting(
                "fcsd_social_{$s}",
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );

            $wp_customize->add_control(
                "fcsd_social_{$s}",
                array(
                    'label'   => sprintf( __( '%s URL', 'fcsd' ), ucfirst( $s ) ),
                    'type'    => 'url',
                    'section' => 'fcsd_social',
                )
            );
        }

        // ====== Static homepage (dummy content) ======
        $wp_customize->add_section(
            'fcsd_home',
            array(
                'title'    => __( 'Homepage', 'fcsd' ),
                'priority' => 32,
            )
        );

        // ====== Home intro (título del hero) – por idioma ======
        // Se guarda como home_intro_{lang} y se lee con fcsd_get_option('home_intro').
        $home_intro_defaults = array(
            'ca' => 'Acompanyem a persones amb SD a construir una vida més autònoma, plena i connectada.',
            'es' => 'Acompañamos a personas con SD a construir una vida más autónoma, plena y conectada.',
            'en' => 'We support people with Down syndrome to build a more independent, fulfilling and connected life.',
        );

        foreach ( array( 'ca', 'es', 'en' ) as $lang ) {
            $wp_customize->add_setting(
                'home_intro_' . $lang,
                array(
                    'default'           => $home_intro_defaults[ $lang ] ?? '',
                    'sanitize_callback' => 'wp_kses_post',
                )
            );

            $wp_customize->add_control(
                'home_intro_' . $lang,
                array(
                    'label'       => sprintf( __( 'Hero title (%s)', 'fcsd' ), strtoupper( $lang ) ),
                    'description' => __( 'Text principal del bàner de la home. Es mostra segons l’idioma seleccionat al frontend.', 'fcsd' ),
                    'type'        => 'textarea',
                    'section'     => 'fcsd_home',
                )
            );
        }

        // ====== Home CTA (botó del hero) – etiqueta + URL por idioma ======
        // Se guarda como home_cta_label_{lang} y home_cta_url_{lang}.
        $home_cta_label_defaults = array(
            'ca' => 'Qui som',
            'es' => 'Quiénes somos',
            'en' => 'About us',
        );

        foreach ( array( 'ca', 'es', 'en' ) as $lang ) {
            $wp_customize->add_setting(
                'home_cta_label_' . $lang,
                array(
                    'default'           => $home_cta_label_defaults[ $lang ] ?? '',
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );

            $wp_customize->add_control(
                'home_cta_label_' . $lang,
                array(
                    'label'       => sprintf( __( 'Hero button label (%s)', 'fcsd' ), strtoupper( $lang ) ),
                    'description' => __( 'Text del botó principal del bàner de la home (per idioma).', 'fcsd' ),
                    'type'        => 'text',
                    'section'     => 'fcsd_home',
                )
            );

            $wp_customize->add_setting(
                'home_cta_url_' . $lang,
                array(
                    'default'           => '',
                    'sanitize_callback' => 'esc_url_raw',
                )
            );

            $wp_customize->add_control(
                'home_cta_url_' . $lang,
                array(
                    'label'       => sprintf( __( 'Hero button URL (%s)', 'fcsd' ), strtoupper( $lang ) ),
                    'description' => __( 'URL del botó principal del bàner de la home (per idioma). Si es deixa buit, apunta a la pàgina “Qui som / About”.', 'fcsd' ),
                    'type'        => 'url',
                    'section'     => 'fcsd_home',
                )
            );
        }

        // ====== Home news strip (continuous marquee) ======
        $wp_customize->add_setting(
            'home_news_marquee_enable',
            array(
                'default'           => true,
                'sanitize_callback' => 'fcsd_sanitize_bool',
            )
        );

        $wp_customize->add_control(
            'home_news_marquee_enable',
            array(
                'label'       => __( 'Home: continuous news carousel', 'fcsd' ),
                'description' => __( 'If enabled, the news strip on the front page auto-scrolls in a continuous loop. If disabled, it becomes a manual horizontal scroll.', 'fcsd' ),
                'type'        => 'checkbox',
                'section'     => 'fcsd_home',
            )
        );

        $wp_customize->add_setting(
            'home_news_marquee_speed',
            array(
                'default'           => 28,
                'sanitize_callback' => static function ( $val ) {
                    $v = (int) $val;
                    if ( $v < 10 ) { $v = 10; }
                    if ( $v > 120 ) { $v = 120; }
                    return $v;
                },
            )
        );

        $wp_customize->add_control(
            'home_news_marquee_speed',
            array(
                'label'       => __( 'Home: news carousel speed (seconds)', 'fcsd' ),
                'description' => __( 'Lower = faster. Recommended: 20–40.', 'fcsd' ),
                'type'        => 'number',
                'section'     => 'fcsd_home',
                'input_attrs' => array(
                    'min'  => 10,
                    'max'  => 120,
                    'step' => 1,
                ),
            )
        );

        $wp_customize->add_setting(
            'home_news_marquee_pause',
            array(
                'default'           => true,
                'sanitize_callback' => 'fcsd_sanitize_bool',
            )
        );

        $wp_customize->add_control(
            'home_news_marquee_pause',
            array(
                'label'       => __( 'Home: pause on hover/focus', 'fcsd' ),
                'type'        => 'checkbox',
                'section'     => 'fcsd_home',
            )
        );

        $wp_customize->add_setting(
            'fcsd_home_dummy',
            array(
                'default'           => 'Benvinguda a FCSD. Contingut de prova.',
                'sanitize_callback' => 'wp_kses_post',
            )
        );

        $wp_customize->add_control(
            'fcsd_home_dummy',
            array(
                'label'   => __( 'Dummy content', 'fcsd' ),
                'type'    => 'textarea',
                'section' => 'fcsd_home',
            )
        );

        // ====== Footer Options ======
        $wp_customize->add_section(
            'fcsd_footer',
            array(
                'title'    => __( 'Footer Options', 'fcsd' ),
                'priority' => 35,
            )
        );

        // Tagline / mensaje del footer
        $wp_customize->add_setting(
            'fcsd_footer_tagline',
            array(
                'default'           => __( 'Treballem per la plena inclusió i igualtat de drets.', 'fcsd' ),
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_tagline',
            array(
                'label'   => __( 'Footer message', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        // Dirección postal
        $wp_customize->add_setting(
            'fcsd_footer_address',
            array(
                'default'           => "Fundació Catalana Síndrome de Down\nComte Borrell, 201–203, entresòl\n08029 Barcelona\nEspanya",
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_address',
            array(
                'label'   => __( 'Postal address', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        // Teléfono
        $wp_customize->add_setting(
            'fcsd_footer_phone',
            array(
                'default'           => '+34 93 215 74 23',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_phone',
            array(
                'label'   => __( 'Phone', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        // Email
        $wp_customize->add_setting(
            'fcsd_footer_email',
            array(
                'default'           => 'general@fcsd.org',
                'sanitize_callback' => 'sanitize_email',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_email',
            array(
                'label'   => __( 'Email', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'email',
            )
        );

        // Email del formulari de contacte (per defecte, el del footer)
        $wp_customize->add_setting(
            'fcsd_contact_email',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_email',
            )
        );

        $wp_customize->add_control(
            'fcsd_contact_email',
            array(
                'label'       => __( 'Email formulari de contacte', 'fcsd' ),
                'description' => __( 'Si el deixes en blanc, s\'utilitzarà el correu del footer.', 'fcsd' ),
                'section'     => 'fcsd_footer',
                'type'        => 'email',
            )
        );


        // Botón donación: URL
        $wp_customize->add_setting(
            'fcsd_footer_donate_url',
            array(
                'default'           => 'https://fcsd.org/es/donativo-particular/',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_donate_url',
            array(
                'label'   => __( 'Donation URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        // Botón donación: texto
        $wp_customize->add_setting(
            'fcsd_footer_donate_label',
            array(
                'default'           => __( 'Donar', 'fcsd' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_donate_label',
            array(
                'label'   => __( 'Donation button label', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        // Redes en el footer (opcionales, aunque ahora reutilizamos las del header)
        $wp_customize->add_setting(
            'fcsd_footer_web_url',
            array(
                'default'           => 'https://fcsd.org/',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_web_url',
            array(
                'label'   => __( 'Main website URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        $wp_customize->add_setting(
            'fcsd_footer_facebook_url',
            array(
                'default'           => 'https://www.facebook.com/fundaciocatalanasindromededown/',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_facebook_url',
            array(
                'label'   => __( 'Facebook URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        $wp_customize->add_setting(
            'fcsd_footer_instagram_url',
            array(
                'default'           => 'https://www.instagram.com/fcsdown/',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_instagram_url',
            array(
                'label'   => __( 'Instagram URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        $wp_customize->add_setting(
            'fcsd_footer_x_url',
            array(
                'default'           => 'https://x.com/fcsdown',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_x_url',
            array(
                'label'   => __( 'X (Twitter) URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        $wp_customize->add_setting(
            'fcsd_footer_youtube_url',
            array(
                'default'           => 'https://www.youtube.com/@FCSDown',
                'sanitize_callback' => 'esc_url_raw',
            )
        );
        $wp_customize->add_control(
            'fcsd_footer_youtube_url',
            array(
                'label'   => __( 'YouTube URL', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'url',
            )
        );

        /* ====== Legal modals content ====== */

        // Política de privacitat (legacy / compatibilidad)
        $wp_customize->add_setting(
            'fcsd_legal_privacy_title',
            array(
                'default'           => __( 'Política de privacitat', 'fcsd' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_privacy_title',
            array(
                'label'   => __( 'Privacitat – Títol', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'fcsd_legal_privacy_content',
            array(
                'default'           => '<p>Aquesta Política de privacitat descriu com es recullen, s\'usen i es protegeixen les dades personals quan visites aquest lloc web.</p><p><strong>Responsable</strong>: l\'entitat titular del lloc.</p><p><strong>Finalitat</strong>: gestionar les consultes, la prestació de serveis i la relació amb les persones usuàries.</p><p><strong>Base jurídica</strong>: el teu consentiment i/o l\'execució d\'un contracte, segons correspongui.</p><p><strong>Conservació</strong>: durant el temps necessari per complir la finalitat i les obligacions legals.</p><p><strong>Drets</strong>: pots exercir els drets d\'accés, rectificació, supressió, oposició, limitació i portabilitat, així com retirar el consentiment.</p><p>Per a més informació o per exercir els teus drets, contacta amb nosaltres mitjançant els canals indicats al lloc web.</p>',
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_privacy_content',
            array(
                'label'   => __( 'Privacitat – Contingut (HTML permès)', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        // Cookies (legacy / compatibilidad)
        $wp_customize->add_setting(
            'fcsd_legal_cookies_title',
            array(
                'default'           => __( 'Política de cookies', 'fcsd' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_cookies_title',
            array(
                'label'   => __( 'Cookies – Títol', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'fcsd_legal_cookies_content',
            array(
                'default'           => '<p>Aquest lloc web utilitza cookies pròpies i de tercers per garantir el seu funcionament, analitzar la navegació i, si escau, personalitzar el contingut.</p><p>Pots configurar, acceptar o rebutjar les cookies des del teu navegador o des del panell de configuració corresponent (si està disponible).</p><p>Algunes cookies són tècniques i necessàries; d\'altres són analítiques o de personalització. La desactivació de certes cookies pot afectar l\'experiència de navegació.</p>',
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_cookies_content',
            array(
                'label'   => __( 'Cookies – Contingut (HTML permès)', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        // Avís legal (legacy / compatibilidad)
        $wp_customize->add_setting(
            'fcsd_legal_notice_title',
            array(
                'default'           => __( 'Avís legal', 'fcsd' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_notice_title',
            array(
                'label'   => __( 'Avís legal – Títol', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'fcsd_legal_notice_content',
            array(
                'default'           => '<p>Aquest lloc web és titularitat de l\'entitat responsable. L\'accés i ús del lloc implica l\'acceptació d\'aquestes condicions.</p><p><strong>Propietat intel·lectual</strong>: els continguts, dissenys i elements del lloc estan protegits i no es poden reproduir sense autorització.</p><p><strong>Responsabilitat</strong>: l\'entitat no es fa responsable de l\'ús indegut del lloc ni de possibles danys derivats d\'errors o interrupcions del servei.</p><p><strong>Enllaços</strong>: els enllaços a tercers es faciliten a títol informatiu i poden canviar sense previ avís.</p>',
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_notice_content',
            array(
                'label'   => __( 'Avís legal – Contingut (HTML permès)', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        // Copyright (legacy / compatibilidad)
        $wp_customize->add_setting(
            'fcsd_legal_copyright_title',
            array(
                'default'           => __( 'Copyright', 'fcsd' ),
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_copyright_title',
            array(
                'label'   => __( 'Copyright – Títol', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'text',
            )
        );

        $wp_customize->add_setting(
            'fcsd_legal_copyright_content',
            array(
                'default'           => '<p>© ' . date( 'Y' ) . ' Fundació Catalana Síndrome de Down. Tots els drets reservats.</p><p>No es permet la reproducció total o parcial dels continguts d\'aquest lloc web sense autorització expressa.</p>',
                'sanitize_callback' => 'wp_kses_post',
            )
        );
        $wp_customize->add_control(
            'fcsd_legal_copyright_content',
            array(
                'label'   => __( 'Copyright – Contingut (HTML permès)', 'fcsd' ),
                'section' => 'fcsd_footer',
                'type'    => 'textarea',
            )
        );

        /* ====== Legal modals content (multidioma) ======
         * Se guardan como theme_mods con sufijo: _ca, _es, _en.
         * El frontend selecciona automáticamente el idioma actual.
         */

        $fcsd_legal_langs = [
            'ca' => __( 'Català', 'fcsd' ),
            'es' => __( 'Español', 'fcsd' ),
            'en' => __( 'English', 'fcsd' ),
        ];

        // Defaults (catalán) para que el sitio tenga contenido de base.
        $fcsd_legal_defaults_ca = [
            'privacy_title'   => 'Política de privacitat',
            'privacy_content' => get_theme_mod( 'fcsd_legal_privacy_content', '' ),
            'cookies_title'   => 'Política de cookies',
            'cookies_content' => get_theme_mod( 'fcsd_legal_cookies_content', '' ),
            'notice_title'    => 'Avís legal',
            'notice_content'  => get_theme_mod( 'fcsd_legal_notice_content', '' ),
            'copyright_title' => 'Copyright',
            'copyright_content' => get_theme_mod( 'fcsd_legal_copyright_content', '' ),
        ];

        foreach ( $fcsd_legal_langs as $lang_code => $lang_label ) {
            $is_ca = ( 'ca' === $lang_code );

            // Privacidad
            $wp_customize->add_setting(
                'fcsd_legal_privacy_title_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['privacy_title'] : ( 'es' === $lang_code ? 'Política de privacidad' : 'Privacy policy' ),
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_privacy_title_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Privacitat – Títol (%s)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'text',
                )
            );

            $wp_customize->add_setting(
                'fcsd_legal_privacy_content_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['privacy_content'] : '',
                    'sanitize_callback' => 'wp_kses_post',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_privacy_content_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Privacitat – Contingut (%s) (HTML permès)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'textarea',
                )
            );

            // Cookies
            $wp_customize->add_setting(
                'fcsd_legal_cookies_title_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['cookies_title'] : ( 'es' === $lang_code ? 'Política de cookies' : 'Cookies policy' ),
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_cookies_title_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Cookies – Títol (%s)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'text',
                )
            );

            $wp_customize->add_setting(
                'fcsd_legal_cookies_content_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['cookies_content'] : '',
                    'sanitize_callback' => 'wp_kses_post',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_cookies_content_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Cookies – Contingut (%s) (HTML permès)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'textarea',
                )
            );

            // Aviso / Legal notice
            $wp_customize->add_setting(
                'fcsd_legal_notice_title_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['notice_title'] : ( 'es' === $lang_code ? 'Aviso legal' : 'Legal notice' ),
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_notice_title_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Avís legal – Títol (%s)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'text',
                )
            );

            $wp_customize->add_setting(
                'fcsd_legal_notice_content_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['notice_content'] : '',
                    'sanitize_callback' => 'wp_kses_post',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_notice_content_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Avís legal – Contingut (%s) (HTML permès)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'textarea',
                )
            );

            // Copyright
            $wp_customize->add_setting(
                'fcsd_legal_copyright_title_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['copyright_title'] : 'Copyright',
                    'sanitize_callback' => 'sanitize_text_field',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_copyright_title_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Copyright – Títol (%s)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'text',
                )
            );

            $wp_customize->add_setting(
                'fcsd_legal_copyright_content_' . $lang_code,
                array(
                    'default'           => $is_ca ? $fcsd_legal_defaults_ca['copyright_content'] : '',
                    'sanitize_callback' => 'wp_kses_post',
                )
            );
            $wp_customize->add_control(
                'fcsd_legal_copyright_content_' . $lang_code,
                array(
                    'label'   => sprintf( __( 'Copyright – Contingut (%s) (HTML permès)', 'fcsd' ), $lang_label ),
                    'section' => 'fcsd_footer',
                    'type'    => 'textarea',
                )
            );
        }

        // ====== SMTP (Email) ======
        $wp_customize->add_section(
            'fcsd_smtp',
            array(
                'title'    => __( 'SMTP (Email)', 'fcsd' ),
                'priority' => 40,
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_host',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_host',
            array(
                'label'   => __( 'SMTP host', 'fcsd' ),
                'type'    => 'text',
                'section' => 'fcsd_smtp',
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_port',
            array(
                'default'           => 587,
                'sanitize_callback' => 'absint',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_port',
            array(
                'label'   => __( 'SMTP port', 'fcsd' ),
                'type'    => 'number',
                'section' => 'fcsd_smtp',
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_user',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_user',
            array(
                'label'   => __( 'SMTP username', 'fcsd' ),
                'type'    => 'text',
                'section' => 'fcsd_smtp',
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_pass',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_pass',
            array(
                'label'   => __( 'SMTP password', 'fcsd' ),
                'type'    => 'password',
                'section' => 'fcsd_smtp',
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_secure',
            array(
                'default'           => 'tls',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_secure',
            array(
                'label'   => __( 'Security', 'fcsd' ),
                'type'    => 'select',
                'section' => 'fcsd_smtp',
                'choices' => array(
                    ''    => __( 'None', 'fcsd' ),
                    'tls' => 'TLS',
                    'ssl' => 'SSL',
                ),
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_from_email',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_email',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_from_email',
            array(
                'label'   => __( 'From email', 'fcsd' ),
                'type'    => 'email',
                'section' => 'fcsd_smtp',
            )
        );

        $wp_customize->add_setting(
            'fcsd_smtp_from_name',
            array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        $wp_customize->add_control(
            'fcsd_smtp_from_name',
            array(
                'label'   => __( 'From name', 'fcsd' ),
                'type'    => 'text',
                'section' => 'fcsd_smtp',
            )
        );
    }
);
