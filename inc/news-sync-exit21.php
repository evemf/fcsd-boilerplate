<?php 
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ============================================================================
 * IMPORTADOR ROBUSTO DE RSS CON WEB SCRAPING DE CONTENIDO COMPLETO
 * ============================================================================
 */

// Category en CPT 'news' + soporte de thumbnail
add_action('init', function () {
    if ( taxonomy_exists('category') ) {
        register_taxonomy_for_object_type('category', 'news');
    }
    add_post_type_support('news', 'thumbnail');
}, 1);

/**
 * ============================================================================
 * SISTEMA DE LOGS
 * ============================================================================
 */
function fcsd_log( $message, $type = 'info' ) {
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        $prefix = strtoupper($type);
        error_log( "[FCSD {$prefix}] " . $message );
    }
}

/**
 * Asegura que la noticia tenga al menos el ámbito "Institucional".
 * Se ejecuta durante la importación (background) para no depender de permisos
 * del usuario actual.
 */
function fcsd_news_ensure_default_area( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) return;

    // Solo para noticias provenientes de EXIT21.
    $src = get_post_meta( $post_id, 'news_source', true );
    if ( $src !== 'exit21' ) return;

    $terms = get_the_terms( $post_id, 'service_area' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        return; // Ya tiene ámbitos
    }

    $default_name = __( 'Institucional', 'fcsd' );
    $existing = term_exists( $default_name, 'service_area' );
    if ( ! $existing ) {
        $created = wp_insert_term( $default_name, 'service_area' );
        if ( is_wp_error( $created ) ) return;
        $term_id = (int) $created['term_id'];
    } else {
        $term_id = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
    }

    wp_set_object_terms( $post_id, [ $term_id ], 'service_area', false );
}

/**
 * ============================================================================
 * EXTRACCIÓN COMPLETA DE CONTENIDO VIA WEB SCRAPING
 * ============================================================================
 */
function fcsd_fetch_full_content( $url ) {
    fcsd_log( "Intentando obtener contenido completo de: {$url}", 'debug' );
    
    $args = [
        // Importante: timeouts cortos para no bloquear admin/front.
        'timeout'     => 5,
        'redirection' => 5,
        'httpversion' => '1.1',
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'headers'     => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
        ],
    ];
    
    $response = wp_remote_get( $url, $args );
    
    if ( is_wp_error( $response ) ) {
        fcsd_log( "Error al obtener URL: " . $response->get_error_message(), 'error' );
        return false;
    }
    
    $html = wp_remote_retrieve_body( $response );
    if ( empty( $html ) ) {
        fcsd_log( "HTML vacío obtenido de: {$url}", 'error' );
        return false;
    }
    
    // Extraer el contenido principal del artículo
    $content = fcsd_extract_article_content( $html, $url );
    
    if ( $content ) {
        fcsd_log( "Contenido completo extraído exitosamente (" . strlen($content) . " caracteres)", 'success' );
        return $content;
    }
    
    fcsd_log( "No se pudo extraer contenido del HTML", 'warning' );
    return false;
}

/**
 * Extrae el contenido principal del artículo desde el HTML completo
 * Usa múltiples estrategias para localizar el contenido
 */
function fcsd_extract_article_content( $html, $source_url = '' ) {
    if ( empty( $html ) ) return false;
    
    // Cargar HTML con DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    
    // Cargar forzando UTF-8 sin usar HTML-ENTITIES (deprecated en PHP 8.2+)
    // Prefijo XML encoding ayuda a DOMDocument a interpretar correctamente.
    @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();
    
    $xpath = new DOMXPath( $dom );
    $content_html = '';
    
    // ========================================================================
    // ESTRATEGIA 1: Selectores específicos de WordPress y page builders
    // ========================================================================
    $selectors = [
        // WordPress estándar
        "//article[contains(@class, 'post')]//div[contains(@class, 'entry-content')]",
        "//div[contains(@class, 'post-content')]",
        "//div[contains(@class, 'article-content')]",
        "//div[@class='content']",
        
        // Elementor
        "//div[contains(@class, 'elementor-widget-theme-post-content')]",
        "//div[@data-elementor-type='wp-post']",
        "//section[contains(@class, 'elementor-section')]",
        
        // WPBakery (Visual Composer)
        "//div[contains(@class, 'vc_row')]",
        "//div[contains(@class, 'wpb_wrapper')]",
        "//div[@class='vc_column-inner']",
        
        // Gutenberg
        "//div[contains(@class, 'wp-block-post-content')]",
        "//div[contains(@class, 'entry-content')]",
        
        // Genéricos semánticos
        "//article//div[@itemprop='articleBody']",
        "//div[@itemprop='articleBody']",
        "//main//article",
        "//article",
        "//main",
    ];
    
    foreach ( $selectors as $selector ) {
        $nodes = $xpath->query( $selector );
        if ( $nodes && $nodes->length > 0 ) {
            $node = $nodes->item(0);
            $content_html = $dom->saveHTML( $node );
            if ( strlen( strip_tags( $content_html ) ) > 300 ) {
                fcsd_log( "Contenido extraído con selector: {$selector}", 'debug' );
                break;
            }
        }
    }
    
    // ========================================================================
    // ESTRATEGIA 2: Buscar el div más grande con contenido
    // ========================================================================
    if ( strlen( strip_tags( $content_html ) ) < 300 ) {
        fcsd_log( "Aplicando estrategia de búsqueda por tamaño de contenido", 'debug' );
        
        $all_divs = $xpath->query( "//div" );
        $max_length = 0;
        $best_div = null;
        
        foreach ( $all_divs as $div ) {
            $text_content = strip_tags( $dom->saveHTML( $div ) );
            $length = strlen( $text_content );
            
            // Debe tener contenido sustancial
            if ( $length > $max_length && $length > 300 ) {
                // Evitar divs de navegación, sidebar, footer
                $class = $div->getAttribute('class');
                $id = $div->getAttribute('id');
                
                if ( preg_match('/nav|menu|sidebar|widget|footer|header|comment/i', $class . $id) ) {
                    continue;
                }
                
                $max_length = $length;
                $best_div = $div;
            }
        }
        
        if ( $best_div ) {
            $content_html = $dom->saveHTML( $best_div );
            fcsd_log( "Contenido extraído por análisis de tamaño ({$max_length} chars)", 'debug' );
        }
    }
    
    // ========================================================================
    // ESTRATEGIA 3: Limpieza y normalización del HTML
    // ========================================================================
    if ( ! empty( $content_html ) ) {
        $content_html = fcsd_clean_scraped_html( $content_html, $source_url );
        return $content_html;
    }
    
    return false;
}

/**
 * Limpia y normaliza el HTML extraído
 */
