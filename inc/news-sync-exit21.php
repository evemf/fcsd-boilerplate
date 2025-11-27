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
 * ============================================================================
 * EXTRACCIÓN COMPLETA DE CONTENIDO VIA WEB SCRAPING
 * ============================================================================
 */
function fcsd_fetch_full_content( $url ) {
    fcsd_log( "Intentando obtener contenido completo de: {$url}", 'debug' );
    
    $args = [
        'timeout'     => 30,
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
    
    // Convertir encoding si es necesario
    $html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
    @$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
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
    @$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
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
    $response = wp_remote_get( $url, ['timeout' => 15] );
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

    $feed_url = 'https://www.exit21.org/feed/';

    // Fuerza refresco
    delete_transient( 'feed_' . md5( $feed_url ) );
    delete_transient( 'feed_mod_' . md5( $feed_url ) );

    $rss = fetch_feed( $feed_url );
    if ( is_wp_error( $rss ) ) {
        fcsd_log( 'Error al obtener feed RSS: ' . $rss->get_error_message(), 'error' );
        return;
    }

    $max = $rss->get_item_quantity( 500 );
    if ( ! $max ) {
        fcsd_log( 'No hay items en el feed', 'warning' );
        return;
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
        $existing = get_posts([
            'post_type'      => 'news',
            'post_status'    => 'any',
            'meta_key'       => 'exit21_guid',
            'meta_value'     => $guid,
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

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
        update_post_meta( $post_id, 'exit21_guid', $guid );
        update_post_meta( $post_id, 'news_source', 'exit21' );
        update_post_meta( $post_id, 'news_external_url', $permalink );

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
 * INTERFAZ DE ADMINISTRACIÓN
 * ============================================================================
 */
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

function fcsd_render_exit21_sync_page() {
    if ( isset( $_POST['fcsd_do_sync'] ) && check_admin_referer( 'fcsd_sync_exit21' ) ) {
        set_time_limit( 300 ); // 5 minutos
        fcsd_sync_exit21_news();
        echo '<div class="updated"><p><strong>' . esc_html__( 'Importació completada!', 'fcsd' ) . '</strong></p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Sincronización EXIT21', 'fcsd' ); ?></h1>
        
        <div class="card" style="max-width: 800px;">
            <h2><?php esc_html_e( 'Importar Feed EXIT21', 'fcsd' ); ?></h2>
            <p><?php esc_html_e( 'Importa el feed completo de exit21.org extrayendo todo el contenido disponible de cada artículo.', 'fcsd' ); ?></p>
            <form method="post">
                <?php wp_nonce_field( 'fcsd_sync_exit21' ); ?>
                <p>
                    <button type="submit" name="fcsd_do_sync" class="button button-primary button-hero">
                        <span class="dashicons dashicons-update" style="margin-top:8px;"></span>
                        <?php esc_html_e( 'Importar Ahora', 'fcsd' ); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
    
    <style>
        .card { margin: 20px 0; padding: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; }
        .button-hero { padding: 10px 20px !important; height: auto !important; font-size: 14px !important; }
    </style>
<?php }

/**
 * ============================================================================
 * CRON AUTOMÁTICO
 * ============================================================================
 */
add_action( 'after_switch_theme', function () {
    if ( ! wp_next_scheduled( 'fcsd_cron_sync_exit21_news' ) ) {
        wp_schedule_event( time(), 'hourly', 'fcsd_cron_sync_exit21_news' );
        fcsd_log( 'Cron programado', 'info' );
    }
} );

add_action( 'init', function () {
    if ( ! wp_next_scheduled( 'fcsd_cron_sync_exit21_news' ) ) {
        wp_schedule_event( time(), 'hourly', 'fcsd_cron_sync_exit21_news' );
    }
} );

add_action( 'fcsd_cron_sync_exit21_news', 'fcsd_sync_exit21_news' );

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
