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

        // Política de privacitat
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
                'default'           => '',
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

        // Cookies
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
                'default'           => '',
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

        // Avís legal
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
                'default'           => '',
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

        // Copyright
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
                'default'           => '',
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