function fcsd_clean_scraped_html( $html, $source_url = '' ) {
    if ( empty( $html ) ) return '';
    
    // Eliminar scripts, styles y elementos no deseados
    $html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
    $html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );
    $html = preg_replace( '/<noscript\b[^>]*>(.*?)<\/noscript>/is', '', $html );
    
    // Eliminar comentarios HTML
    $html = preg_replace( '/<!--(.|\s)*?-->/', '', $html );
    
    // Eliminar elementos de navegación, sidebar, widgets
    $html = preg_replace( '/<(nav|aside|header|footer)[^>]*>.*?<\/\1>/is', '', $html );
    $html = preg_replace( '/<div[^>]*class="[^"]*(?:nav|menu|sidebar|widget|footer|header|comment|related|share|social)[^"]*"[^>]*>.*?<\/div>/is', '', $html );
    
    // Limpiar clases y atributos innecesarios pero mantener estructura
    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    @$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();
    
    $xpath = new DOMXPath( $dom );
    
    // Limpiar atributos innecesarios excepto src, href, alt, title
    $all_elements = $xpath->query( '//*' );
    foreach ( $all_elements as $element ) {
        $attributes_to_keep = ['src', 'href', 'alt', 'title'];
        $attributes = [];
        
        if ( $element->hasAttributes() ) {
            foreach ( $element->attributes as $attr ) {
                $attributes[] = $attr->nodeName;
            }
            
            foreach ( $attributes as $attr_name ) {
                if ( ! in_array( $attr_name, $attributes_to_keep ) ) {
                    $element->removeAttribute( $attr_name );
                }
            }
        }
    }
    
    // Convertir URLs relativas a absolutas
    if ( $source_url ) {
        $parsed_url = parse_url( $source_url );
        if ( ! empty( $parsed_url['scheme'] ) && ! empty( $parsed_url['host'] ) ) {
            $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            
            // Imágenes
            $images = $xpath->query( '//img[@src]' );
            foreach ( $images as $img ) {
                $src = $img->getAttribute('src');
                if ( strpos( $src, 'http' ) !== 0 ) {
                    if ( strpos( $src, '//' ) === 0 ) {
                        $img->setAttribute( 'src', $parsed_url['scheme'] . ':' . $src );
                    } elseif ( strpos( $src, '/' ) === 0 ) {
                        $img->setAttribute( 'src', $base_url . $src );
                    } else {
                        $img->setAttribute( 'src', $base_url . '/' . $src );
                    }
                }
            }
            
            // Enlaces
            $links = $xpath->query( '//a[@href]' );
            foreach ( $links as $link ) {
                $href = $link->getAttribute('href');
                if ( strpos( $href, 'http' ) !== 0 && strpos( $href, '#' ) !== 0 ) {
                    if ( strpos( $href, '/' ) === 0 ) {
                        $link->setAttribute( 'href', $base_url . $href );
                    } else {
                        $link->setAttribute( 'href', $base_url . '/' . $href );
                    }
                }
            }
        }
    }
    
    $html = $dom->saveHTML();
    
    // Normalizar espacios en blanco
    $html = preg_replace( '/\s+/', ' ', $html );
    $html = preg_replace( '/>\s+</', '><', $html );
    
    return trim( $html );
}

/**
 * ============================================================================
 * EXTRACCIÓN MEJORADA DE IMÁGENES
 * ============================================================================
 */
function fcsd_extract_all_images_from_item( $item ) {
    $images = [];
    
    // 1) media:thumbnail
    if ( defined('SIMPLEPIE_NAMESPACE_MEDIARSS') ) {
        $thumbs = (array) $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'thumbnail' );
        foreach ( $thumbs as $t ) {
            $u = $t['attribs']['']['url'] ?? '';
            if ( $u ) $images[] = $u;
        }
        
        // 2) media:content image/*
        $medias = (array) $item->get_item_tags( SIMPLEPIE_NAMESPACE_MEDIARSS, 'content' );
        foreach ( $medias as $m ) {
            $u = $m['attribs']['']['url']  ?? '';
            $ty= $m['attribs']['']['type'] ?? '';
            if ( $u && ( ! $ty || stripos($ty,'image/')===0 ) ) $images[] = $u;
        }
    }

    // 3) enclosure
    $enc = $item->get_enclosure();
    if ( $enc && $enc->get_link() ) $images[] = $enc->get_link();

    // Helper para localizar todas las <img ...>
    $find_all_imgs = function( $html ) {
        if ( ! $html ) return [];
        $found = [];
        
        // src estándar
        if ( preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches) ) {
            foreach ( $matches[1] as $url ) {
                if ( preg_match('~^https?://~i', $url) ) $found[] = $url;
            }
        }
        
        // srcset
        if ( preg_match_all('/<img[^>]+srcset=["\']([^"\']+)["\']/i', $html, $matches) ) {
            foreach ( $matches[1] as $srcset ) {
                $parts = array_map('trim', explode(',', $srcset));
                foreach ( $parts as $p ) {
                    if ( preg_match('~(https?://\S+\.(?:jpe?g|png|gif|webp|svg))~i', $p, $mm) ) {
                        $found[] = $mm[1];
                    }
                }
            }
        }
        
        // data-src, data-large_image, data-orig-file, data-lazy-src
        if ( preg_match_all('/<img[^>]+data-(?:src|large_image|orig-file|lazy-src)=["\']([^"\']+)["\']/i', $html, $matches) ) {
            foreach ( $matches[1] as $url ) {
                if ( preg_match('~^https?://~i', $url) ) $found[] = $url;
            }
        }
        
        // Buscar cualquier URL de imagen en atributos de img
        if ( preg_match_all('~<img[^>]*?(https?://[^\s"\'<>]+\.(?:jpe?g|png|gif|webp|svg))~i', $html, $matches) ) {
            foreach ( $matches[1] as $url ) {
                $found[] = $url;
            }
        }
        
        return $found;
    };

    // 4) content:encoded (CRUDO)
    $enc = $item->get_item_tags('http://purl.org/rss/1.0/modules/content/','encoded');
    if ( ! empty($enc[0]['data']) ) {
        $imgs = $find_all_imgs( $enc[0]['data'] );
        if ( $imgs ) $images = array_merge( $images, $imgs );
    }

    // 5) get_content()
    $imgs = $find_all_imgs( $item->get_content() );
    if ( $imgs ) $images = array_merge( $images, $imgs );

    // 6) description
    $imgs = $find_all_imgs( $item->get_description() );
    if ( $imgs ) $images = array_merge( $images, $imgs );
    
    // 7) Buscar en iframes de YouTube/Vimeo (obtener thumbnail)
    $get_video_thumbs = function( $html ) {
        if ( ! $html ) return [];
        $thumbs = [];
        
        // YouTube (múltiples formatos)
        if ( preg_match_all('~(?:youtube\.com/embed/|youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)~i', $html, $matches) ) {
            foreach ( $matches[1] as $video_id ) {
                $thumbs[] = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
                $thumbs[] = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
            }
        }
        
        // Vimeo
        if ( preg_match_all('~player\.vimeo\.com/video/(\d+)~i', $html, $matches) ) {
            foreach ( $matches[1] as $video_id ) {
                $json = wp_remote_get( 'https://vimeo.com/api/v2/video/' . $video_id . '.json', ['timeout' => 10] );
                if ( ! is_wp_error( $json ) ) {
                    $body = wp_remote_retrieve_body( $json );
                    $data = json_decode( $body, true );
                    if ( isset($data[0]['thumbnail_large']) ) {
                        $thumbs[] = $data[0]['thumbnail_large'];
                    }
                }
            }
        }
        
        return $thumbs;
    };
    
    if ( ! empty($enc[0]['data']) ) {
        $video_thumbs = $get_video_thumbs( $enc[0]['data'] );
        if ( $video_thumbs ) $images = array_merge( $images, $video_thumbs );
    }
    
    $video_thumbs = $get_video_thumbs( $item->get_content() );
    if ( $video_thumbs ) $images = array_merge( $images, $video_thumbs );
    
    // 8) Open Graph image desde la URL original
    $permalink = $item->get_permalink();
    if ( $permalink ) {
        $og_image = fcsd_extract_og_image( $permalink );
        if ( $og_image ) {
            array_unshift( $images, $og_image ); // Prioridad alta
        }
    }
    
    // Eliminar duplicados y filtrar válidas
    $images = array_values( array_unique( array_filter( $images, function($url) {
        return preg_match('~^https?://~i', $url) && preg_match('~\.(jpe?g|png|gif|webp|svg)($|\?)~i', $url);
    })));
    
    return $images;
}

/**
 * Extrae la imagen Open Graph de una URL
 */
function fcsd_extract_og_image( $url ) {
    $response = wp_remote_get( $url, ['timeout' => 5] );
    if ( is_wp_error( $response ) ) return false;
    
    $html = wp_remote_retrieve_body( $response );
    if ( empty( $html ) ) return false;
    
    // Buscar meta property="og:image"
    if ( preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $match) ) {
        return $match[1];
    }
    
    // Buscar meta name="twitter:image"
    if ( preg_match('/<meta\s+name=["\']twitter:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $match) ) {
        return $match[1];
    }
    
    return false;
}

function fcsd_first_image_from_item( $item ) {
    $images = fcsd_extract_all_images_from_item( $item );
    return ! empty( $images ) ? $images[0] : false;
}

/**
 * ============================================================================
 * SINCRONIZACIÓN CON CONTENIDO COMPLETO
 * ============================================================================
 */
function fcsd_sync_exit21_news() {
    fcsd_log( "========== INICIO DE SINCRONIZACIÓN EXIT21 ==========", 'info' );
    
    if ( ! function_exists( 'fetch_feed' ) ) {
        include_once ABSPATH . WPINC . '/feed.php';
    }

    $feeds = [
        [ 'url' => 'https://www.exit21.org/feed/',    'lang' => 'ca' ],
        [ 'url' => 'https://www.exit21.org/es/feed/', 'lang' => 'es' ],
    ];

    foreach ( $feeds as $feed_def ) {
        $feed_url  = $feed_def['url'];
        $feed_lang = $feed_def['lang'];

    // Fuerza refresco
    delete_transient( 'feed_' . md5( $feed_url ) );
    delete_transient( 'feed_mod_' . md5( $feed_url ) );

    $rss = fetch_feed( $feed_url );
    if ( is_wp_error( $rss ) ) {
        fcsd_log( 'Error al obtener feed RSS: ' . $rss->get_error_message(), 'error' );
        continue;
    }

    $max = $rss->get_item_quantity( 500 );
    if ( ! $max ) {
        fcsd_log( 'No hay items en el feed', 'warning' );
        continue;
    }

    fcsd_log( "Feed obtenido correctamente. Items encontrados: {$max}", 'info' );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    foreach ( $rss->get_items(0, $max) as $item ) {
        $guid = $item->get_id() ?: $item->get_permalink();
        $permalink = $item->get_permalink();
        
        fcsd_log( "Procesando: {$guid}", 'debug' );

        // ====================================================================
        // OBTENER CONTENIDO COMPLETO VIA SCRAPING
        // ====================================================================
        $full_content = '';
        
        // Intentar obtener contenido completo desde la URL original
        if ( $permalink ) {
            $scraped_content = fcsd_fetch_full_content( $permalink );
            if ( $scraped_content && strlen( strip_tags($scraped_content) ) > strlen( strip_tags($item->get_content()) ) ) {
                $full_content = $scraped_content;
                fcsd_log( "Usando contenido de scraping (más completo)", 'success' );
            }
        }
        
        // Fallback al contenido del RSS
        if ( empty( $full_content ) ) {
            $full_content = $item->get_content();
            fcsd_log( "Usando contenido del RSS (fallback)", 'info' );
        }

        // ====================================================================
        // CREAR O ACTUALIZAR POST
        // ====================================================================
        $guid_key = fcsd_exit21_guid_key( (string) $guid, (string) $feed_lang );

        $existing = get_posts([
            'post_type'      => 'news',
            'post_status'    => 'any',
            'meta_key'       => 'exit21_guid',
            'meta_value'     => $guid_key,
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

        // Compatibilidad: instalaciones antiguas guardaban el GUID sin idioma.
        if ( ! $existing && $guid_key !== (string) $guid ) {
            $existing = get_posts([
                'post_type'      => 'news',
                'post_status'    => 'any',
                'meta_key'       => 'exit21_guid',
                'meta_value'     => (string) $guid,
                'fields'         => 'ids',
                'posts_per_page' => 1,
            ]);
        }

        $post_data = [
            'post_title'   => $item->get_title() ?: 'Sin título',
            'post_content' => $full_content,
            'post_status'  => 'publish',
            'post_type'    => 'news',
        ];

        if ( $existing ) {
            $post_id = (int) $existing[0];
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
            fcsd_log( "Post actualizado: {$post_id}", 'info' );
        } else {
            $post_data['post_date'] = $item->get_date('Y-m-d H:i:s') ?: current_time('mysql');
            $post_id = wp_insert_post( $post_data );
            if ( is_wp_error($post_id) || ! $post_id ) {
                fcsd_log( "Error al crear post: " . (is_wp_error($post_id) ? $post_id->get_error_message() : 'desconocido'), 'error' );
                continue;
            }
            fcsd_log( "Post creado: {$post_id}", 'success' );
        }

        // ====================================================================
        // METAS BASE
        // ====================================================================
        // Guardar/migrar GUID con sufijo de idioma.
        update_post_meta( $post_id, 'exit21_guid', $guid_key ?: (string) $guid );
        update_post_meta( $post_id, 'news_source', 'exit21' );
        update_post_meta( $post_id, 'news_external_url', $permalink );
        // Idioma (ES/CA) — preferimos el idioma del feed que estamos procesando.
        $lang = $feed_lang ?: ( ( strpos( $permalink, '/es/' ) !== false || preg_match('~//[^/]+/es(/|$)~', $permalink ) ) ? 'es' : 'ca' );
        update_post_meta( $post_id, 'news_language', $lang );

        // Ámbito por defecto: Institucional (si no tiene ninguno asignado)
        // Debe ocurrir en la importación para evitar depender de permisos.
        fcsd_news_ensure_default_area( $post_id );

        // ====================================================================
        // AUTORES
        // ====================================================================
        $author_raw = '';
        $author_obj = $item->get_author();
        if ( $author_obj ) {
            if ( method_exists( $author_obj, 'get_name' ) )  $author_raw = $author_obj->get_name();
            if ( ! $author_raw && method_exists( $author_obj, 'get_email' ) ) $author_raw = $author_obj->get_email();
        }
        
        if ( $author_raw ) {
            $a = html_entity_decode( $author_raw, ENT_QUOTES, 'UTF-8' );
            $a = str_replace( "\xC2\xA0", ' ', $a );
            $a = wp_strip_all_tags( $a );
            $a = preg_replace( '~\s*(?:&|and| i | y |;|\+)\s*~iu', ',', $a );
            $authors = array_values( array_unique( array_filter( array_map(
                fn($s)=>trim(preg_replace('/\s+/u',' ',$s)), explode(',', $a)
            ))));
            
            if ( $authors ) {
                update_post_meta( $post_id, 'news_authors', $authors );
                update_post_meta( $post_id, 'news_author', implode( ', ', $authors ) );
            } else {
                update_post_meta( $post_id, 'news_author', $a );
                delete_post_meta( $post_id, 'news_authors' );
            }
        }

        // ====================================================================
        // CATEGORÍAS
        // ====================================================================
        $cat_names = [];
        $cats = $item->get_categories();
        if ( is_array( $cats ) ) {
            foreach ( $cats as $c ) {
                $label = '';
                if ( is_object( $c ) ) {
                    if ( method_exists( $c, 'get_term' ) )  $label = $c->get_term();
                    if ( ! $label && method_exists( $c, 'get_label' ) ) $label = $c->get_label();
                }
                if ( $label ) $cat_names[] = wp_strip_all_tags( $label );
            }
        }

        foreach ( (array) $item->get_item_tags( '', 'category' ) as $tag ) {
            if ( ! empty( $tag['data'] ) ) $cat_names[] = wp_strip_all_tags( $tag['data'] );
        }

        $term_ids = [];
        if ( $cat_names ) {
            $cat_names = array_values( array_unique( $cat_names ) );
            update_post_meta( $post_id, 'news_categories_raw', $cat_names );

            foreach ( $cat_names as $name ) {
                $slug  = sanitize_title( $name );
                $found = term_exists( $slug, 'category' );
                if ( 0 === $found || null === $found ) {
                    $created_term = wp_insert_term( $name, 'category', [ 'slug' => $slug ] );
                    if ( ! is_wp_error( $created_term ) ) $term_ids[] = (int) $created_term['term_id'];
                } else {
                    $term_ids[] = (int) ( is_array( $found ) ? $found['term_id'] : $found );
                }
            }
            if ( $term_ids ) wp_set_post_terms( $post_id, $term_ids, 'category', false );
        }

        // ====================================================================
        // IMAGEN DESTACADA - ESTRATEGIA MÚLTIPLE
        // ====================================================================
        $img = fcsd_first_image_from_item( $item );
        if ( $img ) {
            update_post_meta( $post_id, 'news_image_src', esc_url_raw( $img ) );
            
            // Solo descargar si no tiene thumbnail
            if ( ! has_post_thumbnail( $post_id ) ) {
                $tmp = download_url( $img, 30 );
                if ( ! is_wp_error( $tmp ) ) {
                    $file_array = [
                        'name'     => basename( parse_url( $img, PHP_URL_PATH ) ) ?: 'image-' . $post_id . '.jpg',
                        'tmp_name' => $tmp,
                    ];
                    
                    $att_id = media_handle_sideload( $file_array, $post_id );
                    
                    if ( ! is_wp_error( $att_id ) ) {
                        set_post_thumbnail( $post_id, $att_id );
                        fcsd_log( "Imagen destacada establecida: {$att_id}", 'success' );
                    } else {
                        @unlink( $tmp );
                        fcsd_log( "Error al subir imagen: " . $att_id->get_error_message(), 'error' );
                    }
                } else {
                    fcsd_log( "Error al descargar imagen {$img}: " . $tmp->get_error_message(), 'error' );
                }
            }
        }
    }

    }

    fcsd_log( "========== FIN DE SINCRONIZACIÓN ==========", 'info' );
}

/**
 * ============================================================================
 * FALLBACK DE IMAGEN DESTACADA
 * ============================================================================
 */
add_filter('post_thumbnail_html', function( $html, $post_id, $thumb_id, $size, $attr ){
    if ( $html || get_post_type( $post_id ) !== 'news' ) return $html;
    $src = get_post_meta( $post_id, 'news_image_src', true );
    if ( ! $src ) return $html;

    $size_slug = is_string($size) ? $size : 'news-thumb';
    $classes = 'wp-post-image attachment-'.$size_slug.' size-'.$size_slug;
    $attr = is_array($attr) ? $attr : [];
    $attr['class']    = trim( ($attr['class'] ?? '').' '.$classes );
    $attr['src']      = esc_url( $src );
    $attr['alt']      = esc_attr( get_the_title( $post_id ) );
    $attr['loading']  = $attr['loading']  ?? 'lazy';
    $attr['decoding'] = $attr['decoding'] ?? 'async';

    $pairs = [];
    foreach ( $attr as $k=>$v ) $pairs[] = sprintf('%s="%s"', esc_attr($k), esc_attr($v));
    return '<img '.implode(' ', $pairs).' />';
}, 10, 5);

/**
 * ============================================================================
 * SINCRONIZACIÓN EN SEGUNDO PLANO (SIN BLOQUEAR ADMIN/FRONT)
 *
 * En este tema NO hay plugins, así que implementamos una cola muy ligera:
 * - Al pulsar "Importar" solo se agenda una ejecución.
 * - El procesado se hace en micro-batches (1 item por tick) para no secuestrar PHP.
 * - El admin muestra progreso e historial.
 *
 * Importante: evitamos ejecutar la sync en la misma request del wp-admin.
 * ============================================================================
 */

/**
 * Feeds EXIT21.
 *
 * Nota: la sincronización "clásica" (fcsd_sync_exit21_news) ya recorre ambos idiomas.
 * La sincronización en segundo plano (usada en servidor) tenía un bug y solo apuntaba
 * al feed por defecto (catalán). Esto provocaba que nunca se importaran las entradas
 * en español.
 */
const FCSD_EXIT21_FEEDS = [
    [ 'url' => 'https://www.exit21.org/feed/',    'lang' => 'ca' ],
    [ 'url' => 'https://www.exit21.org/es/feed/', 'lang' => 'es' ],
];

/**
 * Construye una clave estable para evitar colisiones entre idiomas.
 *
 * En EXIT21 es posible que el GUID del RSS no sea único entre /feed/ y /es/feed/.
 * Guardamos la meta exit21_guid como "{guid}|{lang}" cuando hay idioma.
 *
 * Mantiene compatibilidad con instalaciones anteriores: si existe un post con el
 * GUID sin sufijo, se reutiliza y se migra al formato nuevo.
 */
function fcsd_exit21_guid_key( string $guid, string $lang = '' ): string {
    $guid = trim( $guid );
    $lang = trim( $lang );
    return ( $guid && $lang ) ? ( $guid . '|' . $lang ) : $guid;
}
const FCSD_EXIT21_HISTORY_OPTION = 'fcsd_exit21_sync_history';
const FCSD_EXIT21_ACTIVE_OPTION  = 'fcsd_exit21_sync_active_run';
const FCSD_EXIT21_STATE_OPTION   = 'fcsd_exit21_sync_state';

function fcsd_exit21_history_get(): array {
    $h = get_option( FCSD_EXIT21_HISTORY_OPTION, [] );
    return is_array( $h ) ? $h : [];
}

function fcsd_exit21_history_add( array $run ): void {
    $history = fcsd_exit21_history_get();
    array_unshift( $history, $run );
    // Limitar historial
    $history = array_slice( $history, 0, 50 );
    update_option( FCSD_EXIT21_HISTORY_OPTION, $history, false );
}

function fcsd_exit21_history_update( string $run_id, array $patch ): void {
    $history = fcsd_exit21_history_get();
    foreach ( $history as &$r ) {
        if ( ( $r['id'] ?? '' ) === $run_id ) {
            $r = array_merge( $r, $patch );
            break;
        }
    }
    update_option( FCSD_EXIT21_HISTORY_OPTION, $history, false );
}

function fcsd_exit21_state_get(): array {
    $s = get_option( FCSD_EXIT21_STATE_OPTION, [] );
    return is_array( $s ) ? $s : [];
}

function fcsd_exit21_state_set( array $state ): void {
    update_option( FCSD_EXIT21_STATE_OPTION, $state, false );
}

function fcsd_exit21_kick_async(): void {
    // Dispara wp-cron sin bloquear (si está habilitado) y además programa un tick inmediato.
    if ( ! wp_next_scheduled( 'fcsd_exit21_process_tick' ) ) {
        wp_schedule_single_event( time() + 2, 'fcsd_exit21_process_tick' );
    }
    // "Fire-and-forget" a wp-cron (no siempre funciona en local, pero ayuda en prod)
    wp_remote_post( site_url( '/wp-cron.php?doing_wp_cron=' . rawurlencode( (string) microtime( true ) ) ), [
        'timeout'   => 0.01,
        'blocking'  => false,
        'sslverify' => false,
    ] );
}

function fcsd_exit21_start_run(): array {
    if ( ! function_exists( 'fetch_feed' ) ) {
        include_once ABSPATH . WPINC . '/feed.php';
    }

    $queue = [];
    $total_items = 0;

    foreach ( FCSD_EXIT21_FEEDS as $feed_def ) {
        $feed_url  = (string) ( $feed_def['url'] ?? '' );
        $feed_lang = (string) ( $feed_def['lang'] ?? '' );
        if ( ! $feed_url ) continue;

        // Fuerza refresco del feed (por URL)
        delete_transient( 'feed_' . md5( $feed_url ) );
        delete_transient( 'feed_mod_' . md5( $feed_url ) );

        $rss = fetch_feed( $feed_url );
        if ( is_wp_error( $rss ) ) {
            // No abortamos el run: si un idioma falla, el otro puede importar.
            fcsd_log( 'Error al obtener feed RSS (' . $feed_url . '): ' . $rss->get_error_message(), 'error' );
            continue;
        }

        $max = (int) $rss->get_item_quantity( 500 );
        $items = $max ? $rss->get_items( 0, $max ) : [];
        if ( ! $items ) {
            continue;
        }

        $total_items += count( $items );

        foreach ( $items as $item ) {
            $guid = $item->get_id() ?: $item->get_permalink();
            $permalink = $item->get_permalink();
            if ( ! $guid ) continue;
            $queue[] = [
                'guid'      => (string) $guid,
                'permalink' => $permalink ? (string) $permalink : '',
                'feed_url'  => $feed_url,
                'lang'      => $feed_lang,
            ];
        }
    }

    if ( ! $queue ) {
        return [ 'ok' => false, 'error' => __( 'No hay items en el feed.', 'fcsd' ) ];
    }

    // Evitar duplicados (por si el feed devuelve lo mismo en varios endpoints).
    $queue = array_values( array_reduce( $queue, function( $acc, $row ) {
        $guid = (string) ( $row['guid'] ?? '' );
        $lang = (string) ( $row['lang'] ?? '' );
        // Si el GUID es compartido entre idiomas, mantenemos ambos.
        $k = $guid . '|' . $lang;
        if ( $guid && ! isset( $acc[ $k ] ) ) $acc[ $k ] = $row;
        return $acc;
    }, [] ) );

    $run_id = 'run_' . wp_generate_uuid4();
    $now = current_time( 'mysql' );

    // Guardar cola en transient (1 día)
    set_transient( 'fcsd_exit21_queue_' . $run_id, $queue, DAY_IN_SECONDS );

    // Estado
    $state = [
        'id'        => $run_id,
        'status'    => 'scheduled',
        'total'     => count( $queue ),
        'processed' => 0,
        'errors'    => 0,
        'started_at'=> $now,
        'updated_at'=> $now,
    ];
    fcsd_exit21_state_set( $state );
    update_option( FCSD_EXIT21_ACTIVE_OPTION, $run_id, false );

    fcsd_exit21_history_add( [
        'id'         => $run_id,
        'started_at' => $now,
        'finished_at'=> '',
        'status'     => 'scheduled',
        'total'      => count( $queue ),
        'processed'  => 0,
        'errors'     => 0,
    ] );

    fcsd_exit21_kick_async();

    return [ 'ok' => true, 'run_id' => $run_id, 'total' => count( $queue ) ];
}

function fcsd_exit21_is_reachable(): bool {
    $cached = get_transient( 'fcsd_exit21_reachable' );
    if ( $cached !== false ) return (bool) $cached;

    $r = wp_remote_head( 'https://www.exit21.org/', [
        'timeout'   => 2,
        'blocking'  => true,
        'sslverify' => false,
    ] );
    $ok = ! is_wp_error( $r ) && (int) wp_remote_retrieve_response_code( $r ) >= 200 && (int) wp_remote_retrieve_response_code( $r ) < 500;
    // Cache corto: si falla no reintentar en bucle
    set_transient( 'fcsd_exit21_reachable', $ok ? 1 : 0, $ok ? 60 : 300 );
    return $ok;
}

function fcsd_exit21_process_one_item( array $payload ): bool {
    if ( ! function_exists( 'fetch_feed' ) ) {
        include_once ABSPATH . WPINC . '/feed.php';
    }

    $guid = $payload['guid'] ?? '';
    if ( ! $guid ) return true;

    $feed_url  = (string) ( $payload['feed_url'] ?? '' );
    $feed_lang = (string) ( $payload['lang'] ?? '' );
    if ( ! $feed_url ) {
        // Compatibilidad: si llega un payload antiguo, caemos al feed por defecto.
        $feed_url = (string) ( FCSD_EXIT21_FEEDS[0]['url'] ?? 'https://www.exit21.org/feed/' );
    }

    // Volvemos a pedir el feed pero NO lo forzamos siempre; SimplePie cache ayuda.
    $rss = fetch_feed( $feed_url );
    if ( is_wp_error( $rss ) ) {
        fcsd_log( 'Error al obtener feed RSS (tick) (' . $feed_url . '): ' . $rss->get_error_message(), 'error' );
        return false;
    }

    $items = $rss->get_items( 0, 500 );
    if ( ! $items ) return true;

    $target = null;
    foreach ( $items as $it ) {
        $it_guid = $it->get_id() ?: $it->get_permalink();
        if ( (string) $it_guid === (string) $guid ) {
            $target = $it;
            break;
        }
    }
    if ( ! $target ) {
        // Item ya no está en feed; considerarlo procesado.
        return true;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $permalink = $target->get_permalink();

    // Contenido
    $full_content = '';
    $reachable = fcsd_exit21_is_reachable();
    if ( $reachable && $permalink ) {
        // Reducimos timeouts en scraping para no bloquear.
        $scraped_content = fcsd_fetch_full_content( $permalink );
        if ( $scraped_content && strlen( strip_tags( $scraped_content ) ) > strlen( strip_tags( $target->get_content() ) ) ) {
            $full_content = $scraped_content;
        }
    }
    if ( empty( $full_content ) ) $full_content = $target->get_content();

    // Crear/actualizar post
    $guid_key = fcsd_exit21_guid_key( (string) $guid, (string) $feed_lang );

    $existing = get_posts([
        'post_type'      => 'news',
        'post_status'    => 'any',
        'meta_key'       => 'exit21_guid',
        'meta_value'     => $guid_key,
        'fields'         => 'ids',
        'posts_per_page' => 1,
    ]);

    // Compatibilidad: instalaciones antiguas guardaban el GUID sin idioma.
    if ( ! $existing && $guid_key !== (string) $guid ) {
        $existing = get_posts([
            'post_type'      => 'news',
            'post_status'    => 'any',
            'meta_key'       => 'exit21_guid',
            'meta_value'     => (string) $guid,
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);
    }

    $post_data = [
        'post_title'   => $target->get_title() ?: 'Sin título',
        'post_content' => $full_content,
        'post_status'  => 'publish',
        'post_type'    => 'news',
    ];

    if ( $existing ) {
        $post_id = (int) $existing[0];
        $post_data['ID'] = $post_id;
        wp_update_post( $post_data );
    } else {
        $post_data['post_date'] = $target->get_date('Y-m-d H:i:s') ?: current_time('mysql');
        $post_id = wp_insert_post( $post_data );
        if ( is_wp_error( $post_id ) || ! $post_id ) {
            return false;
        }
    }

        update_post_meta( $post_id, 'exit21_guid', $guid_key );
    update_post_meta( $post_id, 'news_source', 'exit21' );
    update_post_meta( $post_id, 'news_external_url', $permalink );

    // Idioma (ES/CA): preferimos el idioma del feed procesado en este tick.
    $lang = $feed_lang ?: ( ( $permalink && ( strpos( $permalink, '/es/' ) !== false || preg_match( '~//[^/]+/es(/|$)~', $permalink ) ) ) ? 'es' : 'ca' );
    update_post_meta( $post_id, 'news_language', $lang );

    // Autores
    $author_raw = '';
    $author_obj = $target->get_author();
    if ( $author_obj ) {
        if ( method_exists( $author_obj, 'get_name' ) )  $author_raw = $author_obj->get_name();
        if ( ! $author_raw && method_exists( $author_obj, 'get_email' ) ) $author_raw = $author_obj->get_email();
    }
    if ( $author_raw ) {
        $a = html_entity_decode( $author_raw, ENT_QUOTES, 'UTF-8' );
        $a = str_replace( "\xC2\xA0", ' ', $a );
        $a = wp_strip_all_tags( $a );
        $a = preg_replace( '~\s*(?:&|and| i | y |;|\+)\s*~iu', ',', $a );
        $authors = array_values( array_unique( array_filter( array_map(
            fn($s)=>trim(preg_replace('/\s+/u',' ',$s)), explode(',', $a)
        ))));
        if ( $authors ) {
            update_post_meta( $post_id, 'news_authors', $authors );
            update_post_meta( $post_id, 'news_author', implode( ', ', $authors ) );
        } else {
            update_post_meta( $post_id, 'news_author', $a );
            delete_post_meta( $post_id, 'news_authors' );
        }
    }

    // Categorías
    $cat_names = [];
    $cats = $target->get_categories();
    if ( is_array( $cats ) ) {
        foreach ( $cats as $c ) {
            $label = '';
            if ( is_object( $c ) ) {
                if ( method_exists( $c, 'get_term' ) )  $label = $c->get_term();
                if ( ! $label && method_exists( $c, 'get_label' ) ) $label = $c->get_label();
            }
            if ( $label ) $cat_names[] = wp_strip_all_tags( $label );
        }
    }
    foreach ( (array) $target->get_item_tags( '', 'category' ) as $tag ) {
        if ( ! empty( $tag['data'] ) ) $cat_names[] = wp_strip_all_tags( $tag['data'] );
    }
    $term_ids = [];
    if ( $cat_names ) {
        $cat_names = array_values( array_unique( $cat_names ) );
        update_post_meta( $post_id, 'news_categories_raw', $cat_names );
        foreach ( $cat_names as $name ) {
            $slug  = sanitize_title( $name );
            $found = term_exists( $slug, 'category' );
            if ( 0 === $found || null === $found ) {
                $created_term = wp_insert_term( $name, 'category', [ 'slug' => $slug ] );
                if ( ! is_wp_error( $created_term ) ) $term_ids[] = (int) $created_term['term_id'];
            } else {
                $term_ids[] = (int) ( is_array( $found ) ? $found['term_id'] : $found );
            }
        }
        if ( $term_ids ) wp_set_post_terms( $post_id, $term_ids, 'category', false );
    }

    // Imagen destacada
    $img = fcsd_first_image_from_item( $target );
    if ( $img ) {
        update_post_meta( $post_id, 'news_image_src', esc_url_raw( $img ) );
        if ( ! has_post_thumbnail( $post_id ) ) {
            $tmp = download_url( $img, 20 );
            if ( ! is_wp_error( $tmp ) ) {
                $file_array = [
                    'name'     => basename( parse_url( $img, PHP_URL_PATH ) ) ?: 'image-' . $post_id . '.jpg',
                    'tmp_name' => $tmp,
                ];
                $att_id = media_handle_sideload( $file_array, $post_id );
                if ( ! is_wp_error( $att_id ) ) {
                    set_post_thumbnail( $post_id, $att_id );
                } else {
                    @unlink( $tmp );
                }
            }
        }
    }

    return true;
}

function fcsd_exit21_process_tick_inner(): array {
    $run_id = (string) get_option( FCSD_EXIT21_ACTIVE_OPTION, '' );
    if ( ! $run_id ) return [ 'ok' => true, 'done' => true ];

    $state = fcsd_exit21_state_get();
    if ( ( $state['id'] ?? '' ) !== $run_id ) {
        // Estado inconsistente -> reset
        delete_option( FCSD_EXIT21_ACTIVE_OPTION );
        return [ 'ok' => true, 'done' => true ];
    }

    $queue = get_transient( 'fcsd_exit21_queue_' . $run_id );
    if ( ! is_array( $queue ) ) $queue = [];

    $idx = (int) ( $state['processed'] ?? 0 );
    $total = (int) ( $state['total'] ?? count( $queue ) );

    if ( $idx >= $total || $idx >= count( $queue ) ) {
        // Finalizar
        $now = current_time( 'mysql' );
        $state['status'] = 'finished';
        $state['updated_at'] = $now;
        fcsd_exit21_state_set( $state );
        fcsd_exit21_history_update( $run_id, [
            'status'      => 'finished',
            'finished_at' => $now,
            'processed'   => (int) $state['processed'],
            'errors'      => (int) $state['errors'],
        ] );
        delete_option( FCSD_EXIT21_ACTIVE_OPTION );
        delete_transient( 'fcsd_exit21_queue_' . $run_id );
        return [ 'ok' => true, 'done' => true ];
    }

    // Marcar running
    $state['status'] = 'running';
    $state['updated_at'] = current_time( 'mysql' );

    // Procesar 1 item
    $ok = fcsd_exit21_process_one_item( $queue[ $idx ] );
    if ( ! $ok ) {
        $state['errors'] = (int) ( $state['errors'] ?? 0 ) + 1;
    }
    $state['processed'] = $idx + 1;
    fcsd_exit21_state_set( $state );
    fcsd_exit21_history_update( $run_id, [
        'status'    => 'running',
        'processed' => (int) $state['processed'],
        'errors'    => (int) $state['errors'],
    ] );

    // Programar siguiente tick (muy pronto)
    wp_schedule_single_event( time() + 3, 'fcsd_exit21_process_tick' );
    fcsd_exit21_kick_async();

    return [ 'ok' => true, 'done' => false ];
}

add_action( 'fcsd_exit21_process_tick', function () {
    // Ejecutar SOLO en cron o en endpoint explícito.
    if ( ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
        return;
    }
    @set_time_limit( 20 );
    fcsd_exit21_process_tick_inner();
} );

// Endpoint REST (solo admin) para avanzar un tick si en local wp-cron no dispara.
add_action( 'rest_api_init', function () {
    register_rest_route( 'fcsd/v1', '/exit21/tick', [
        'methods'             => 'POST',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
        'callback' => function () {
            @set_time_limit( 20 );
            $res = fcsd_exit21_process_tick_inner();
            return rest_ensure_response( $res );
        },
    ] );
    register_rest_route( 'fcsd/v1', '/exit21/status', [
        'methods'             => 'GET',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
        'callback' => function () {
            $state = fcsd_exit21_state_get();
            $active = (string) get_option( FCSD_EXIT21_ACTIVE_OPTION, '' );
            return rest_ensure_response( [
                'active_run' => $active,
                'state'      => $state,
                'history'    => fcsd_exit21_history_get(),
            ] );
        },
    ] );
} );

// Admin UI
add_action( 'admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=news',
        __( 'Sync EXIT21', 'fcsd' ),
        __( 'Sync EXIT21', 'fcsd' ),
        'manage_options',
        'fcsd-sync-exit21',
        'fcsd_render_exit21_sync_page'
    );
} );

add_action( 'wp_ajax_fcsd_exit21_start', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
    check_ajax_referer( 'fcsd_exit21_sync' );
    $res = fcsd_exit21_start_run();
    if ( ! ( $res['ok'] ?? false ) ) {
        wp_send_json_error( [ 'message' => $res['error'] ?? 'error' ], 500 );
    }
    wp_send_json_success( $res );
} );

function fcsd_render_exit21_sync_page() {
    $nonce = wp_create_nonce( 'fcsd_exit21_sync' );
    $rest_nonce = wp_create_nonce( 'wp_rest' );
    $state = fcsd_exit21_state_get();
    $history = fcsd_exit21_history_get();

    // Si por cualquier motivo quedó un run activo (por ejemplo en local con wp-cron deshabilitado),
    // evitamos bloquear la pantalla para siempre: detectamos estado estancado y liberamos el botón.
    $state = fcsd_exit21_state_get();
    $active = (string) get_option( FCSD_EXIT21_ACTIVE_OPTION, '' );
    if ( $active ) {
        $updated_at = isset($state['updated_at']) ? strtotime((string) $state['updated_at']) : 0;
        $updated_at = $updated_at ?: 0;
        $has_next = (bool) wp_next_scheduled( 'fcsd_exit21_process_tick' );
        // Si no hay próximo tick y hace >5 min que no se actualiza el estado, lo consideramos estancado.
        if ( ! $has_next && ( time() - $updated_at ) > 300 ) {
            delete_option( FCSD_EXIT21_ACTIVE_OPTION );
            $active = '';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Sincronización EXIT21', 'fcsd' ); ?></h1>

        <div class="card" style="max-width: 920px;">
            <h2><?php esc_html_e( 'Importar Feed EXIT21', 'fcsd' ); ?></h2>
            <p><?php esc_html_e( 'Importa el feed de exit21.org en segundo plano (sin bloquear el backend ni el frontend).', 'fcsd' ); ?></p>

            <p style="display:flex; gap:12px; align-items:center;">
                <button id="fcsd-exit21-start" class="button button-primary button-hero" <?php echo $active ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-update" style="margin-top:8px;"></span>
                    <?php echo $active ? esc_html__( 'Sincronitzant en segon pla...', 'fcsd' ) : esc_html__( 'Importar Ara (background)', 'fcsd' ); ?>
                </button>
                <span id="fcsd-exit21-status" style="color:#50575e;"></span>
            </p>

            <div id="fcsd-exit21-progress" style="margin-top:14px;"></div>
        </div>

        <div class="card" style="max-width: 920px;">
            <h2><?php esc_html_e( 'Historial de sincronizaciones', 'fcsd' ); ?></h2>
            <?php if ( ! $history ) : ?>
                <p><?php esc_html_e( 'Aún no se ha realizado ninguna sincronización.', 'fcsd' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Inicio', 'fcsd' ); ?></th>
                            <th><?php esc_html_e( 'Fin', 'fcsd' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'fcsd' ); ?></th>
                            <th><?php esc_html_e( 'Procesadas', 'fcsd' ); ?></th>
                            <th><?php esc_html_e( 'Errores', 'fcsd' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $history as $r ) : ?>
                            <tr>
                                <td><?php echo esc_html( $r['started_at'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $r['finished_at'] ?? '' ); ?></td>
                                <td><?php echo esc_html( $r['status'] ?? '' ); ?></td>
                                <td><?php echo esc_html( (string) ( $r['processed'] ?? 0 ) ); ?> / <?php echo esc_html( (string) ( $r['total'] ?? 0 ) ); ?></td>
                                <td><?php echo esc_html( (string) ( $r['errors'] ?? 0 ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .card { margin: 20px 0; padding: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .card h2 { margin-top: 0; }
        .button-hero { padding: 10px 20px !important; height: auto !important; font-size: 14px !important; }
        .fcsd-bar { height: 10px; background: #e5e5e5; border-radius: 999px; overflow: hidden; }
        .fcsd-bar > div { height: 10px; background: #2271b1; width: 0%; }
    </style>

    <script>
    (function(){
        const ajaxUrl = <?php echo wp_json_encode( admin_url('admin-ajax.php') ); ?>;
        const nonce = <?php echo wp_json_encode( $nonce ); ?>;
        const restTick = <?php echo wp_json_encode( rest_url('fcsd/v1/exit21/tick') ); ?>;
        const restStatus = <?php echo wp_json_encode( rest_url('fcsd/v1/exit21/status') ); ?>;
        const restNonce = <?php echo wp_json_encode( $rest_nonce ); ?>;
        const startBtn = document.getElementById('fcsd-exit21-start');
        const statusEl = document.getElementById('fcsd-exit21-status');
        const progressEl = document.getElementById('fcsd-exit21-progress');

        function pct(done,total){
            if(!total) return 0;
            return Math.min(100, Math.round((done/total)*100));
        }

        async function fetchStatus(){
            const res = await fetch(restStatus, {credentials:'same-origin', headers:{'X-WP-Nonce': restNonce}});
            return await res.json();
        }

        async function tick(){
            await fetch(restTick, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-WP-Nonce': restNonce}});
        }

        function renderState(state){
            const total = state?.total || 0;
            const done = state?.processed || 0;
            const p = pct(done,total);
            const st = state?.status || 'idle';
            statusEl.textContent = st === 'idle' ? '' : ('Estat: ' + st + ' · Progrés: ' + done + '/' + total);
            progressEl.innerHTML = `
                <div class="fcsd-bar"><div style="width:${p}%"></div></div>
                <p style="margin-top:8px;color:#50575e;">${p}%</p>
            `;
        }

        let polling = null;
        async function ensureLoop(){
            if(polling) return;
            polling = setInterval(async ()=>{
                try{
                    const data = await fetchStatus();
                    const active = data.active_run;
                    renderState(data.state || {});
                    if(active){
                        // En local, forzamos avance con ticks muy cortos.
                        await tick();
                        startBtn.disabled = true;
                        startBtn.textContent = 'Sincronitzant en segon pla...';
                    }else{
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<span class="dashicons dashicons-update" style="margin-top:8px;"></span> Importar Ara (background)';
                    }
                }catch(e){
                    // noop
                }
            }, 3000);
        }

        if(startBtn){
            startBtn.addEventListener('click', async (e)=>{
                e.preventDefault();
                startBtn.disabled = true;
                statusEl.textContent = 'Programant...';
                const fd = new URLSearchParams();
                fd.set('action','fcsd_exit21_start');
                fd.set('_ajax_nonce', nonce);
                const r = await fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: fd.toString()});
                const j = await r.json();
                if(!j.success){
                    statusEl.textContent = 'Error: ' + (j.data?.message || 'unknown');
                    startBtn.disabled = false;
                    return;
                }
                statusEl.textContent = 'Scheduled';
                await ensureLoop();
            });
        }

        // Auto-loop si ya hay una sync activa
        ensureLoop();
        renderState(<?php echo wp_json_encode( $state ); ?>);
    })();
    </script>
<?php }

/**
 * ============================================================================
 * COLUMNAS PERSONALIZADAS EN ADMIN
 * ============================================================================
 */
add_filter( 'manage_news_posts_columns', function ( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        if ( 'cb' === $key ) $new[ $key ] = $label;
        if ( 'cb' === $key ) $new['news_thumbnail'] = __( 'Imatge', 'fcsd' );
        if ( in_array( $key, [ 'title', 'date' ], true ) ) $new[ $key ] = $label;
        if ( 'date' === $key ) {
            $new['news_source']       = __( 'Font', 'fcsd' );
            $new['news_category']     = __( 'Categoria', 'fcsd' );
            $new['news_author']       = __( 'Autor(s)', 'fcsd' );
            $new['news_external_url'] = __( 'URL origen', 'fcsd' );
        }
    }
    return $new;
} );

add_action( 'manage_news_posts_custom_column', function ( $column, $post_id ) {
    if ( 'news_thumbnail' === $column ) {
        if ( has_post_thumbnail( $post_id ) ) {
            echo get_the_post_thumbnail( $post_id, [ 60, 60 ], [ 'style' => 'display:block;width:60px;height:60px;object-fit:cover;border-radius:4px;' ] );
        } else {
            $src = get_post_meta( $post_id, 'news_image_src', true );
            if ( $src ) {
                echo '<img src="' . esc_url( $src ) . '" alt="" style="display:block;width:60px;height:60px;object-fit:cover;border-radius:4px;" />';
            } else {
                echo '<span style="display:block;width:60px;height:60px;background:#ddd;border-radius:4px;"></span>';
            }
        }
        return;
    }
    
    if ( 'news_source' === $column ) {
        $source = get_post_meta( $post_id, 'news_source', true ) ?: 'INTERNAL';
        $colors = [
            'exit21' => '#2271b1',
            'INTERNAL' => '#50575e',
        ];
        $color = $colors[ $source ] ?? '#50575e';
        echo '<span style="display:inline-block;padding:3px 8px;background:' . esc_attr($color) . ';color:white;border-radius:3px;font-size:11px;font-weight:600;">' 
             . esc_html( strtoupper( $source ) ) . '</span>';
        return;
    }
    
    if ( 'news_external_url' === $column ) {
        $url = get_post_meta( $post_id, 'news_external_url', true );
        if ( $url ) {
            echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="button button-small">' 
                 . esc_html__( 'Veure origen', 'fcsd' ) . ' ↗</a>';
        }
        return;
    }
    
    if ( 'news_category' === $column ) {
        $terms = get_the_terms( $post_id, 'category' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            $names = array_map( function($term) {
                return '<span style="display:inline-block;padding:2px 6px;background:#f0f0f1;border-radius:2px;margin:2px;font-size:11px;">' 
                       . esc_html($term->name) . '</span>';
            }, $terms );
            echo implode( '', $names );
        } else {
            $cats = get_post_meta( $post_id, 'news_categories_raw', true );
            echo $cats ? esc_html( is_array( $cats ) ? implode( ', ', $cats ) : $cats ) : '&#8212;';
        }
        return;
    }
    
    if ( 'news_author' === $column ) {
        $authors = get_post_meta( $post_id, 'news_authors', true );
        if ( is_array( $authors ) && $authors ) {
            echo '<strong>' . esc_html( implode( '</strong>, <strong>', $authors ) ) . '</strong>';
        } else {
            $author = get_post_meta( $post_id, 'news_author', true );
            echo $author ? '<strong>' . esc_html( $author ) . '</strong>' : '&#8212;';
        }
        return;
    }
}, 10, 2 );

/**
 * ============================================================================
 * FILTROS DE ADMIN
 * ============================================================================
 */
add_action( 'restrict_manage_posts', function () {
    global $typenow; 
    if ( 'news' !== $typenow ) return;

    $selected_cat  = isset( $_GET['news_category'] ) ? (int) $_GET['news_category'] : 0;
    $selected_src  = isset( $_GET['news_source_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['news_source_filter'] ) ) : '';
    $selected_home = isset( $_GET['news_home_carousel'] ) ? sanitize_text_field( wp_unslash( $_GET['news_home_carousel'] ) ) : '';
    $selected_lang  = isset( $_GET['news_lang'] ) ? sanitize_text_field( wp_unslash( $_GET['news_lang'] ) ) : '';
    $date_from_val = isset( $_GET['news_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['news_date_from'] ) ) : '';
    $date_to_val   = isset( $_GET['news_date_to'] )   ? sanitize_text_field( wp_unslash( $_GET['news_date_to'] ) )   : '';

    // Filtro de categoría
    wp_dropdown_categories( [
        'show_option_all' => __( 'Totes les categories', 'fcsd' ),
        'taxonomy'        => 'category',
        'name'            => 'news_category',
        'orderby'         => 'name',
        'selected'        => $selected_cat,
        'hierarchical'    => true,
        'depth'           => 3,
        'show_count'      => false,
        'hide_empty'      => false,
    ] );
    
    // Filtro de fuente
    ?>
    <select name="news_source_filter" style="margin-left:8px;">
        <option value=""><?php esc_html_e( 'Totes les fonts', 'fcsd' ); ?></option>
        <option value="exit21" <?php selected( $selected_src, 'exit21' ); ?>>EXIT21</option>
        <option value="internal" <?php selected( $selected_src, 'internal' ); ?>><?php esc_html_e( 'Internes', 'fcsd' ); ?></option>
    </select>

    <select name="news_home_carousel" style="margin-left:8px;">
        <option value=""><?php esc_html_e( 'Home: totes', 'fcsd' ); ?></option>
        <option value="1" <?php selected( $selected_home, '1' ); ?>><?php esc_html_e( 'Home: al carrusel', 'fcsd' ); ?></option>
        <option value="0" <?php selected( $selected_home, '0' ); ?>><?php esc_html_e( 'Home: fora del carrusel', 'fcsd' ); ?></option>
    </select>

    <select name="news_lang" style="margin-left:8px;">
        <option value=""><?php esc_html_e( 'Idioma: tots', 'fcsd' ); ?></option>
        <option value="es" <?php selected( $selected_lang, 'es' ); ?>>ES</option>
        <option value="ca" <?php selected( $selected_lang, 'ca' ); ?>>CA</option>
        <option value="undef" <?php selected( $selected_lang, 'undef' ); ?>><?php esc_html_e( 'Sense definir', 'fcsd' ); ?></option>
    </select>
    
    <input type="date" name="news_date_from" value="<?php echo esc_attr( $date_from_val ); ?>" 
           placeholder="<?php esc_attr_e( 'Des de', 'fcsd' ); ?>" style="margin-left:8px;" />
    <input type="date" name="news_date_to" value="<?php echo esc_attr( $date_to_val ); ?>" 
           placeholder="<?php esc_attr_e( 'Fins', 'fcsd' ); ?>" style="margin-left:4px;" />
<?php } );

add_action( 'pre_get_posts', function ( $query ) {
    global $pagenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow ) return;
    if ( empty( $query->query_vars['post_type'] ) || 'news' !== $query->query_vars['post_type'] ) return;
    if ( ! $query->is_main_query() ) return;

    $query->set( 'orderby', 'date' );
    $query->set( 'order', 'DESC' );

    // Filtro de categoría
    if ( ! empty( $_GET['news_category'] ) ) {
        $cat_id = (int) $_GET['news_category'];
        if ( $cat_id > 0 ) {
            $tax_query   = (array) $query->get( 'tax_query' );
            $tax_query[] = [ 'taxonomy'=>'category', 'field'=>'term_id', 'terms'=>$cat_id ];
            $query->set( 'tax_query', $tax_query );
        }
    }
    
    // Filtro de fuente
    if ( ! empty( $_GET['news_source_filter'] ) ) {
        $source = sanitize_text_field( wp_unslash( $_GET['news_source_filter'] ) );
        $meta_query = (array) $query->get( 'meta_query' );
        
        if ( $source === 'internal' ) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'news_source',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'news_source',
                    'value'   => 'exit21',
                    'compare' => '!=',
                ],
            ];
        } else {
            $meta_query[] = [
                'key'     => 'news_source',
                'value'   => $source,
                'compare' => '=',
            ];
        }
        
        $query->set( 'meta_query', $meta_query );
    }

    // Filtro Home carousel
    if ( isset( $_GET['news_home_carousel'] ) && $_GET['news_home_carousel'] !== '' ) {
        $home = sanitize_text_field( wp_unslash( $_GET['news_home_carousel'] ) );
        $meta_query = (array) $query->get( 'meta_query' );
        if ( $home === '1' ) {
            $meta_query[] = [
                'key'   => '_fcsd_show_in_home_carousel',
                'value' => '1',
            ];
        } elseif ( $home === '0' ) {
            $meta_query[] = [
                'relation' => 'OR',
                [ 'key' => '_fcsd_show_in_home_carousel', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_fcsd_show_in_home_carousel', 'value' => '1', 'compare' => '!=' ],
            ];
        }
        $query->set( 'meta_query', $meta_query );
    }


    // Filtro de idioma
    if ( isset( $_GET['news_lang'] ) && $_GET['news_lang'] !== '' ) {
        $lang = sanitize_text_field( wp_unslash( $_GET['news_lang'] ) );
        $meta_query = (array) $query->get( 'meta_query' );

        if ( $lang === 'undef' ) {
            $meta_query[] = [
                'relation' => 'OR',
                [ 'key' => 'news_language', 'compare' => 'NOT EXISTS' ],
                [ 'key' => 'news_language', 'value' => '', 'compare' => '=' ],
            ];
        } else {
            $meta_query[] = [
                'key'     => 'news_language',
                'value'   => $lang,
                'compare' => '=',
            ];
        }

        $query->set( 'meta_query', $meta_query );
    }

    // Filtro de fechas
    $date_query = [];
    if ( ! empty( $_GET['news_date_from'] ) ) {
        $date_query[] = [ 'column'=>'post_date', 'after' => sanitize_text_field( wp_unslash($_GET['news_date_from']) ).' 00:00:00' ];
    }
    if ( ! empty( $_GET['news_date_to'] ) ) {
        $date_query[] = [ 'column'=>'post_date', 'before' => sanitize_text_field( wp_unslash($_GET['news_date_to']) ).' 23:59:59' ];
    }
    if ( $date_query ) {
        $query->set( 'date_query', $date_query );
    }
} );

/**
 * ============================================================================
 * METABOX DE INFORMACIÓN EN EL EDITOR
 * ============================================================================
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'fcsd_news_info',
        __( 'Información de Sincronización', 'fcsd' ),
        'fcsd_news_info_metabox',
        'news',
        'side',
        'default'
    );
});

function fcsd_news_info_metabox( $post ) {
    $source = get_post_meta( $post->ID, 'news_source', true );
    $external_url = get_post_meta( $post->ID, 'news_external_url', true );
    ?>
    <div style="padding: 10px 0;">
        <p><strong><?php esc_html_e( 'Fuente:', 'fcsd' ); ?></strong><br/>
        <?php echo $source ? '<span style="background:#2271b1;color:white;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;">' . esc_html( strtoupper($source) ) . '</span>' : '—'; ?></p>
        
        <?php if ( $external_url ) : ?>
        <p><strong><?php esc_html_e( 'URL original:', 'fcsd' ); ?></strong><br/>
        <a href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="noopener" class="button button-small">
            <?php esc_html_e( 'Ver original', 'fcsd' ); ?> ↗
        </a></p>
        <?php endif; ?>
    </div>
    <?php
}
